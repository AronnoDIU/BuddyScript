import { useState, useEffect, useRef } from 'react';
import { MessageCircle } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import CreateGroupPost from './CreateGroupPost';
import GroupPost from './GroupPost';
import './GroupFeed.css';

export default function GroupFeed({ group, permissions, onPostCreated }) {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [hasMore, setHasMore] = useState(true);
  const [showCreatePost, setShowCreatePost] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const lastPostRef = useRef(null);

  useEffect(() => {
    const timeout = setTimeout(() => {
      fetchPosts(searchQuery);
    }, 250);

    return () => clearTimeout(timeout);
  }, [group?.id, searchQuery]);

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && hasMore && !loadingMore) {
          loadMorePosts();
        }
      },
      { threshold: 0.1 }
    );

    if (lastPostRef.current) {
      observer.observe(lastPostRef.current);
    }

    return () => {
      if (lastPostRef.current) {
        observer.unobserve(lastPostRef.current);
      }
    };
  }, [hasMore, loadingMore]);

  const fetchPosts = async (query = '', { offset = 0, append = false } = {}) => {
    try {
      if (!append) {
        setLoading(true);
      }
      const response = await groupsApi.getGroupPosts(group.id, {
        q: query,
        limit: 20,
        offset,
      });
      const nextPosts = response.data.posts || [];
      const pagination = response.data.pagination || {};

      setPosts((prev) => {
        if (append) {
          const map = new Map([...prev, ...nextPosts].map((item) => [item.id, item]));
          return Array.from(map.values());
        }

        return nextPosts;
      });
      setHasMore(Boolean(pagination.hasMore) || nextPosts.length === 20);
    } catch (error) {
      console.error('Failed to fetch posts:', error);
    } finally {
      if (!append) {
        setLoading(false);
      }
    }
  };

  const loadMorePosts = async () => {
    if (!hasMore || loadingMore) return;

    try {
      setLoadingMore(true);
      await fetchPosts(searchQuery, { offset: posts.length, append: true });
    } catch (error) {
      console.error('Failed to load more posts:', error);
    } finally {
      setLoadingMore(false);
    }
  };

  const handleSearch = (e) => {
    setSearchQuery(e.target.value);
  };

  const handlePostCreated = (newPost) => {
    setPosts((prev) => [newPost.post, ...prev]);
    setShowCreatePost(false);
    onPostCreated();
  };

  const handlePostUpdated = (updatedPost) => {
    setPosts(posts.map(post => 
      post.id === updatedPost.id ? updatedPost : post
    ));
  };

  const handlePostDeleted = (postId) => {
    setPosts(posts.filter(post => post.id !== postId));
  };


  return (
    <div className="group-feed">
      {/* Search Bar */}
      <div className="group-feed__search">
        <input
          type="text"
          placeholder="Search posts in this group..."
          value={searchQuery}
          onChange={handleSearch}
          className="group-feed__search-input"
        />
      </div>

      {/* Create Post */}
      {permissions?.post && (
        <div className="group-feed__create-post">
          <button
            className="group-feed__create-post-btn"
            onClick={() => setShowCreatePost(true)}
          >
            <div className="group-feed__create-post-avatar">
              <div className="group-feed__create-post-avatar-placeholder">
                <span>You</span>
              </div>
            </div>
            <div className="group-feed__create-post-input">
              What&apos;s on your mind, {group.name.split(' ')[0]}?
            </div>
          </button>
        </div>
      )}

      {/* Posts */}
      <div className="group-feed__posts">
        {loading ? (
          <div className="group-feed__loading">
            <div className="group-feed__spinner"></div>
            <p>Loading posts...</p>
          </div>
        ) : posts.length === 0 ? (
          <div className="group-feed__empty">
            <MessageCircle size={48} className="group-feed__empty-icon" />
            <h3>No Posts Yet</h3>
            <p>Be the first to share something with the group!</p>
            {permissions?.post && (
              <button
                onClick={() => setShowCreatePost(true)}
                className="group-feed__empty-action"
              >
                Create First Post
              </button>
            )}
          </div>
        ) : (
          <>
            {posts.map((post, index) => (
              <div
                key={post.id}
                ref={index === posts.length - 1 ? lastPostRef : null}
              >
                <GroupPost
                  post={post}
                  group={group}
                  onPostUpdated={handlePostUpdated}
                  onPostDeleted={handlePostDeleted}
                />
              </div>
            ))}
            
            {loadingMore && (
              <div className="group-feed__loading-more">
                <div className="group-feed__spinner"></div>
                <p>Loading more posts...</p>
              </div>
            )}
            
            {!hasMore && posts.length > 0 && (
              <div className="group-feed__end">
                <p>You&apos;ve seen all posts in this group</p>
              </div>
            )}
          </>
        )}
      </div>

      {/* Create Post Modal */}
      {showCreatePost && (
        <CreateGroupPost
          group={group}
          onClose={() => setShowCreatePost(false)}
          onPostCreated={handlePostCreated}
        />
      )}
    </div>
  );
}
