import { useEffect, useRef, useState } from 'react';
import { api, clearToken, resolveMediaUrl } from '../api';

/* ─── Comment Item ─────────────────────────────────────────────────── */
function CommentItem({ comment, onToggleLike, onReply }) {
  const [replyText, setReplyText] = useState('');
  const [showReply, setShowReply] = useState(false);

  const submitReply = (event) => {
    event.preventDefault();
    if (!replyText.trim()) return;
    onReply(comment.id, replyText.trim());
    setReplyText('');
    setShowReply(false);
  };

  return (
    <div className="_comment_main">
      <div className="_comment_image">
        <a href="#0" className="_comment_image_link">
          <img src="/assets/images/comment_img.png" alt="" className="_comment_img1" />
        </a>
      </div>
      <div className="_comment_area">
        <div className="_comment_details">
          <div className="_comment_details_top">
            <div className="_comment_name">
              <a href="#0">
                <h4 className="_comment_name_title">{comment.author?.displayName}</h4>
              </a>
            </div>
          </div>
          <div className="_comment_status">
            <p className="_comment_status_text">
              <span>{comment.content}</span>
            </p>
          </div>
          <div className="_total_reactions">
            <div className="_total_react">
              <span className="_reaction_like">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3" />
                </svg>
              </span>
            </div>
            <span className="_total">{comment.likesCount || 0}</span>
          </div>
          <div className="_comment_reply">
            <div className="_comment_reply_num">
              <ul className="_comment_reply_list">
                <li>
                  <span
                    style={{ cursor: 'pointer' }}
                    onClick={() => onToggleLike(comment.id)}
                  >
                    {comment.likedByMe ? 'Unlike.' : 'Like.'}
                  </span>
                </li>
                <li>
                  <span style={{ cursor: 'pointer' }} onClick={() => setShowReply(!showReply)}>
                    Reply.
                  </span>
                </li>
                <li><span>Share</span></li>
              </ul>
            </div>
          </div>
        </div>

        {showReply && (
          <div className="_feed_inner_comment_box">
            <form className="_feed_inner_comment_box_form" onSubmit={submitReply}>
              <div className="_feed_inner_comment_box_content">
                <div className="_feed_inner_comment_box_content_image">
                  <img src="/assets/images/comment_img.png" alt="" className="_comment_img" />
                </div>
                <div className="_feed_inner_comment_box_content_txt">
                  <textarea
                    className="form-control _comment_textarea"
                    placeholder="Write a reply"
                    value={replyText}
                    onChange={(e) => setReplyText(e.target.value)}
                  />
                </div>
              </div>
            </form>
          </div>
        )}

        {comment.replies?.map((reply) => (
          <div className="ms-4 mt-2" key={reply.id}>
            <CommentItem comment={reply} onToggleLike={onToggleLike} onReply={onReply} />
          </div>
        ))}
      </div>
    </div>
  );
}

/* ─── Post Item ─────────────────────────────────────────────────────── */
function PostItem({ post, me, onToggleLike, onAddComment, onToggleCommentLike, onReply }) {
  const [commentText, setCommentText] = useState('');
  const [showDropdown, setShowDropdown] = useState(false);
  const dropRef = useRef(null);

  const submitComment = () => {
    if (!commentText.trim()) return;
    onAddComment(post.id, commentText.trim());
    setCommentText('');
  };

  const handleCommentKey = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      submitComment();
    }
  };

  return (
    <div className="_feed_inner_timeline_post_area _b_radious6 _padd_b24 _padd_t24 _mar_b16">
      <div className="_feed_inner_timeline_content _padd_r24 _padd_l24">
        {/* Post Header */}
        <div className="_feed_inner_timeline_post_top">
          <div className="_feed_inner_timeline_post_box">
            <div className="_feed_inner_timeline_post_box_image">
              <img src="/assets/images/post_img.png" alt="" className="_post_img" />
            </div>
            <div className="_feed_inner_timeline_post_box_txt">
              <h4 className="_feed_inner_timeline_post_box_title">
                {post.author?.displayName}
              </h4>
              <p className="_feed_inner_timeline_post_box_para">
                Just now . <a href="#0">{post.visibility}</a>
              </p>
            </div>
          </div>
          <div className="_feed_inner_timeline_post_box_dropdown" ref={dropRef}>
            <div className="_feed_timeline_post_dropdown">
              <button
                className="_feed_timeline_post_dropdown_link"
                onClick={() => setShowDropdown(!showDropdown)}
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="4" height="17" fill="none" viewBox="0 0 4 17">
                  <circle cx="2" cy="2" r="2" fill="#C4C4C4" />
                  <circle cx="2" cy="8" r="2" fill="#C4C4C4" />
                  <circle cx="2" cy="15" r="2" fill="#C4C4C4" />
                </svg>
              </button>
            </div>
            {showDropdown && (
              <div className="_feed_timeline_dropdown _timeline_dropdown" style={{ display: 'block', opacity: 1, visibility: 'visible', transform: 'none' }}>
                <ul className="_feed_timeline_dropdown_list">
                  <li className="_feed_timeline_dropdown_item">
                    <a href="#0" className="_feed_timeline_dropdown_link">
                      <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 18 18">
                          <path stroke="#1890FF" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2"
                            d="M14.25 15.75L9 12l-5.25 3.75v-12a1.5 1.5 0 011.5-1.5h7.5a1.5 1.5 0 011.5 1.5v12z" />
                        </svg>
                      </span>
                      Save Post
                    </a>
                  </li>
                  <li className="_feed_timeline_dropdown_item">
                    <a href="#0" className="_feed_timeline_dropdown_link">
                      <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 18 18">
                          <path stroke="#1890FF" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2"
                            d="M14.25 2.25H3.75a1.5 1.5 0 00-1.5 1.5v10.5a1.5 1.5 0 001.5 1.5h10.5a1.5 1.5 0 001.5-1.5V3.75a1.5 1.5 0 00-1.5-1.5zM6.75 6.75l4.5 4.5M11.25 6.75l-4.5 4.5" />
                        </svg>
                      </span>
                      Hide
                    </a>
                  </li>
                </ul>
              </div>
            )}
          </div>
        </div>

        {/* Post Content */}
        <h4 className="_feed_inner_timeline_post_title">{post.content}</h4>
        {post.imageUrl && (
          <div className="_feed_inner_timeline_image">
            <img src={resolveMediaUrl(post.imageUrl)} alt="Post" className="_time_img" />
          </div>
        )}
      </div>

      {/* Reaction counts */}
      <div className="_feed_inner_timeline_total_reacts _padd_r24 _padd_l24 _mar_b26">
        <div className="_feed_inner_timeline_total_reacts_image">
          <img src="/assets/images/react_img1.png" alt="" className="_react_img1" />
          <img src="/assets/images/react_img2.png" alt="" className="_react_img" />
          <p className="_feed_inner_timeline_total_reacts_para">{post.likesCount || 0}</p>
        </div>
        <div className="_feed_inner_timeline_total_reacts_txt">
          <p className="_feed_inner_timeline_total_reacts_para1">
            <a href="#0"><span>{post.comments?.length || 0}</span> Comment</a>
          </p>
        </div>
      </div>

      {/* Reaction buttons */}
      <div className="_feed_inner_timeline_reaction">
        <button
          className={`_feed_inner_timeline_reaction_emoji _feed_reaction${post.likedByMe ? ' _feed_reaction_active' : ''}`}
          onClick={() => onToggleLike(post.id)}
        >
          <span className="_feed_inner_timeline_reaction_link">
            <span>
              <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 19 19">
                <path fill="#FFCC4D" d="M9.5 19a9.5 9.5 0 100-19 9.5 9.5 0 000 19z" />
                <path fill="#664500" d="M9.5 11.083c-1.912 0-3.181-.222-4.75-.527-.358-.07-1.056 0-1.056 1.055 0 2.111 2.425 4.75 5.806 4.75 3.38 0 5.805-2.639 5.805-4.75 0-1.055-.697-1.125-1.055-1.055-1.57.305-2.838.527-4.75.527z" />
                <path fill="#fff" d="M4.75 11.611s1.583.528 4.75.528 4.75-.528 4.75-.528-1.056 2.111-4.75 2.111-4.75-2.11-4.75-2.11z" />
                <path fill="#664500" d="M6.333 8.972c.729 0 1.32-.827 1.32-1.847s-.591-1.847-1.32-1.847c-.729 0-1.32.827-1.32 1.847s.591 1.847 1.32 1.847zM12.667 8.972c.729 0 1.32-.827 1.32-1.847s-.591-1.847-1.32-1.847c-.729 0-1.32.827-1.32 1.847s.591 1.847 1.32 1.847z" />
              </svg>
              {post.likedByMe ? 'Liked' : 'Like'}
            </span>
          </span>
        </button>

        <button className="_feed_inner_timeline_reaction_comment _feed_reaction">
          <span className="_feed_inner_timeline_reaction_link">
            <span>
              <svg className="_reaction_svg" xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" viewBox="0 0 21 21">
                <path stroke="#000" d="M1 10.5c0-.464 0-.696.009-.893A9 9 0 019.607 1.01C9.804 1 10.036 1 10.5 1v0c.464 0 .696 0 .893.009a9 9 0 018.598 8.598c.009.197.009.429.009.893v6.046c0 1.36 0 2.041-.317 2.535a2 2 0 01-.602.602c-.494.317-1.174.317-2.535.317H10.5c-.464 0-.696 0-.893-.009a9 9 0 01-8.598-8.598C1 11.196 1 10.964 1 10.5v0z" />
                <path stroke="#000" strokeLinecap="round" strokeLinejoin="round" d="M6.938 9.313h7.125M10.5 14.063h3.563" />
              </svg>
              Comment
            </span>
          </span>
        </button>

        <button className="_feed_inner_timeline_reaction_share _feed_reaction">
          <span className="_feed_inner_timeline_reaction_link">
            <span>
              <svg className="_reaction_svg" xmlns="http://www.w3.org/2000/svg" width="24" height="21" fill="none" viewBox="0 0 24 21">
                <path stroke="#000" strokeLinejoin="round" d="M23 10.5L12.917 1v5.429C3.267 6.429 1 13.258 1 20c2.785-3.52 5.248-5.429 11.917-5.429V20L23 10.5z" />
              </svg>
              Share
            </span>
          </span>
        </button>
      </div>

      {/* Comment area */}
      <div className="_feed_inner_timeline_cooment_area">
        <div className="_feed_inner_comment_box">
          <form
            className="_feed_inner_comment_box_form"
            onSubmit={(e) => { e.preventDefault(); submitComment(); }}
          >
            <div className="_feed_inner_comment_box_content">
              <div className="_feed_inner_comment_box_content_image">
                <img src="/assets/images/comment_img.png" alt="" className="_comment_img" />
              </div>
              <div className="_feed_inner_comment_box_content_txt">
                <textarea
                  className="form-control _comment_textarea"
                  placeholder="Write a comment"
                  value={commentText}
                  onChange={(e) => setCommentText(e.target.value)}
                  onKeyDown={handleCommentKey}
                />
              </div>
            </div>
            <div className="_feed_inner_comment_box_icon">
              <button type="submit" className="_feed_inner_comment_box_icon_btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="13" fill="none" viewBox="0 0 14 13">
                  <path fill="#000" fillOpacity=".46" fillRule="evenodd"
                    d="M6.37 7.879l2.438 3.955a.335.335 0 00.34.162c.068-.01.23-.05.289-.247l3.049-10.297a.348.348 0 00-.09-.35.341.341 0 00-.34-.088L1.75 4.03a.34.34 0 00-.247.289.343.343 0 00.16.347L5.666 7.17 9.2 3.597a.5.5 0 01.712.703L6.37 7.88z"
                    clipRule="evenodd" />
                </svg>
              </button>
            </div>
          </form>
        </div>

        {/* Comments list */}
        <div className="_timline_comment_main">
          {post.comments?.map((comment) => (
            <CommentItem
              key={comment.id}
              comment={comment}
              onToggleLike={onToggleCommentLike}
              onReply={onReply}
            />
          ))}
        </div>
      </div>
    </div>
  );
}

/* ─── Feed Page ─────────────────────────────────────────────────────── */
export default function FeedPage() {
  const [me, setMe] = useState(null);
  const [posts, setPosts] = useState([]);
  const [content, setContent] = useState('');
  const [visibility, setVisibility] = useState('public');
  const [image, setImage] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [profileDropOpen, setProfileDropOpen] = useState(false);
  const [notifyDropOpen, setNotifyDropOpen] = useState(false);

  const mapComments = (comments, targetId, applyUpdate) => (
    comments.map((comment) => {
      const nextReplies = comment.replies ? mapComments(comment.replies, targetId, applyUpdate) : [];
      const nextComment = nextReplies !== comment.replies ? { ...comment, replies: nextReplies } : comment;

      if (comment.id !== targetId) {
        return nextComment;
      }

      return applyUpdate(nextComment);
    })
  );

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
    if (!content.trim()) return;

    const formData = new FormData();
    formData.append('content', content.trim());
    formData.append('visibility', visibility);
    if (image) formData.append('image', image);

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
    try {
      const response = await api.post(`/comments/${commentId}/likes/toggle`);
      const likes = response.data?.likes || [];
      const liked = Boolean(response.data?.liked);

      setPosts((prevPosts) => prevPosts.map((post) => ({
        ...post,
        comments: mapComments(post.comments || [], commentId, (comment) => ({
          ...comment,
          likedByMe: liked,
          likes,
          likesCount: likes.length,
        })),
      })));
    } catch (submitError) {
      setError(submitError.response?.data?.message || 'Failed to toggle comment like.');
    }
  };

  const addComment = async (postId, text) => {
    await api.post(`/posts/${postId}/comments`, { content: text });
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
    <div className="_layout _layout_main_wrapper">
      <div className="_main_layout">

        {/* ── Desktop Navigation ── */}
        <nav className="navbar navbar-expand-lg navbar-light _header_nav _padd_t10">
          <div className="container _custom_container">
            <div className="_logo_wrap">
              <a className="navbar-brand" href="/feed">
                <img src="/assets/images/logo.svg" alt="Image" className="_nav_logo" />
              </a>
            </div>
            <button
              className="navbar-toggler bg-light"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#navbarSupportedContent"
            >
              <span className="navbar-toggler-icon"></span>
            </button>
            <div className="collapse navbar-collapse" id="navbarSupportedContent">
              {/* Search */}
              <div className="_header_form ms-auto">
                <form className="_header_form_grp">
                  <svg className="_header_form_svg" xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 17 17">
                    <circle cx="7" cy="7" r="6" stroke="#666" />
                    <path stroke="#666" strokeLinecap="round" d="M16 16l-3-3" />
                  </svg>
                  <input className="form-control me-2 _inpt1" type="search" placeholder="input search text" />
                </form>
              </div>

              {/* Nav icons */}
              <ul className="navbar-nav mb-2 mb-lg-0 _header_nav_list ms-auto _mar_r8">
                {/* Home */}
                <li className="nav-item _header_nav_item">
                  <a className="nav-link _header_nav_link_active _header_nav_link" href="/feed">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="21" fill="none" viewBox="0 0 18 21">
                      <path className="_home_active" stroke="#000" strokeWidth="1.5" strokeOpacity=".6"
                        d="M1 9.924c0-1.552 0-2.328.314-3.01.313-.682.902-1.187 2.08-2.196l1.143-.98C6.667 1.913 7.732 1 9 1c1.268 0 2.333.913 4.463 2.738l1.142.98c1.179 1.01 1.768 1.514 2.081 2.196.314.682.314 1.458.314 3.01v4.846c0 2.155 0 3.233-.67 3.902-.669.67-1.746.67-3.901.67H5.57c-2.155 0-3.232 0-3.902-.67C1 18.002 1 16.925 1 14.77V9.924z" />
                      <path className="_home_active" stroke="#000" strokeOpacity=".6" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5"
                        d="M11.857 19.341v-5.857a1 1 0 00-1-1H7.143a1 1 0 00-1 1v5.857" />
                    </svg>
                  </a>
                </li>
                {/* Friends */}
                <li className="nav-item _header_nav_item">
                  <a className="nav-link _header_nav_link" href="#0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="20" fill="none" viewBox="0 0 26 20">
                      <path fill="#000" fillOpacity=".6" fillRule="evenodd"
                        d="M12.79 12.15h.429c2.268.015 7.45.243 7.45 3.732 0 3.466-5.002 3.692-7.415 3.707h-.894c-2.268-.015-7.452-.243-7.452-3.727 0-3.47 5.184-3.697 7.452-3.711l.297-.001h.132zm0 1.75c-2.792 0-6.12.34-6.12 1.962 0 1.585 3.13 1.955 5.864 1.976l.255.002c2.792 0 6.118-.34 6.118-1.958 0-1.638-3.326-1.982-6.118-1.982zm9.343-2.224c2.846.424 3.444 1.751 3.444 2.79 0 .636-.251 1.794-1.931 2.43a.882.882 0 01-1.137-.506.873.873 0 01.51-1.13c.796-.3.796-.633.796-.793 0-.511-.654-.868-1.944-1.06a.878.878 0 01-.741-.996.886.886 0 011.003-.735zm-17.685.735a.878.878 0 01-.742.997c-1.29.19-1.944.548-1.944 1.059 0 .16 0 .491.798.793a.873.873 0 01-.314 1.693.897.897 0 01-.313-.057C.25 16.259 0 15.1 0 14.466c0-1.037.598-2.366 3.446-2.79.485-.06.929.257 1.002.735zM12.789 0c2.96 0 5.368 2.392 5.368 5.33 0 2.94-2.407 5.331-5.368 5.331h-.031a5.329 5.329 0 01-3.782-1.57 5.253 5.253 0 01-1.553-3.764C7.423 2.392 9.83 0 12.789 0zm0 1.75c-1.987 0-3.604 1.607-3.604 3.58a3.526 3.526 0 001.04 2.527 3.58 3.58 0 002.535 1.054l.03.875v-.875c1.987 0 3.605-1.605 3.605-3.58S14.777 1.75 12.789 1.75z"
                        clipRule="evenodd" />
                    </svg>
                  </a>
                </li>
                {/* Notifications */}
                <li className="nav-item _header_nav_item">
                  <span
                    id="_notify_btn"
                    className="nav-link _header_nav_link _header_notify_btn"
                    onClick={() => setNotifyDropOpen(!notifyDropOpen)}
                    style={{ cursor: 'pointer' }}
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="22" fill="none" viewBox="0 0 20 22">
                      <path fill="#000" fillOpacity=".6" fillRule="evenodd"
                        d="M7.547 19.55c.533.59 1.218.915 1.93.915.714 0 1.403-.324 1.938-.916a.777.777 0 011.09-.056c.318.284.344.77.058 1.084-.832.917-1.927 1.423-3.086 1.423h-.002c-1.155-.001-2.248-.506-3.077-1.424a.762.762 0 01.057-1.083.774.774 0 011.092.057zM9.527 0c4.58 0 7.657 3.543 7.657 6.85 0 1.702.436 2.424.899 3.19.457.754.976 1.612.976 3.233-.36 4.14-4.713 4.478-9.531 4.478-4.818 0-9.172-.337-9.528-4.413-.003-1.686.515-2.544.973-3.299l.161-.27c.398-.679.737-1.417.737-2.918C1.871 3.543 4.948 0 9.528 0z"
                        clipRule="evenodd" />
                    </svg>
                    <span className="_counting">6</span>

                    {/* Notification Dropdown */}
                    <div
                      id="_notify_drop"
                      className="_notification_dropdown"
                      style={notifyDropOpen ? { opacity: 1, visibility: 'visible', transform: 'translateY(0)' } : {}}
                    >
                      <div className="_notifications_content">
                        <h4 className="_notifications_content_title">Notifications</h4>
                        <div className="_notification_box_right">
                          <button type="button" className="_notification_box_right_link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="4" height="17" fill="none" viewBox="0 0 4 17">
                              <circle cx="2" cy="2" r="2" fill="#C4C4C4" />
                              <circle cx="2" cy="8" r="2" fill="#C4C4C4" />
                              <circle cx="2" cy="15" r="2" fill="#C4C4C4" />
                            </svg>
                          </button>
                        </div>
                      </div>
                      <div className="_notifications_drop_box">
                        <div className="_notifications_btn_grp">
                          <button className="_notifications_btn_link">All</button>
                          <button className="_notifications_btn_link1">Unread</button>
                        </div>
                        <div className="_notifications_all">
                          {[1, 2, 3].map((i) => (
                            <div className="_notification_box" key={i}>
                              <div className="_notification_image">
                                <img src="/assets/images/friend-req.png" alt="" className="_notify_img" />
                              </div>
                              <div className="_notification_txt">
                                <p className="_notification_para">
                                  <span className="_notify_txt_link">Steve Jobs</span> posted a link in your timeline.
                                </p>
                                <div className="_nitification_time">
                                  <span>42 minutes ago</span>
                                </div>
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    </div>
                  </span>
                </li>
                {/* Chat */}
                <li className="nav-item _header_nav_item">
                  <a className="nav-link _header_nav_link" href="#0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" fill="none" viewBox="0 0 23 22">
                      <path fill="#000" fillOpacity=".6" fillRule="evenodd"
                        d="M11.43 0c2.96 0 5.743 1.143 7.833 3.22 4.32 4.29 4.32 11.271 0 15.562C17.145 20.886 14.293 22 11.405 22c-1.575 0-3.16-.33-4.643-1.012-.437-.174-.847-.338-1.14-.338-.338.002-.793.158-1.232.308-.9.307-2.022.69-2.852-.131-.826-.822-.445-1.932-.138-2.826.152-.44.307-.895.307-1.239 0-.282-.137-.642-.347-1.161C-.57 11.46.322 6.47 3.596 3.22A11.04 11.04 0 0111.43 0z"
                        clipRule="evenodd" />
                    </svg>
                    <span className="_counting">2</span>
                  </a>
                </li>
              </ul>

              {/* Profile dropdown */}
              <div className="_header_nav_profile">
                <div className="_header_nav_profile_image">
                  <img src="/assets/images/profile.png" alt="Image" className="_nav_profile_img" />
                </div>
                <div
                  className="_header_nav_dropdown"
                  onClick={() => setProfileDropOpen(!profileDropOpen)}
                >
                  <p className="_header_nav_para">{me?.displayName || 'User'}</p>
                  <button className="_header_nav_dropdown_btn _dropdown_toggle" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" fill="none" viewBox="0 0 10 6">
                      <path fill="#112032" d="M5 5l.354.354L5 5.707l-.354-.353L5 5zm4.354-3.646l-4 4-.708-.708 4-4 .708.708zm-4.708 4l-4-4 .708-.708 4 4-.708.708z" />
                    </svg>
                  </button>
                </div>
                {profileDropOpen && (
                  <div className="_nav_profile_dropdown _profile_dropdown" style={{ display: 'block', opacity: 1, visibility: 'visible' }}>
                    <div className="_nav_profile_dropdown_info">
                      <div className="_nav_profile_dropdown_image">
                        <img src="/assets/images/profile.png" alt="Image" className="_nav_drop_img" />
                      </div>
                      <div className="_nav_profile_dropdown_info_txt">
                        <h4 className="_nav_dropdown_title">{me?.displayName || 'User'}</h4>
                        <a href="#0" className="_nav_drop_profile">View Profile</a>
                      </div>
                    </div>
                    <hr />
                    <ul className="_nav_dropdown_list">
                      <li className="_nav_dropdown_list_item">
                        <button
                          className="_nav_dropdown_link"
                          style={{ background: 'none', border: 'none', cursor: 'pointer', padding: 0, width: '100%', textAlign: 'left' }}
                          onClick={logout}
                        >
                          <div className="_nav_drop_info">
                            <span>
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="19" fill="none" viewBox="0 0 18 19">
                                <path fill="#377DFF" d="M13.5 12.75l3-3.75-3-3.75M16.5 9H6.75M9 16.5H3.75A1.5 1.5 0 012.25 15V4a1.5 1.5 0 011.5-1.5H9" />
                              </svg>
                            </span>
                            Logout
                          </div>
                        </button>
                      </li>
                    </ul>
                  </div>
                )}
              </div>
            </div>
          </div>
        </nav>
        {/* ── Desktop Navigation End ── */}

        {/* ── Mobile Bottom Navigation ── */}
        <div className="_mobile_navigation_bottom_wrapper">
          <div className="_mobile_navigation_bottom_wrap">
            <div className="container">
              <div className="row">
                <div className="col-xl-12 col-lg-12 col-md-12">
                  <ul className="_mobile_navigation_bottom_list">
                    <li className="_mobile_navigation_bottom_item">
                      <a href="/feed" className="_mobile_navigation_bottom_link _mobile_navigation_bottom_link_active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="27" fill="none" viewBox="0 0 24 27">
                          <path className="_mobile_svg" fill="#000" fillOpacity=".6" stroke="#666666" strokeWidth="1.5"
                            d="M1 13.042c0-2.094 0-3.141.431-4.061.432-.92 1.242-1.602 2.862-2.965l1.571-1.321C8.792 2.232 10.256 1 12 1c1.744 0 3.208 1.232 6.136 3.695l1.572 1.321c1.62 1.363 2.43 2.044 2.86 2.965.432.92.432 1.967.432 4.06v6.54c0 2.908 0 4.362-.92 5.265-.921.904-2.403.904-5.366.904H7.286c-2.963 0-4.445 0-5.365-.904C1 23.944 1 22.49 1 19.581v-6.54z" />
                          <path fill="#fff" stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                            d="M9.07 18.497h5.857v7.253H9.07v-7.253z" />
                        </svg>
                      </a>
                    </li>
                    <li className="_mobile_navigation_bottom_item">
                      <a href="#0" className="_mobile_navigation_bottom_link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="27" fill="none" viewBox="0 0 25 27">
                          <path className="_dark_svg" fill="#000" fillOpacity=".6" fillRule="evenodd"
                            d="M10.17 23.46c.671.709 1.534 1.098 2.43 1.098.9 0 1.767-.39 2.44-1.099a.885.885 0 011.374.068.885.885 0 01.072 1.298c-1.049 1.101-2.428 1.708-3.886 1.708h-.003c-1.454-.001-2.831-.608-3.875-1.71a.885.885 0 01.072-1.298 1.01 1.01 0 011.374.068z"
                            clipRule="evenodd" />
                        </svg>
                        <span className="_counting">6</span>
                      </a>
                    </li>
                    <li className="_mobile_navigation_bottom_item">
                      <a href="#0" className="_mobile_navigation_bottom_link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                          <path className="_dark_svg" fill="#000" fillOpacity=".6" fillRule="evenodd"
                            d="M12.002 0c3.208 0 6.223 1.239 8.487 3.489 4.681 4.648 4.681 12.211 0 16.86-2.294 2.28-5.384 3.486-8.514 3.486-1.706 0-3.423-.358-5.03-1.097-.474-.188-.917-.366-1.235-.366-.366.003-.859.171-1.335.334-.976.333-2.19.748-3.09-.142-.895-.89-.482-2.093-.149-3.061.164-.477.333-.97.333-1.342 0-.306-.149-.697-.376-1.259C-1 12.417-.032 7.011 3.516 3.49A11.96 11.96 0 0112.002 0z"
                            clipRule="evenodd" />
                        </svg>
                        <span className="_counting">2</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
        {/* ── Mobile Bottom Navigation End ── */}

        {/* ── Main 3-column Layout ── */}
        <div className="container _custom_container">
          <div className="_layout_inner_wrap">
            <div className="row">

              {/* ── Left Sidebar ── */}
              <div className="col-xl-3 col-lg-3 col-md-12 col-sm-12">
                <div className="_layout_left_sidebar_wrap">
                  {/* Explore */}
                  <div className="_layout_left_sidebar_inner">
                    <div className="_left_inner_area_explore _padd_t24 _padd_b6 _padd_r24 _padd_l24 _b_radious6 _feed_inner_area">
                      <h4 className="_left_inner_area_explore_title _title5 _mar_b24">Explore</h4>
                      <ul className="_left_inner_area_explore_list">
                        <li className="_left_inner_area_explore_item _explore_item">
                          <a href="#0" className="_left_inner_area_explore_link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 20 20">
                              <path fill="#666" d="M10 0c5.523 0 10 4.477 10 10s-4.477 10-10 10S0 15.523 0 10 4.477 0 10 0zm0 1.395a8.605 8.605 0 100 17.21 8.605 8.605 0 000-17.21zm-1.233 4.65l.104.01c.188.028.443.113.668.203 1.026.398 3.033 1.746 3.8 2.563l.223.239.08.092a1.16 1.16 0 01.025 1.405c-.04.053-.086.105-.19.215l-.269.28c-.812.794-2.57 1.971-3.569 2.391-.277.117-.675.25-.865.253a1.167 1.167 0 01-1.07-.629c-.053-.104-.12-.353-.171-.586l-.051-.262c-.093-.57-.143-1.437-.142-2.347l.001-.288c.01-.858.063-1.64.157-2.147.037-.207.12-.563.167-.678.104-.25.291-.45.523-.575a1.15 1.15 0 01.58-.14z" />
                            </svg>
                            Learning
                          </a>
                          <span className="_left_inner_area_explore_link_txt">New</span>
                        </li>
                        <li className="_left_inner_area_explore_item">
                          <a href="#0" className="_left_inner_area_explore_link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="24" fill="none" viewBox="0 0 22 24">
                              <path fill="#666" d="M14.96 2c3.101 0 5.159 2.417 5.159 5.893v8.214c0 3.476-2.058 5.893-5.16 5.893H6.989c-3.101 0-5.159-2.417-5.159-5.893V7.893C1.83 4.42 3.892 2 6.988 2h7.972z" />
                            </svg>
                            Insights
                          </a>
                        </li>
                        <li className="_left_inner_area_explore_item">
                          <a href="#0" className="_left_inner_area_explore_link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="24" fill="none" viewBox="0 0 22 24">
                              <path fill="#666" d="M9.032 14.456l.297.002c4.404.041 6.907 1.03 6.907 3.678 0 2.586-2.383 3.573-6.615 3.654l-.589.005c-4.588 0-7.203-.972-7.203-3.68 0-2.704 2.604-3.659 7.203-3.659z" />
                            </svg>
                            Find friends
                          </a>
                        </li>
                        <li className="_left_inner_area_explore_item">
                          <a href="#0" className="_left_inner_area_explore_link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="24" fill="none" viewBox="0 0 22 24">
                              <path fill="#666" d="M13.704 2c2.8 0 4.585 1.435 4.585 4.258V20.33c0 .443-.157.867-.436 1.18-.279.313-.658.489-1.063.489a1.456 1.456 0 01-.708-.203l-5.132-3.134-5.112 3.14c-.615.36-1.361.194-1.829-.405l-.09-.126-.085-.155a1.913 1.913 0 01-.176-.786V6.434C3.658 3.5 5.404 2 8.243 2h5.46z" />
                            </svg>
                            Bookmarks
                          </a>
                        </li>
                        <li className="_left_inner_area_explore_item">
                          <a href="#0" className="_left_inner_area_explore_link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#666" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                              <circle cx="9" cy="7" r="4" />
                              <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            Group
                          </a>
                        </li>
                        <li className="_left_inner_area_explore_item _explore_item">
                          <a href="#0" className="_left_inner_area_explore_link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="24" fill="none" viewBox="0 0 22 24">
                              <path fill="#666" d="M7.625 2c.315-.015.642.306.645.69.003.309.234.558.515.558h.928c1.317 0 2.402 1.169 2.419 2.616v.24h2.604c2.911-.026 5.255 2.337 5.377 5.414.005.12.006.245.004.368v4.31c.062 3.108-2.21 5.704-5.064 5.773-.117.003-.228 0-.34-.005a199.325 199.325 0 01-7.516 0c-2.816.132-5.238-2.292-5.363-5.411a6.262 6.262 0 01-.004-.371V11.87c-.03-1.497.48-2.931 1.438-4.024.956-1.094 2.245-1.714 3.629-1.746z" />
                            </svg>
                            Gaming
                          </a>
                          <span className="_left_inner_area_explore_link_txt">New</span>
                        </li>
                        <li className="_left_inner_area_explore_item">
                          <a href="#0" className="_left_inner_area_explore_link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="24" fill="none" viewBox="0 0 22 24">
                              <path fill="#666" d="M22 9v-1a2 2 0 0 0-2-2h-1V4a2 2 0 0 0-2-2H3a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h1v1a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V11a2 2 0 0 0-2-2z" />
                            </svg>
                            Save post
                          </a>
                        </li>
                      </ul>
                    </div>
                  </div>

                  {/* Suggested People */}
                  <div className="_layout_left_sidebar_inner">
                    <div className="_left_inner_area_suggest _padd_t24 _padd_b6 _padd_r24 _padd_l24 _b_radious6 _feed_inner_area">
                      <div className="_left_inner_area_suggest_content _mar_b24">
                        <h4 className="_left_inner_area_suggest_content_title _title5">Suggested People</h4>
                        <span className="_left_inner_area_suggest_content_txt">
                          <a className="_left_inner_area_suggest_content_txt_link" href="#0">See All</a>
                        </span>
                      </div>
                      {[
                        { img: 'people1.png', name: 'Steve Jobs', role: 'CEO of Apple' },
                        { img: 'people2.png', name: 'Ryan Roslansky', role: 'CEO of Linkedin' },
                        { img: 'people3.png', name: 'Dylan Field', role: 'CEO of Figma' },
                      ].map((person) => (
                        <div className="_left_inner_area_suggest_info" key={person.name}>
                          <div className="_left_inner_area_suggest_info_box">
                            <div className="_left_inner_area_suggest_info_image">
                              <a href="#0">
                                <img src={`/assets/images/${person.img}`} alt="Image" className="_info_img1" />
                              </a>
                            </div>
                            <div className="_left_inner_area_suggest_info_txt">
                              <a href="#0">
                                <h4 className="_left_inner_area_suggest_info_title">{person.name}</h4>
                              </a>
                              <p className="_left_inner_area_suggest_info_para">{person.role}</p>
                            </div>
                          </div>
                          <div className="_left_inner_area_suggest_info_link">
                            <a href="#0" className="_info_link">Connect</a>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Events */}
                  <div className="_layout_left_sidebar_inner">
                    <div className="_left_inner_area_event _padd_t24 _padd_b6 _padd_r24 _padd_l24 _b_radious6 _feed_inner_area">
                      <div className="_left_inner_event_content">
                        <h4 className="_left_inner_event_title _title5">Events</h4>
                        <a href="#0" className="_left_inner_event_link">See all</a>
                      </div>
                      {[1, 2].map((i) => (
                        <a className="_left_inner_event_card_link" href="#0" key={i}>
                          <div className="_left_inner_event_card">
                            <div className="_left_inner_event_card_iamge">
                              <img src="/assets/images/feed_event1.png" alt="Image" className="_card_img" />
                            </div>
                            <div className="_left_inner_event_card_content">
                              <div className="_left_inner_card_date">
                                <p className="_left_inner_card_date_para">10</p>
                                <p className="_left_inner_card_date_para1">Jul</p>
                              </div>
                              <div className="_left_inner_card_txt">
                                <h4 className="_left_inner_event_card_title">No more terrorism no more cry</h4>
                              </div>
                            </div>
                            <hr className="_underline" />
                            <div className="_left_inner_event_bottom">
                              <p className="_left_iner_event_bottom">17 People Going</p>
                              <a href="#0" className="_left_iner_event_bottom_link">Going</a>
                            </div>
                          </div>
                        </a>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
              {/* ── Left Sidebar End ── */}

              {/* ── Middle Column ── */}
              <div className="col-xl-6 col-lg-6 col-md-12 col-sm-12">
                <div className="_layout_middle_wrap">
                  <div className="_layout_middle_inner">

                    {/* Story Cards */}
                    <div className="_feed_inner_ppl_card _mar_b16">
                      <div className="_feed_inner_story_arrow">
                        <button type="button" className="_feed_inner_story_arrow_btn">
                          <svg xmlns="http://www.w3.org/2000/svg" width="9" height="8" fill="none" viewBox="0 0 9 8">
                            <path fill="#fff" d="M8 4l.366-.341.318.341-.318.341L8 4zm-7 .5a.5.5 0 010-1v1zM5.566.659l2.8 3-.732.682-2.8-3L5.566.66zm2.8 3.682l-2.8 3-.732-.682 2.8-3 .732.682zM8 4.5H1v-1h7v1z" />
                          </svg>
                        </button>
                      </div>
                      <div className="row">
                        <div className="col-xl-3 col-lg-3 col-md-4 col-sm-4 col">
                          <div className="_feed_inner_profile_story _b_radious6">
                            <div className="_feed_inner_profile_story_image">
                              <img src="/assets/images/card_ppl1.png" alt="Image" className="_profile_story_img" />
                              <div className="_feed_inner_story_txt">
                                <div className="_feed_inner_story_btn">
                                  <button className="_feed_inner_story_btn_link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 10 10">
                                      <path stroke="#fff" strokeLinecap="round" d="M.5 4.884h9M4.884 9.5v-9" />
                                    </svg>
                                  </button>
                                </div>
                                <p className="_feed_inner_story_para">Add Story</p>
                              </div>
                            </div>
                          </div>
                        </div>
                        {[
                          { img: 'card_ppl2.png', name: 'Steve' },
                          { img: 'card_ppl3.png', name: 'Ryan' },
                          { img: 'card_ppl4.png', name: 'Dylan' },
                        ].map((story) => (
                          <div className="col-xl-3 col-lg-3 col-md-4 col-sm-4 col" key={story.name}>
                            <div className="_feed_inner_public_story _b_radious6">
                              <div className="_feed_inner_public_story_image">
                                <img src={`/assets/images/${story.img}`} alt="Image" className="_public_story_img" />
                                <div className="_feed_inner_public_mini">
                                  <img src="/assets/images/mini_pic.png" alt="" className="_public_mini_img" />
                                </div>
                              </div>
                              <div className="_feed_inner_pulic_story_txt">
                                <p className="_feed_inner_pulic_story_para">{story.name}</p>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Post Creation */}
                    <div className="_feed_inner_text_area _b_radious6 _padd_b24 _padd_t24 _padd_r24 _padd_l24 _mar_b16">
                      <div className="_feed_inner_text_area_box">
                        <div className="_feed_inner_text_area_box_image">
                          <img src="/assets/images/txt_img.png" alt="Image" className="_txt_img" />
                        </div>
                        <div className="form-floating _feed_inner_text_area_box_form">
                          <textarea
                            className="form-control _textarea"
                            placeholder="Write something ..."
                            id="floatingTextarea"
                            value={content}
                            onChange={(e) => setContent(e.target.value)}
                          />
                          <label className="_feed_textarea_label" htmlFor="floatingTextarea">
                            Write something ...
                          </label>
                        </div>
                      </div>

                      {error && <div className="alert alert-danger mt-2">{error}</div>}

                      {/* Desktop bottom bar */}
                      <div className="_feed_inner_text_area_bottom">
                        <div className="_feed_inner_text_area_item">
                          <div className="_feed_inner_text_area_bottom_photo _feed_common">
                            <label className="_feed_inner_text_area_bottom_photo_link" style={{ cursor: 'pointer' }}>
                              <span className="_feed_inner_text_area_bottom_photo_iamge _mar_img">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 20 20">
                                  <path fill="#666" d="M13.916 0c3.109 0 5.18 2.429 5.18 5.914v8.17c0 3.486-2.072 5.916-5.18 5.916H5.999C2.89 20 .827 17.572.827 14.085v-8.17C.827 2.43 2.897 0 6 0h7.917z" />
                                </svg>
                              </span>
                              Photo
                              <input
                                type="file"
                                accept="image/*"
                                style={{ display: 'none' }}
                                onChange={(e) => setImage(e.target.files?.[0] || null)}
                              />
                            </label>
                          </div>
                          <div className="_feed_inner_text_area_bottom_video _feed_common">
                            <button type="button" className="_feed_inner_text_area_bottom_photo_link">
                              <span className="_feed_inner_text_area_bottom_photo_iamge _mar_img">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="24" fill="none" viewBox="0 0 22 24">
                                  <path fill="#666" d="M11.485 4.5c2.213 0 3.753 1.534 3.917 3.784l2.418-1.082c1.047-.468 2.188.327 2.271 1.533l.005.141v6.64c0 1.237-1.103 2.093-2.155 1.72l-.121-.047-2.418-1.083c-.164 2.25-1.708 3.785-3.917 3.785H5.76c-2.343 0-3.932-1.72-3.932-4.188V8.688c0-2.47 1.589-4.188 3.932-4.188h5.726z" />
                                </svg>
                              </span>
                              Video
                            </button>
                          </div>
                          <div className="_feed_inner_text_area_bottom_event _feed_common">
                            <button type="button" className="_feed_inner_text_area_bottom_photo_link">
                              <span className="_feed_inner_text_area_bottom_photo_iamge _mar_img">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="24" fill="none" viewBox="0 0 22 24">
                                  <path fill="#666" d="M14.371 2c.32 0 .585.262.627.603l.005.095v.788c2.598.195 4.188 2.033 4.18 5v8.488c0 3.145-1.786 5.026-4.656 5.026H7.395C4.53 22 2.74 20.087 2.74 16.904V8.486c0-2.966 1.596-4.804 4.187-5v-.788c0-.386.283-.698.633-.698z" />
                                </svg>
                              </span>
                              Event
                            </button>
                          </div>
                          <div className="_feed_inner_text_area_bottom_article _feed_common">
                            <button type="button" className="_feed_inner_text_area_bottom_photo_link">
                              <span className="_feed_inner_text_area_bottom_photo_iamge _mar_img">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="20" fill="none" viewBox="0 0 18 20">
                                  <path fill="#666" d="M12.49 0c2.92 0 4.665 1.92 4.693 5.132v9.659c0 3.257-1.75 5.209-4.693 5.209H5.434c-.377 0-.734-.032-1.07-.095l-.2-.041C2 19.371.74 17.555.74 14.791V5.209c0-.334.019-.654.055-.96C1.114 1.564 2.799 0 5.434 0h7.056z" />
                                </svg>
                              </span>
                              Article
                            </button>
                          </div>
                        </div>
                        <div className="_feed_inner_text_area_btn">
                          <button
                            type="button"
                            className="_feed_inner_text_area_btn_link"
                            onClick={onCreatePost}
                            disabled={loading}
                          >
                            <svg className="_mar_img" xmlns="http://www.w3.org/2000/svg" width="14" height="13" fill="none" viewBox="0 0 14 13">
                              <path fill="#fff" fillRule="evenodd"
                                d="M6.37 7.879l2.438 3.955a.335.335 0 00.34.162c.068-.01.23-.05.289-.247l3.049-10.297a.348.348 0 00-.09-.35.341.341 0 00-.34-.088L1.75 4.03a.34.34 0 00-.247.289.343.343 0 00.16.347L5.666 7.17 9.2 3.597a.5.5 0 01.712.703L6.37 7.88z"
                                clipRule="evenodd" />
                            </svg>
                            <span>{loading ? 'Posting...' : 'Post'}</span>
                          </button>
                        </div>
                      </div>
                    </div>

                    {/* Posts */}
                    {posts.map((post) => (
                      <PostItem
                        key={post.id}
                        post={post}
                        me={me}
                        onToggleLike={togglePostLike}
                        onAddComment={addComment}
                        onToggleCommentLike={toggleCommentLike}
                        onReply={addReply}
                      />
                    ))}
                  </div>
                </div>
              </div>
              {/* ── Middle Column End ── */}

              {/* ── Right Sidebar ── */}
              <div className="col-xl-3 col-lg-3 col-md-12 col-sm-12">
                <div className="_layout_right_sidebar_wrap">
                  {/* You Might Like */}
                  <div className="_layout_right_sidebar_inner">
                    <div className="_right_inner_area_info _padd_t24 _padd_b24 _padd_r24 _padd_l24 _b_radious6 _feed_inner_area">
                      <div className="_right_inner_area_info_content _mar_b24">
                        <h4 className="_right_inner_area_info_content_title _title5">You Might Like</h4>
                        <span className="_right_inner_area_info_content_txt">
                          <a className="_right_inner_area_info_content_txt_link" href="#0">See All</a>
                        </span>
                      </div>
                      <hr className="_underline" />
                      <div className="_right_inner_area_info_ppl">
                        <div className="_right_inner_area_info_box">
                          <div className="_right_inner_area_info_box_image">
                            <a href="#0">
                              <img src="/assets/images/Avatar.png" alt="Image" className="_ppl_img" />
                            </a>
                          </div>
                          <div className="_right_inner_area_info_box_txt">
                            <a href="#0">
                              <h4 className="_right_inner_area_info_box_title">Radovan SkillArena</h4>
                            </a>
                            <p className="_right_inner_area_info_box_para">Founder &amp; CEO at Trophy</p>
                          </div>
                        </div>
                        <div className="_right_info_btn_grp">
                          <button type="button" className="_right_info_btn_link">Ignore</button>
                          <button type="button" className="_right_info_btn_link _right_info_btn_link_active">Follow</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Your Friends */}
                  <div className="_layout_right_sidebar_inner">
                    <div className="_feed_right_inner_area_card _padd_t24 _padd_b6 _padd_r24 _padd_l24 _b_radious6 _feed_inner_area">
                      <div className="_feed_top_fixed">
                        <div className="_feed_right_inner_area_card_content _mar_b24">
                          <h4 className="_feed_right_inner_area_card_content_title _title5">Your Friends</h4>
                          <span className="_feed_right_inner_area_card_content_txt">
                            <a className="_feed_right_inner_area_card_content_txt_link" href="#0">See All</a>
                          </span>
                        </div>
                        <form className="_feed_right_inner_area_card_form">
                          <svg className="_feed_right_inner_area_card_form_svg" xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 17 17">
                            <circle cx="7" cy="7" r="6" stroke="#666" />
                            <path stroke="#666" strokeLinecap="round" d="M16 16l-3-3" />
                          </svg>
                          <input
                            className="form-control me-2 _feed_right_inner_area_card_form_inpt"
                            type="search"
                            placeholder="input search text"
                          />
                        </form>
                      </div>
                      <div className="_feed_bottom_fixed">
                        {[
                          { img: 'people1.png', name: 'Steve Jobs', role: 'CEO of Apple', online: false, time: '5 minute ago' },
                          { img: 'people2.png', name: 'Ryan Roslansky', role: 'CEO of Linkedin', online: true, time: null },
                          { img: 'people3.png', name: 'Dylan Field', role: 'CEO of Figma', online: true, time: null },
                        ].map((friend) => (
                          <div
                            key={friend.name}
                            className={`_feed_right_inner_area_card_ppl${!friend.online ? ' _feed_right_inner_area_card_ppl_inactive' : ''}`}
                          >
                            <div className="_feed_right_inner_area_card_ppl_box">
                              <div className="_feed_right_inner_area_card_ppl_image">
                                <a href="#0">
                                  <img src={`/assets/images/${friend.img}`} alt="" className="_box_ppl_img" />
                                </a>
                              </div>
                              <div className="_feed_right_inner_area_card_ppl_txt">
                                <a href="#0">
                                  <h4 className="_feed_right_inner_area_card_ppl_title">{friend.name}</h4>
                                </a>
                                <p className="_feed_right_inner_area_card_ppl_para">{friend.role}</p>
                              </div>
                            </div>
                            <div className="_feed_right_inner_area_card_ppl_side">
                              {friend.online ? (
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14">
                                  <rect width="12" height="12" x="1" y="1" fill="#0ACF83" stroke="#fff" strokeWidth="2" rx="6" />
                                </svg>
                              ) : (
                                <span>{friend.time}</span>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* ── Right Sidebar End ── */}

            </div>
          </div>
        </div>
        {/* ── Main Layout End ── */}

      </div>
    </div>
  );
}
