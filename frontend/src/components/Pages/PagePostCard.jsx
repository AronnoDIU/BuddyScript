import { Image as ImageIcon, MessageCircle, ThumbsUp } from 'lucide-react';

const formatDate = (value) => {
  if (!value) return 'Just now';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'Just now';
  return date.toLocaleString();
};

export default function PagePostCard({ post, page }) {
  return (
    <article className="pages-post-card">
      <div className="pages-post-card__head">
        <span className="pages-post-card__avatar">{page.name?.charAt(0)?.toUpperCase() || 'P'}</span>
        <div>
          <strong>{page.name}</strong>
          <p>{formatDate(post.createdAt)}</p>
        </div>
      </div>

      <p className="pages-post-card__content">{post.content}</p>

      {post.imageUrl && (
        <div className="pages-post-card__image-wrap">
          <img src={post.imageUrl} alt="Post attachment" />
        </div>
      )}

      {!post.imageUrl && (
        <div className="pages-post-card__placeholder">
          <ImageIcon size={16} />
          No media attached
        </div>
      )}

      <div className="pages-post-card__stats">
        <span><ThumbsUp size={14} /> {post.likesCount || 0}</span>
        <span><MessageCircle size={14} /> {post.commentsCount || 0}</span>
      </div>
    </article>
  );
}

