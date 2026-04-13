import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { api, clearToken, resolveMediaUrl } from '../api';
import Skeleton, { SkeletonCardRows, SkeletonLine } from '../components/Skeleton';
import StatePanel from '../components/StatePanel';

const formatDate = (value) => {
  if (!value) return 'Unknown date';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'Unknown date';
  return date.toLocaleString();
};

const getDisplayName = (user) => user?.displayName
  || [user?.firstName, user?.lastName].filter(Boolean).join(' ')
  || user?.email
  || 'Unknown user';

export default function ProfilePage() {
  const { userId } = useParams();
  const navigate = useNavigate();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [profile, setProfile] = useState(null);
  const [posts, setPosts] = useState([]);
  const [activeTab, setActiveTab] = useState('all');
  const [deletingPostId, setDeletingPostId] = useState(null);

  const initials = useMemo(() => {
    if (!profile?.displayName) return '?';
    const parts = profile.displayName.split(/\s+/).filter(Boolean);
    return parts.slice(0, 2).map((part) => part[0]?.toUpperCase() || '').join('') || '?';
  }, [profile]);

  const filteredPosts = useMemo(() => {
    const normalizedPosts = posts.map((post) => ({
      ...post,
      visibility: post?.visibility || 'public',
      comments: Array.isArray(post?.comments) ? post.comments : [],
    }));

    if (activeTab === 'public') {
      return normalizedPosts.filter((post) => post.visibility === 'public');
    }

    if (activeTab === 'private') {
      return normalizedPosts.filter((post) => post.visibility === 'private');
    }

    return normalizedPosts;
  }, [activeTab, posts]);

  useEffect(() => {
    let isMounted = true;

    const loadProfile = async () => {
      setLoading(true);
      setError('');

      try {
        const response = await api.get(`/v1/profiles/${userId}`);
        if (!isMounted) return;

        setProfile(response.data?.profile || null);
        setPosts(Array.isArray(response.data?.posts) ? response.data.posts : []);
      } catch (loadError) {
        if (!isMounted) return;

        const status = loadError?.response?.status;
        if (status === 401 || status === 403) {
          clearToken();
          navigate('/login', { replace: true });
          return;
        }

        setError(loadError?.response?.data?.message || 'Failed to load profile.');
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    };

    if (userId) {
      loadProfile();
    }

    return () => {
      isMounted = false;
    };
  }, [navigate, userId]);

  useEffect(() => {
    setActiveTab('all');
  }, [userId]);

  useEffect(() => {
    if (profile && !profile.isMe && activeTab === 'private') {
      setActiveTab('all');
    }
  }, [activeTab, profile]);

  const handleDeletePost = async (postId) => {
    const confirmed = window.confirm('Are you sure you want to delete this post?');
    if (!confirmed) {
      return;
    }

    setDeletingPostId(postId);
    setError('');
    try {
      await api.delete(`/v1/posts/${postId}`);
      setPosts((prevPosts) => prevPosts.filter((post) => post.id !== postId));
    } catch (deleteError) {
      setError(deleteError?.response?.data?.message || 'Failed to delete post.');
    } finally {
      setDeletingPostId(null);
    }
  };

  return (
    <div className="profile_page">
      <div className="profile_page_header _feed_inner_area _b_radious6 _padd_t24 _padd_b24 _padd_r24 _padd_l24">
        <div className="profile_page_header_row">
          <div>
            <h4 className="_title5 profile_page_heading">View Profile</h4>
            <p className="profile_meta profile_page_heading_meta">
              {profile ? `${profile.displayName} profile overview` : 'Profile overview'}
            </p>
          </div>
          <Link to="/feed" className="profile_page_back">Back to feed</Link>
        </div>
      </div>

      {loading && (
        <div className="profile_loading_skeleton" aria-hidden="true">
          <Skeleton className="profile_loading_hero" />
          <div className="profile_loading_grid">
            <div className="profile_loading_card">
              <SkeletonLine width="45%" />
              <SkeletonLine width="70%" />
              <SkeletonCardRows rows={3} />
            </div>
            <div className="profile_loading_card">
              <SkeletonLine width="35%" />
              <SkeletonCardRows rows={4} />
            </div>
          </div>
        </div>
      )}

      {!loading && error && (
        <StatePanel variant="error" title="Could not load profile" message={error} className="profile_page_state" />
      )}

      {!loading && !error && profile && (
        <>
          <section className="profile_hero">
            <div
              className="profile_hero_cover"
              style={profile.coverUrl ? { backgroundImage: `url(${resolveMediaUrl(profile.coverUrl)})` } : undefined}
            >
              {!profile.coverUrl && <div className="profile_hero_cover_overlay" />}
            </div>

            <div className="profile_hero_body">
              <div className="profile_hero_avatar_wrap">
                {profile.avatarUrl ? (
                  <img src={resolveMediaUrl(profile.avatarUrl)} alt={profile.displayName} className="profile_hero_avatar" />
                ) : (
                  <div className="profile_hero_avatar_fallback">{initials}</div>
                )}
              </div>

              <div className="profile_hero_identity">
                <h1 className="profile_name">{profile.displayName}</h1>
                <p className="profile_meta">Joined {formatDate(profile.joinedAt)}</p>
                <p className="profile_meta">{profile.stats?.postsCount ?? 0} total posts</p>
              </div>

              <div className="profile_hero_actions">
                <span className="profile_visibility_chip">{profile.isMe ? 'Your profile' : 'Public profile view'}</span>
              </div>
            </div>
          </section>

          <div className="profile_grid">
            <aside className="profile_sidebar">
              <section className="profile_card">
                <h2 className="profile_section_title">Intro</h2>
                <p className="profile_meta">{profile.bio || 'No bio yet.'}</p>
                {profile.email && <p className="profile_meta">Email: {profile.email}</p>}
                <p className="profile_meta">Name: {profile.firstName} {profile.lastName}</p>
              </section>

              <section className="profile_card">
                <h2 className="profile_section_title">Profile stats</h2>
                <div className="profile_stats_grid">
                  <div className="profile_stat_item"><strong>{profile.stats?.postsCount ?? 0}</strong><span>Posts</span></div>
                  <div className="profile_stat_item"><strong>{profile.stats?.publicPostsCount ?? 0}</strong><span>Public</span></div>
                  <div className="profile_stat_item"><strong>{profile.stats?.privatePostsCount ?? 0}</strong><span>Private</span></div>
                  <div className="profile_stat_item"><strong>{profile.stats?.likesReceivedCount ?? 0}</strong><span>Likes</span></div>
                  <div className="profile_stat_item"><strong>{profile.stats?.commentsReceivedCount ?? 0}</strong><span>Comments</span></div>
                </div>
              </section>
            </aside>

            <section className="profile_timeline">
              <div className="profile_posts_header profile_card">
                <h2 className="profile_section_title">Posts</h2>
                <div className="profile_tabs">
                  <button type="button" className={activeTab === 'all' ? 'profile_tab profile_tab_active' : 'profile_tab'} onClick={() => setActiveTab('all')}>All</button>
                  <button type="button" className={activeTab === 'public' ? 'profile_tab profile_tab_active' : 'profile_tab'} onClick={() => setActiveTab('public')}>Public</button>
                  {profile.isMe && (
                    <button type="button" className={activeTab === 'private' ? 'profile_tab profile_tab_active' : 'profile_tab'} onClick={() => setActiveTab('private')}>Private</button>
                  )}
                </div>
              </div>

              {filteredPosts.length === 0 ? (
                <StatePanel variant="empty" title="No posts yet" message="No posts in this section yet." className="profile_page_state" />
              ) : (
                filteredPosts.map((post) => (
                  <article className="profile_post_card" key={post.id}>
                    <div className="profile_post_header">
                      <div>
                        <strong>{post.author?.displayName || profile.displayName}</strong>
                        <p className="profile_meta profile_post_meta">{formatDate(post.createdAt)}</p>
                      </div>
                      <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                        <span className="profile_post_visibility">{post.visibility}</span>
                        {post.canDelete && (
                          <button
                            type="button"
                            className="profile_tab"
                            disabled={deletingPostId === post.id}
                            onClick={() => handleDeletePost(post.id)}
                          >
                            {deletingPostId === post.id ? 'Deleting...' : 'Delete'}
                          </button>
                        )}
                      </div>
                    </div>
                    <p className="profile_meta profile_post_meta">By {getDisplayName(post.author)}</p>
                    <p className="profile_post_content">{post.content}</p>
                    {post.imageUrl && (
                      <img
                        src={resolveMediaUrl(post.imageUrl)}
                        alt="Post"
                        className="profile_post_image"
                      />
                    )}
                    <div className="profile_post_footer">
                      <span>{post.likesCount || 0} likes</span>
                      <span>{(post.comments || []).length} comments</span>
                    </div>
                  </article>
                ))
              )}
            </section>
          </div>
        </>
      )}
    </div>
  );
}

