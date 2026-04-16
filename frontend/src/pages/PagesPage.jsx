import { useEffect, useMemo, useState } from 'react';
import { Search, Plus } from 'lucide-react';
import { pagesApi } from '../api/pages';
import { getApiErrorMessage } from '../api';
import CreatePageModal from '../components/Pages/CreatePageModal';
import CreatePagePost from '../components/Pages/CreatePagePost';
import PageCard from '../components/Pages/PageCard';
import PagePostCard from '../components/Pages/PagePostCard';
import './PagesPage.css';

export default function PagesPage() {
  const [myPages, setMyPages] = useState([]);
  const [discoverPages, setDiscoverPages] = useState([]);
  const [selectedPage, setSelectedPage] = useState(null);
  const [posts, setPosts] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeTab, setActiveTab] = useState('my-pages');
  const [loading, setLoading] = useState(true);
  const [loadingPosts, setLoadingPosts] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [error, setError] = useState('');

  const activePages = useMemo(
    () => (activeTab === 'my-pages' ? myPages : discoverPages),
    [activeTab, myPages, discoverPages]
  );

  const fetchPages = async (query = '') => {
    setLoading(true);
    setError('');
    try {
      const [myPagesResponse, publicPagesResponse] = await Promise.all([
        pagesApi.getPages({ q: query, limit: 40 }),
        pagesApi.getPublicPages({ q: query, limit: 40 }),
      ]);

      const own = myPagesResponse.data?.pages || [];
      const discover = publicPagesResponse.data?.pages || [];
      setMyPages(own);
      setDiscoverPages(discover);

      if (!selectedPage && own.length > 0) {
        setSelectedPage(own[0]);
      }
    } catch (loadError) {
      setError(getApiErrorMessage(loadError, 'Failed to load pages.'));
    } finally {
      setLoading(false);
    }
  };

  const fetchPosts = async (page) => {
    if (!page?.id) {
      setPosts([]);
      return;
    }

    setLoadingPosts(true);
    try {
      const response = await pagesApi.getPagePosts(page.id, { limit: 30 });
      setPosts(response.data?.posts || []);
    } catch (loadError) {
      setError(getApiErrorMessage(loadError, 'Failed to load page posts.'));
    } finally {
      setLoadingPosts(false);
    }
  };

  useEffect(() => {
    const timeout = setTimeout(() => {
      fetchPages(searchQuery);
    }, 250);

    return () => clearTimeout(timeout);
  }, [searchQuery]);

  useEffect(() => {
    fetchPosts(selectedPage);
  }, [selectedPage?.id]);

  const onSearch = (event) => {
    setSearchQuery(event.target.value);
  };

  const onCreatePage = async (payload) => {
    const response = await pagesApi.createPage(payload);
    const newPage = response.data?.page;
    if (newPage) {
      setMyPages((prev) => [newPage, ...prev]);
      setDiscoverPages((prev) => [newPage, ...prev.filter((page) => page.id !== newPage.id)]);
      setSelectedPage(newPage);
    }
    setShowCreateModal(false);
  };

  const onCreatePost = async (payload) => {
    if (!selectedPage?.id) return;
    const response = await pagesApi.createPagePost(selectedPage.id, payload);
    const newPost = response.data?.post;

    if (newPost) {
      setPosts((prev) => [newPost, ...prev]);
      setMyPages((prev) => prev.map((page) => (
        page.id === selectedPage.id ? { ...page, postsCount: (page.postsCount || 0) + 1 } : page
      )));
      setDiscoverPages((prev) => prev.map((page) => (
        page.id === selectedPage.id ? { ...page, postsCount: (page.postsCount || 0) + 1 } : page
      )));
    }
  };

  const onToggleFollow = async (page) => {
    if (page.isFollowing) {
      await pagesApi.unfollowPage(page.id);
    } else {
      await pagesApi.followPage(page.id);
    }

    await fetchPages(searchQuery);
  };

  return (
    <div className="fb-pages">
      <header className="fb-pages__header">
        <div>
          <h1>Pages</h1>
          <p>Create, manage, and publish posts from your pages.</p>
        </div>

        <div className="fb-pages__header-actions">
          <label className="fb-pages__search">
            <Search size={16} />
            <input value={searchQuery} onChange={onSearch} placeholder="Search pages" />
          </label>

          <button type="button" className="pages-btn pages-btn--primary" onClick={() => setShowCreateModal(true)}>
            <Plus size={16} />
            Create Page
          </button>
        </div>
      </header>

      <div className="fb-pages__layout">
        <aside className="fb-pages__left card-ui">
          <div className="fb-pages__tabs">
            <button
              type="button"
              className={activeTab === 'my-pages' ? 'is-active' : ''}
              onClick={() => setActiveTab('my-pages')}
            >
              Your Pages
            </button>
            <button
              type="button"
              className={activeTab === 'discover' ? 'is-active' : ''}
              onClick={() => setActiveTab('discover')}
            >
              Discover
            </button>
          </div>

          {loading ? (
            <p className="fb-pages__muted">Loading pages...</p>
          ) : activePages.length === 0 ? (
            <p className="fb-pages__muted">No pages found.</p>
          ) : (
            <div className="fb-pages__page-list">
              {activePages.map((page) => (
                <PageCard
                  key={page.id}
                  page={page}
                  selected={selectedPage?.id === page.id}
                  onSelect={setSelectedPage}
                  onToggleFollow={onToggleFollow}
                />
              ))}
            </div>
          )}
        </aside>

        <section className="fb-pages__center">
          {!selectedPage ? (
            <div className="card-ui fb-pages__empty">Select a page to start posting.</div>
          ) : (
            <>
              <article className="card-ui fb-pages__hero">
                <h2>{selectedPage.name}</h2>
                <p>{selectedPage.description || 'No description added yet.'}</p>
                <div className="fb-pages__hero-stats">
                  <span>{(selectedPage.followersCount || 0).toLocaleString()} followers</span>
                  <span>{selectedPage.postsCount || 0} posts</span>
                  <span>{selectedPage.category || 'Community'}</span>
                </div>
              </article>

              {selectedPage.isOwner || selectedPage.permissions?.post ? (
                <CreatePagePost page={selectedPage} onCreatePost={onCreatePost} />
              ) : (
                <div className="card-ui fb-pages__empty">Follow this page to see updates.</div>
              )}

              <div className="fb-pages__feed">
                {loadingPosts ? (
                  <div className="card-ui fb-pages__empty">Loading posts...</div>
                ) : posts.length === 0 ? (
                  <div className="card-ui fb-pages__empty">No posts yet. Create the first post.</div>
                ) : (
                  posts.map((post) => <PagePostCard key={post.id} post={post} page={selectedPage} />)
                )}
              </div>
            </>
          )}
        </section>

        <aside className="fb-pages__right">
          <div className="card-ui fb-pages__panel">
            <h3>Sponsored</h3>
            <p>Grow your audience by promoting your Page posts.</p>
            <button type="button">Create Promotion</button>
          </div>

          <div className="card-ui fb-pages__panel">
            <h3>Contacts</h3>
            <ul>
              <li>Steve Jobs</li>
              <li>Ryan Roslansky</li>
              <li>Dylan Field</li>
              <li>Chris Nguyen</li>
            </ul>
          </div>
        </aside>
      </div>

      {error && <p className="fb-pages__error">{error}</p>}

      {showCreateModal && (
        <CreatePageModal
          onClose={() => setShowCreateModal(false)}
          onCreate={onCreatePage}
        />
      )}
    </div>
  );
}

