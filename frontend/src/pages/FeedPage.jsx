import { useEffect, useState } from 'react';
import { api, clearToken } from '../api';

function CommentItem({ comment, onToggleLike, onReply }) {
  const [replyText, setReplyText] = useState('');

  const submitReply = (event) => {
    event.preventDefault();
    if (!replyText.trim()) {
      return;
    }
    onReply(comment.id, replyText.trim());
    setReplyText('');
  };

  return (
    <div className="_comment_main mt-3">
      <div className="_comment_area w-100">
        <div className="_comment_details">
          <div className="_comment_name">
            <h4 className="_comment_name_title">{comment.author.displayName}</h4>
          </div>
          <p className="_comment_status_text mb-2">{comment.content}</p>
          <div className="d-flex align-items-center gap-2">
            <button className="btn btn-sm btn-light" onClick={() => onToggleLike(comment.id)}>
              {comment.likedByMe ? 'Unlike' : 'Like'} ({comment.likesCount})
            </button>
            <button
              className="btn btn-sm btn-outline-secondary"
              onClick={() =>
                alert(comment.likes.length ? comment.likes.map((like) => like.displayName).join(', ') : 'No likes yet')
              }
            >
              Who liked?
            </button>
          </div>
        </div>

        <form className="mt-2" onSubmit={submitReply}>
          <input
            className="form-control"
            placeholder="Write a reply"
            value={replyText}
            onChange={(event) => setReplyText(event.target.value)}
          />
        </form>

        {comment.replies?.map((reply) => (
          <div className="ms-4 mt-2" key={reply.id}>
            <CommentItem comment={reply} onToggleLike={onToggleLike} onReply={onReply} />
          </div>
        ))}
      </div>
    </div>
  );
}

export default function FeedPage() {
  const [me, setMe] = useState(null);
  const [posts, setPosts] = useState([]);
  const [content, setContent] = useState('');
  const [visibility, setVisibility] = useState('public');
  const [image, setImage] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [commentTextByPost, setCommentTextByPost] = useState({});

  const loadData = async () => {
    const [meResponse, feedResponse] = await Promise.all([api.get('/me'), api.get('/feed')]);
    setMe(meResponse.data.user);
    setPosts(feedResponse.data.posts || []);
  };

  useEffect(() => {
    loadData().catch(() => {
      clearToken();
      window.location.href = '/login';
    });
  }, []);

  const onCreatePost = async (event) => {
    event.preventDefault();
    if (!content.trim()) {
      return;
    }

    const formData = new FormData();
    formData.append('content', content.trim());
    formData.append('visibility', visibility);
    if (image) {
      formData.append('image', image);
    }

    setLoading(true);
    setError('');
    try {
      await api.post('/posts', formData);
      setContent('');
      setVisibility('public');
      setImage(null);
      await loadData();
    } catch (submitError) {
      setError(submitError.response?.data?.message || 'Failed to create post.');
    } finally {
      setLoading(false);
    }
  };

  const togglePostLike = async (postId) => {
    await api.post(`/posts/${postId}/likes/toggle`);
    await loadData();
  };

  const toggleCommentLike = async (commentId) => {
    await api.post(`/comments/${commentId}/likes/toggle`);
    await loadData();
  };

  const addComment = async (postId) => {
    const text = commentTextByPost[postId]?.trim();
    if (!text) {
      return;
    }

    await api.post(`/posts/${postId}/comments`, { content: text });
    setCommentTextByPost((prev) => ({ ...prev, [postId]: '' }));
    await loadData();
  };

  const addReply = async (commentId, text) => {
    await api.post(`/comments/${commentId}/replies`, { content: text });
    await loadData();
  };

  const logout = () => {
    clearToken();
    window.location.href = '/login';
  };

  return (
    <div className="_feed_wrapper _layout_main_wrapper">
      <div className="container py-4">
        <div className="d-flex justify-content-between align-items-center mb-3">
          <h3 className="m-0">Feed</h3>
          <div className="d-flex align-items-center gap-3">
            <span>{me?.displayName}</span>
            <button className="btn btn-outline-dark btn-sm" onClick={logout}>
              Logout
            </button>
          </div>
        </div>

        <div className="_feed_inner_text_area _b_radious6 _padd_t24 _padd_b24 _padd_r24 _padd_l24 _mar_b16">
          <form onSubmit={onCreatePost}>
            {error && <div className="alert alert-danger">{error}</div>}
            <textarea
              className="form-control"
              rows="4"
              placeholder="Write something..."
              value={content}
              onChange={(event) => setContent(event.target.value)}
            />
            <div className="d-flex flex-wrap align-items-center gap-2 mt-2">
              <select
                className="form-select"
                style={{ maxWidth: 180 }}
                value={visibility}
                onChange={(event) => setVisibility(event.target.value)}
              >
                <option value="public">Public</option>
                <option value="private">Private</option>
              </select>
              <input
                className="form-control"
                style={{ maxWidth: 260 }}
                type="file"
                accept="image/*"
                onChange={(event) => setImage(event.target.files?.[0] || null)}
              />
              <button className="btn btn-primary" disabled={loading}>
                {loading ? 'Posting...' : 'Post'}
              </button>
            </div>
          </form>
        </div>

        {posts.map((post) => (
          <div className="_feed_inner_timeline_post_area _b_radious6 _padd_b24 _padd_t24 _mar_b16" key={post.id}>
            <div className="_feed_inner_timeline_content _padd_r24 _padd_l24">
              <h4 className="_feed_inner_timeline_post_box_title">{post.author.displayName}</h4>
              <p className="_feed_inner_timeline_post_para">{post.content}</p>
              {post.imageUrl && <img src={post.imageUrl} alt="post" className="img-fluid rounded mb-3" />}
              <p className="small text-muted">Visibility: {post.visibility}</p>

              <div className="d-flex align-items-center gap-2 mb-3">
                <button className="btn btn-sm btn-light" onClick={() => togglePostLike(post.id)}>
                  {post.likedByMe ? 'Unlike' : 'Like'} ({post.likesCount})
                </button>
                <button
                  className="btn btn-sm btn-outline-secondary"
                  onClick={() =>
                    alert(post.likes.length ? post.likes.map((like) => like.displayName).join(', ') : 'No likes yet')
                  }
                >
                  Who liked?
                </button>
              </div>

              <div className="_feed_inner_timeline_cooment_area p-0">
                <div className="_feed_inner_comment_box p-2">
                  <div className="d-flex gap-2">
                    <input
                      className="form-control"
                      placeholder="Write a comment"
                      value={commentTextByPost[post.id] || ''}
                      onChange={(event) =>
                        setCommentTextByPost((prev) => ({ ...prev, [post.id]: event.target.value }))
                      }
                    />
                    <button className="btn btn-primary" onClick={() => addComment(post.id)}>
                      Comment
                    </button>
                  </div>
                </div>

                <div className="_timline_comment_main mt-3">
                  {post.comments?.map((comment) => (
                    <CommentItem
                      key={comment.id}
                      comment={comment}
                      onToggleLike={toggleCommentLike}
                      onReply={addReply}
                    />
                  ))}
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

