import { useState } from 'react';
import { Heart, MessageCircle, Share2, MoreHorizontal, Send } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import PostComment from './PostComment.jsx';
import './GroupPost.css';

export default function GroupPost({ post, group, permissions, onPostDeleted }) {
  const [showComments, setShowComments] = useState(false);
  const [commentText, setCommentText] = useState('');
  const [isSubmittingComment, setIsSubmittingComment] = useState(false);
  const [isLiking, setIsLiking] = useState(false);
  const [showMenu, setShowMenu] = useState(false);
  const [localPost, setLocalPost] = useState(post);

  const handleLike = async () => {
    try {
      setIsLiking(true);
      const response = await groupsApi.togglePostLike(localPost.id);
      setLocalPost(prev => ({
        ...prev,
        likedByMe: response.data.liked,
        likesCount: response.data.likes.length,
        likes: response.data.likes
      }));
    } catch (error) {
      console.error('Failed to toggle like:', error);
    } finally {
      setIsLiking(false);
    }
  };

  const handleComment = async (e) => {
    e.preventDefault();
    if (!commentText.trim()) return;

    try {
      setIsSubmittingComment(true);
      const response = await groupsApi.addPostComment(localPost.id, commentText);
      setLocalPost(prev => ({
        ...prev,
        commentsCount: prev.commentsCount + 1,
        comments: [response.data.comment, ...prev.comments]
      }));
      setCommentText('');
    } catch (error) {
      console.error('Failed to add comment:', error);
    } finally {
      setIsSubmittingComment(false);
    }
  };

  const handleDelete = async () => {
    if (window.confirm('Are you sure you want to delete this post?')) {
      try {
        await groupsApi.deletePost(localPost.id);
        onPostDeleted(localPost.id);
      } catch (error) {
        console.error('Failed to delete post:', error);
      }
    }
  };

  const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  };

  return (
    <div className="group-post">
      {/* Post Header */}
      <div className="group-post__header">
        <div className="group-post__author">
          <div className="group-post__author-avatar">
            <div className="group-post__author-avatar-placeholder">
              {localPost.author.displayName.charAt(0).toUpperCase()}
            </div>
          </div>
          <div className="group-post__author-info">
            <div className="group-post__author-name">{localPost.author.displayName}</div>
            <div className="group-post__author-meta">
              <span>{formatTimeAgo(localPost.createdAt)}</span>
              <span>·</span>
              <span className="group-post__group-name">{group.name}</span>
            </div>
          </div>
        </div>

        {showMenu && (
          <div className="group-post__menu">
            <button
              className="group-post__menu-btn"
              onClick={() => setShowMenu(!showMenu)}
            >
              <MoreHorizontal size={20} />
            </button>
            
            {showMenu && (
              <div className="group-post__dropdown">
                {permissions?.admin && (
                  <button className="group-post__dropdown-item danger" onClick={handleDelete}>
                    Delete Post
                  </button>
                )}
                <button className="group-post__dropdown-item">
                  Report Post
                </button>
                <button className="group-post__dropdown-item">
                  Turn Off Notifications
                </button>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Post Content */}
      <div className="group-post__content">
        <div className="group-post__text">{localPost.content}</div>
        
        {localPost.imageUrl && (
          <div className="group-post__image">
            <img src={localPost.imageUrl} alt="Post image" />
          </div>
        )}

        {localPost.hashtags && localPost.hashtags.length > 0 && (
          <div className="group-post__hashtags">
            {localPost.hashtags.map((hashtag, index) => (
              <span key={index} className="group-post__hashtag">
                #{hashtag}
              </span>
            ))}
          </div>
        )}
      </div>

      {/* Post Actions */}
      <div className="group-post__actions">
        <button
          className={`group-post__action ${localPost.likedByMe ? 'liked' : ''}`}
          onClick={handleLike}
          disabled={isLiking}
        >
          <Heart size={20} fill={localPost.likedByMe ? 'currentColor' : 'none'} />
          {localPost.likesCount > 0 && (
            <span>{localPost.likesCount}</span>
          )}
        </button>

        <button
          className={`group-post__action ${showComments ? 'active' : ''}`}
          onClick={() => setShowComments(!showComments)}
        >
          <MessageCircle size={20} />
          {localPost.commentsCount > 0 && (
            <span>{localPost.commentsCount}</span>
          )}
        </button>

        <button className="group-post__action">
          <Share2 size={20} />
        </button>
      </div>

      {/* Comments Section */}
      {showComments && (
        <div className="group-post__comments">
          {/* Add Comment */}
          <form onSubmit={handleComment} className="group-post__comment-form">
            <div className="group-post__comment-avatar">
              <div className="group-post__comment-avatar-placeholder">
                <span>You</span>
              </div>
            </div>
            <div className="group-post__comment-input-wrapper">
              <input
                type="text"
                placeholder="Write a comment..."
                value={commentText}
                onChange={(e) => setCommentText(e.target.value)}
                className="group-post__comment-input"
              />
              <button
                type="submit"
                disabled={!commentText.trim() || isSubmittingComment}
                className="group-post__comment-submit"
              >
                <Send size={16} />
              </button>
            </div>
          </form>

          {/* Existing Comments */}
          {localPost.comments && localPost.comments.length > 0 && (
            <div className="group-post__comments-list">
              {localPost.comments.map((comment) => (
                <PostComment
                  key={comment.id}
                  comment={comment}
                  onCommentUpdated={(updatedComment) => {
                    setLocalPost(prev => ({
                      ...prev,
                      comments: prev.comments.map(c => 
                        c.id === updatedComment.id ? updatedComment : c
                      )
                    }));
                  }}
                />
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
