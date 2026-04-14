import { api } from '../api';

const LOCAL_PAGES_KEY = 'buddyscript_local_pages';
const LOCAL_PAGE_POSTS_KEY = 'buddyscript_local_page_posts';

const safeParse = (value, fallback) => {
  try {
    return JSON.parse(value) || fallback;
  } catch {
    return fallback;
  }
};

const readLocalPages = () => {
  const saved = safeParse(localStorage.getItem(LOCAL_PAGES_KEY), []);
  if (saved.length) {
    return saved;
  }

  const seeded = [
    {
      id: 'local-page-1',
      name: 'BuddyScript Creators',
      description: 'A place for design, frontend and creator updates.',
      category: 'Community',
      avatarUrl: null,
      followersCount: 1243,
      postsCount: 0,
      isOwner: true,
      isFollowing: true,
      createdAt: new Date().toISOString(),
    },
    {
      id: 'local-page-2',
      name: 'Tech News Daily',
      description: 'Latest product launches and software updates.',
      category: 'News',
      avatarUrl: null,
      followersCount: 9840,
      postsCount: 0,
      isOwner: false,
      isFollowing: false,
      createdAt: new Date().toISOString(),
    },
  ];

  localStorage.setItem(LOCAL_PAGES_KEY, JSON.stringify(seeded));
  return seeded;
};

const writeLocalPages = (pages) => {
  localStorage.setItem(LOCAL_PAGES_KEY, JSON.stringify(pages));
};

const readLocalPosts = () => safeParse(localStorage.getItem(LOCAL_PAGE_POSTS_KEY), {});

const writeLocalPosts = (postsByPage) => {
  localStorage.setItem(LOCAL_PAGE_POSTS_KEY, JSON.stringify(postsByPage));
};

const shouldUseLocalFallback = (error) => {
  const status = error?.response?.status;
  return !status || status === 404 || status === 405;
};

const filterPages = (pages, query) => {
  const normalized = query.trim().toLowerCase();
  if (!normalized) {
    return pages;
  }

  return pages.filter((page) => {
    const bag = `${page.name} ${page.description || ''} ${page.category || ''}`.toLowerCase();
    return bag.includes(normalized);
  });
};

const applyLimitOffset = (items, { limit = 20, offset = 0 } = {}) => {
  return items.slice(offset, offset + limit);
};

const makeId = (prefix) => `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

export const pagesApi = {
  createPage: async (pageData) => {
    try {
      const formData = new FormData();
      formData.append('name', pageData.name);
      if (pageData.description) formData.append('description', pageData.description);
      if (pageData.category) formData.append('category', pageData.category);
      if (pageData.avatar) formData.append('avatar', pageData.avatar);

      return await api.post('/v1/pages', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) {
        throw error;
      }

      const pages = readLocalPages();
      const newPage = {
        id: makeId('local-page'),
        name: pageData.name,
        description: pageData.description || '',
        category: pageData.category || 'Community',
        avatarUrl: null,
        followersCount: 0,
        postsCount: 0,
        isOwner: true,
        isFollowing: true,
        createdAt: new Date().toISOString(),
      };

      writeLocalPages([newPage, ...pages]);
      return { data: { page: newPage } };
    }
  },

  getPages: async (params = {}) => {
    try {
      return await api.get('/v1/pages', { params });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) {
        throw error;
      }

      const pages = readLocalPages().filter((page) => page.isOwner);
      const filtered = filterPages(pages, params.q || '');
      return { data: { pages: applyLimitOffset(filtered, params) } };
    }
  },

  getPublicPages: async (params = {}) => {
    try {
      return await api.get('/v1/pages/public', { params });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) {
        throw error;
      }

      const pages = readLocalPages();
      const filtered = filterPages(pages, params.q || '');
      return { data: { pages: applyLimitOffset(filtered, params) } };
    }
  },

  followPage: async (pageId) => {
    try {
      return await api.post(`/v1/pages/${pageId}/follow`);
    } catch (error) {
      if (!shouldUseLocalFallback(error)) {
        throw error;
      }

      const pages = readLocalPages().map((page) => (
        page.id === pageId
          ? { ...page, isFollowing: true, followersCount: (page.followersCount || 0) + 1 }
          : page
      ));
      writeLocalPages(pages);
      return { data: { success: true } };
    }
  },

  unfollowPage: async (pageId) => {
    try {
      return await api.post(`/v1/pages/${pageId}/unfollow`);
    } catch (error) {
      if (!shouldUseLocalFallback(error)) {
        throw error;
      }

      const pages = readLocalPages().map((page) => (
        page.id === pageId
          ? { ...page, isFollowing: false, followersCount: Math.max((page.followersCount || 1) - 1, 0) }
          : page
      ));
      writeLocalPages(pages);
      return { data: { success: true } };
    }
  },

  createPagePost: async (pageId, postData) => {
    try {
      const formData = new FormData();
      formData.append('content', postData.content);
      if (postData.image) formData.append('image', postData.image);

      return await api.post(`/v1/pages/${pageId}/posts`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) {
        throw error;
      }

      const postsByPage = readLocalPosts();
      const pagePosts = postsByPage[pageId] || [];
      const post = {
        id: makeId('local-page-post'),
        pageId,
        content: postData.content,
        imageUrl: postData.image ? URL.createObjectURL(postData.image) : null,
        author: { displayName: 'You' },
        likesCount: 0,
        commentsCount: 0,
        createdAt: new Date().toISOString(),
      };

      postsByPage[pageId] = [post, ...pagePosts];
      writeLocalPosts(postsByPage);

      const pages = readLocalPages().map((page) => (
        page.id === pageId
          ? { ...page, postsCount: (page.postsCount || 0) + 1 }
          : page
      ));
      writeLocalPages(pages);

      return { data: { post } };
    }
  },

  getPagePosts: async (pageId, params = {}) => {
    try {
      return await api.get(`/v1/pages/${pageId}/posts`, { params });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) {
        throw error;
      }

      const postsByPage = readLocalPosts();
      const pagePosts = postsByPage[pageId] || [];
      const normalized = (params.q || '').trim().toLowerCase();
      const filtered = normalized
        ? pagePosts.filter((post) => post.content.toLowerCase().includes(normalized))
        : pagePosts;

      return { data: { posts: applyLimitOffset(filtered, params) } };
    }
  },
};

