import { useState } from 'react';
import { Heart, MoreHorizontal, Clock } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import './PostComment.css';

export default function PostComment({ comment, onCommentUpdated }) {
  const [isLiking, setIsLiking] = useState(false);
  const [showMenu, setShowMenu] = useState(false);
  const [localComment, setLocalComment] = useState(comment);

  const handleLike = async () => {
    try {
      setIsLiking(true);
      const response = await groupsApi.toggleCommentLike(localComment.id);
      setLocalComment(prev => ({
        ...prev,
        likedByMe: response.data.liked,
        likesCount: response.data.likes.length,
        likes: response.data.likes
      }));
      onCommentUpdated(localComment);
    } catch (error) {
      console.error('Failed to toggle like:', error);
    } finally {
      setIsLiking(false);
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
    <div className="post-comment">
      <div className="post-comment__header">
        <div className="post-comment__author">
          <div className="post-comment__author-avatar">
            <div className="post-comment__author-avatar-placeholder">
              {localComment.author.displayName.charAt(0).toUpperCase()}
            </div>
          </div>
          <div className="post-comment__author-info">
            <div className="post-comment__author-name">{localComment.author.displayName}</div>
            <div className="post-comment__time">
              <Clock size={12} />
              <span>{formatTimeAgo(localComment.createdAt)}</span>
              {localComment.updatedAt && localComment.updatedAt !== localComment.createdAt && (
                <span className="post-comment__edited">· edited</span>
              )}
            </div>
          </div>
        </div>

        {showMenu && (
          <div className="post-comment__menu">
            <button
              className="post-comment__menu-btn"
              onClick={() => setShowMenu(!showMenu)}
            >
              <MoreHorizontal size={16} />
            </button>
            
            {showMenu && (
              <div className="post-comment__dropdown">
                <button className="post-comment__dropdown-item">
                  Report Comment
                </button>
                <button className="post-comment__dropdown-item">
                  Delete Comment
                </button>
              </div>
            )}
          </div>
        )}
      </div>

      <div className="post-comment__content">
        <div className="post-comment__text">{localComment.content}</div>
      </div>

      <div className="post-comment__actions">
        <button
          className={`post-comment__action ${localComment.likedByMe ? 'liked' : ''}`}
          onClick={handleLike}
          disabled={isLiking}
        >
          <Heart size={14} fill={localComment.likedByMe ? 'currentColor' : 'none'} />
          {localComment.likesCount > 0 && (
            <span>{localComment.likesCount}</span>
          )}
        </button>
        
        <button className="post-comment__action">
          Reply
        </button>
      </div>
    </div>
  );
}
