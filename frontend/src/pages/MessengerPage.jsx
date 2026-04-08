import { useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { API_BASE_URL, api, resolveMediaUrl } from '../api';

const formatTime = (value) => {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const getInitials = (user) => {
  const name = (user?.displayName || user?.email || '').trim();
  if (!name) return '?';
  const parts = name.split(/\s+/).filter(Boolean);
  return parts.slice(0, 2).map((part) => part[0].toUpperCase()).join('') || '?';
};

const getUserAvatarUrl = (user) => {
  const avatarUrl = user?.avatarUrl
    || user?.profile?.avatarUrl
    || user?.avatar?.url
    || user?.profileImageUrl
    || user?.imageUrl
    || user?.photoUrl
    || '';

  if (typeof avatarUrl !== 'string') return '';
  return avatarUrl.trim();
};

export default function MessengerPage() {
  const [conversations, setConversations] = useState([]);
  const [activeConversation, setActiveConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [conversationQueryInput, setConversationQueryInput] = useState('');
  const [conversationQuery, setConversationQuery] = useState('');
  const [includeArchived, setIncludeArchived] = useState(false);
  const [conversationsPagination, setConversationsPagination] = useState({ hasMore: false, nextOffset: 0 });
  const [messagesPagination, setMessagesPagination] = useState({ hasMore: false, nextBefore: null });
  const [recipientId, setRecipientId] = useState('');
  const [content, setContent] = useState('');
  const [attachment, setAttachment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState('');
  const [streamMode, setStreamMode] = useState('idle');
  const [receiptViewer, setReceiptViewer] = useState(null);

  const activeConversationId = activeConversation?.id || null;
  const sinceRef = useRef('');

  const activeTitle = useMemo(() => {
    if (!activeConversation) return 'Select conversation';
    const names = (activeConversation.participants || []).map((user) => user.displayName).filter(Boolean);
    return names.length ? names.join(', ') : 'Conversation';
  }, [activeConversation]);

  const loadConversations = async ({ reset = false } = {}) => {
    const offset = reset ? 0 : conversationsPagination.nextOffset || 0;
    const response = await api.get('/v1/messenger/conversations', {
      params: {
        q: conversationQuery,
        offset,
        limit: 25,
        includeArchived,
      },
    });
    const list = response.data?.conversations || [];
    const pagination = response.data?.pagination || {};

    setConversations((prev) => {
      if (reset) {
        return list;
      }

      const map = new Map(prev.map((item) => [item.id, item]));
      list.forEach((item) => map.set(item.id, item));
      return Array.from(map.values());
    });
    setConversationsPagination({
      hasMore: Boolean(pagination.hasMore),
      nextOffset: Number.isFinite(pagination.nextOffset) ? pagination.nextOffset : offset + list.length,
    });

    if (!activeConversationId && list.length > 0 && reset) {
      setActiveConversation(list[0]);
    }

    if (activeConversationId) {
      const match = list.find((item) => item.id === activeConversationId);
      if (match) {
        setActiveConversation(match);
      }
    }
  };

  const loadMessages = async (conversationId, { before = null, prepend = false } = {}) => {
    if (!conversationId) {
      setMessages([]);
      setMessagesPagination({ hasMore: false, nextBefore: null });
      return;
    }

    const response = await api.get(`/v1/messenger/conversations/${conversationId}/messages`, {
      params: {
        limit: 80,
        ...(before ? { before } : {}),
      },
    });

    const batch = response.data?.messages || [];
    const pagination = response.data?.pagination || {};

    setMessages((prev) => {
      if (!prepend) {
        return batch;
      }

      const map = new Map([...batch, ...prev].map((item) => [item.id, item]));
      return Array.from(map.values()).sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
    });
    setMessagesPagination({
      hasMore: Boolean(pagination.hasMore),
      nextBefore: pagination.nextBefore || null,
    });

    await api.post(`/v1/messenger/conversations/${conversationId}/read`);
    await loadConversations({ reset: true });
  };

  useEffect(() => {
    let mounted = true;

    Promise.all([loadConversations({ reset: true })])
      .catch((loadError) => {
        if (!mounted) return;
        setError(loadError.response?.data?.message || 'Failed to load messenger.');
      })
      .finally(() => {
        if (!mounted) return;
        setLoading(false);
      });

    return () => {
      mounted = false;
    };
  }, [conversationQuery, includeArchived]);

  useEffect(() => {
    if (!activeConversationId) {
      setMessages([]);
      return;
    }

    loadMessages(activeConversationId).catch((loadError) => {
      setError(loadError.response?.data?.message || 'Failed to load conversation messages.');
    });
  }, [activeConversationId]);

  useEffect(() => {
    if (!activeConversationId) {
      setStreamMode('idle');
      return undefined;
    }

    if (typeof window === 'undefined' || typeof window.EventSource === 'undefined') {
      setStreamMode('polling');
      return undefined;
    }

    let source = null;

    const initStream = async () => {
      try {
        const tokenResponse = await api.post('/v1/messenger/stream-token');
        const streamToken = tokenResponse.data?.token;
        if (!streamToken) {
          setStreamMode('polling');
          return;
        }

        setStreamMode('streaming');
        const streamUrl = `${API_BASE_URL}/api/v1/messenger/stream?streamToken=${encodeURIComponent(streamToken)}&conversationId=${encodeURIComponent(activeConversationId)}&since=${encodeURIComponent(sinceRef.current)}`;
        source = new EventSource(streamUrl);
        source.addEventListener('updates', handleUpdates);
        source.onerror = () => {
          setStreamMode('polling');
          source?.close();
        };
      } catch {
        setStreamMode('polling');
      }
    };

    const handleUpdates = async (event) => {
      try {
        const payload = JSON.parse(event.data || '{}');
        const updates = payload.updates || [];
        if (payload.serverTime) {
          sinceRef.current = payload.serverTime;
        }

        if (updates.length === 0) {
          return;
        }

        setMessages((prev) => {
          const map = new Map(prev.map((item) => [item.id, item]));
          updates.forEach((item) => map.set(item.id, item));
          return Array.from(map.values()).sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
        });
        await loadConversations({ reset: true });
      } catch {
        // Ignore malformed stream events.
      }
    };

    initStream();

    return () => {
      source?.removeEventListener('updates', handleUpdates);
      source?.close();
    };
  }, [activeConversationId]);

  const updateConversationPreference = async (action, payload) => {
    if (!activeConversationId) return;

    try {
      const response = await api.post(`/v1/messenger/conversations/${activeConversationId}/${action}`, payload);
      const updated = response.data?.conversation;
      if (!updated) return;

      setActiveConversation(updated);
      setConversations((prev) => prev.map((item) => (item.id === updated.id ? updated : item)));
    } catch (prefError) {
      setError(prefError.response?.data?.message || 'Failed to update conversation preference.');
    }
  };

  useEffect(() => {
    if (!activeConversationId || streamMode !== 'polling') return undefined;

    const timer = setInterval(async () => {
      try {
        const response = await api.get('/v1/messenger/updates', {
          params: sinceRef.current ? { since: sinceRef.current } : {},
        });

        const updates = response.data?.updates || [];
        const serverTime = response.data?.serverTime;
        if (serverTime) {
          sinceRef.current = serverTime;
        }

        const relevant = updates.filter((message) => message.conversationId === activeConversationId);
        if (relevant.length > 0) {
          setMessages((prev) => {
            const map = new Map(prev.map((item) => [item.id, item]));
            relevant.forEach((item) => map.set(item.id, item));
            return Array.from(map.values()).sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
          });
          await loadConversations({ reset: true });
        }
      } catch {
        // Keep silent for periodic polling failures.
      }
    }, 2200);

    return () => clearInterval(timer);
  }, [activeConversationId, streamMode]);

  const sendMessage = async (event) => {
    event.preventDefault();

    if (!activeConversationId && !recipientId.trim()) {
      setError('Recipient user id is required to start a conversation.');
      return;
    }

    if (!content.trim() && !attachment) {
      setError('Type a message or attach a file.');
      return;
    }

    setSending(true);
    setError('');

    try {
      const formData = new FormData();
      if (activeConversationId) {
        formData.append('conversationId', activeConversationId);
      } else {
        formData.append('recipientId', recipientId.trim());
      }
      if (content.trim()) {
        formData.append('content', content.trim());
      }
      if (attachment) {
        formData.append('attachment', attachment);
      }

      const response = await api.post('/v1/messenger/messages', formData);
      const createdMessage = response.data?.message;
      const conversation = response.data?.conversation;

      if (conversation) {
        setActiveConversation(conversation);
      }
      if (createdMessage) {
        setMessages((prev) => [...prev, createdMessage]);
      }

      setContent('');
      setAttachment(null);
      await loadConversations({ reset: true });
      if (conversation?.id) {
        await loadMessages(conversation.id);
      }
    } catch (sendError) {
      setError(sendError.response?.data?.message || 'Failed to send message.');
    } finally {
      setSending(false);
    }
  };

  const openReceiptViewer = (message) => {
    if (!message) return;
    setReceiptViewer({ message });
  };

  const buildReceiptGroups = (message) => {
    const deliveredBy = Array.isArray(message?.deliveredBy) ? message.deliveredBy : [];
    const readBy = Array.isArray(message?.readBy) ? message.readBy : [];

    const recipients = Array.isArray(activeConversation?.participants)
      ? activeConversation.participants
      : [];

    const deliveredIds = new Set(deliveredBy.map((user) => user?.id).filter(Boolean));
    const readIds = new Set(readBy.map((user) => user?.id).filter(Boolean));

    const deliveredOnly = deliveredBy.filter((user) => user?.id && !readIds.has(user.id));
    const pending = recipients.filter((user) => user?.id && !deliveredIds.has(user.id));

    return {
      read: readBy,
      deliveredOnly,
      pending,
    };
  };

  return (
    <div className="messenger_page">
      <header className="phase1_page_header">
        <h2>Messenger</h2>
        <p>Phase 2 communication: real-time stream with polling fallback, attachments, and read states.</p>
        <Link to="/feed" className="phase1_back_link">Back to Feed</Link>
        <div className="messenger_status_row">
          <span className={`messenger_mode_badge messenger_mode_badge_${streamMode}`}>{streamMode}</span>
        </div>
      </header>

      {error && <div className="phase1_notice phase1_notice_error">{error}</div>}

      <div className="messenger_layout">
        <aside className="messenger_sidebar">
          <h3>Conversations</h3>
          <div className="messenger_sidebar_search">
            <input
              type="search"
              className="messenger_input"
              placeholder="Search conversations"
              value={conversationQueryInput}
              onChange={(event) => setConversationQueryInput(event.target.value)}
            />
            <button type="button" className="phase1_btn" onClick={() => setConversationQuery(conversationQueryInput.trim())}>Search</button>
            <button type="button" className="phase1_btn" onClick={() => setIncludeArchived((prev) => !prev)}>
              {includeArchived ? 'Hide Archived' : 'Show Archived'}
            </button>
          </div>
          {loading && <p>Loading...</p>}
          {!loading && conversations.length === 0 && <p>No conversations yet.</p>}
          {!loading && conversations.map((conversation) => (
            <button
              key={conversation.id}
              type="button"
              className={`messenger_thread${conversation.id === activeConversationId ? ' messenger_thread_active' : ''}`}
              onClick={() => setActiveConversation(conversation)}
            >
              <div className="messenger_thread_top">
                <strong>
                  {(conversation.participants || []).map((u) => u.displayName).join(', ') || 'Conversation'}
                  {conversation.isPinned ? ' 📌' : ''}
                </strong>
                <span>{formatTime(conversation.lastMessageAt)}</span>
              </div>
              <small>{conversation.latestMessage?.content || 'Attachment'}</small>
              {(conversation.unreadCount || 0) > 0 && (
                <span className="phase1_unread_badge">{conversation.unreadCount}</span>
              )}
            </button>
          ))}
          {!loading && conversationsPagination.hasMore && (
            <button type="button" className="phase1_btn" onClick={() => loadConversations()}>
              Load more
            </button>
          )}
        </aside>

        <section className="messenger_chat">
          <div className="messenger_chat_header">
            <h3>{activeTitle}</h3>
            {!activeConversationId && (
              <input
                type="text"
                className="messenger_input"
                placeholder="Recipient user id"
                value={recipientId}
                onChange={(event) => setRecipientId(event.target.value)}
              />
            )}
          </div>

          {activeConversationId && (
            <div className="messenger_settings_panel">
              <div className="messenger_settings_meta">
                <span>Pinned: {activeConversation?.isPinned ? 'Yes' : 'No'}</span>
                <span>Muted: {activeConversation?.mutedUntil ? 'Yes' : 'No'}</span>
                <span>Archived: {activeConversation?.isArchived ? 'Yes' : 'No'}</span>
              </div>
              <div className="messenger_pref_actions">
                <button type="button" className="phase1_btn phase1_btn_sm" onClick={() => updateConversationPreference('pin', { pinned: !activeConversation?.isPinned })}>
                  {activeConversation?.isPinned ? 'Unpin' : 'Pin'}
                </button>
                <button type="button" className="phase1_btn phase1_btn_sm" onClick={() => updateConversationPreference('mute', { minutes: activeConversation?.mutedUntil ? 0 : 60 })}>
                  {activeConversation?.mutedUntil ? 'Unmute' : 'Mute 1h'}
                </button>
                <button type="button" className="phase1_btn phase1_btn_sm" onClick={() => updateConversationPreference('archive', { archived: !activeConversation?.isArchived })}>
                  {activeConversation?.isArchived ? 'Unarchive' : 'Archive'}
                </button>
              </div>
            </div>
          )}

          <div className="messenger_messages">
            {messagesPagination.hasMore && (
              <div className="messenger_more_row">
                <button
                  type="button"
                  className="phase1_btn"
                  onClick={() => loadMessages(activeConversationId, { before: messagesPagination.nextBefore, prepend: true })}
                >
                  Load older messages
                </button>
              </div>
            )}
            {messages.map((message) => (
              <div key={message.id} className={`messenger_message${message.isMine ? ' messenger_message_mine' : ''}`}>
                <div className="messenger_message_body">
                  {message.content && <p>{message.content}</p>}
                  {Array.isArray(message.attachments) && message.attachments.map((attachmentItem) => {
                    const mimeType = attachmentItem.mimeType || '';
                    const isImage = mimeType.startsWith('image/');

                    return (
                      <div key={attachmentItem.id} className="messenger_attachment_wrap">
                        <a href={resolveMediaUrl(attachmentItem.url)} target="_blank" rel="noreferrer" className="messenger_attachment">
                          {attachmentItem.name}
                        </a>
                        <span className="messenger_attachment_chip">{mimeType || 'file'}</span>
                        {isImage && (
                          <img src={resolveMediaUrl(attachmentItem.url)} alt={attachmentItem.name} className="messenger_attachment_preview" />
                        )}
                      </div>
                    );
                  })}
                </div>
                <div className="messenger_message_meta">
                  <span>{message.sender?.displayName}</span>
                  <span>{formatTime(message.createdAt)}</span>
                  {message.isMine && (
                    <span>
                      {message.readAt ? 'Read' : message.deliveredAt ? 'Delivered' : 'Sent'}
                    </span>
                  )}
                  {message.isMine && (
                    <button type="button" className="messenger_receipt_btn" onClick={() => openReceiptViewer(message)}>
                      {`Delivered to ${(message.deliveredBy || []).length}`}
                    </button>
                  )}
                  <button
                    type="button"
                    className="messenger_receipt_btn"
                    onClick={() => openReceiptViewer(message)}
                  >
                    {(message.readBy || []).length} read
                  </button>
                </div>
              </div>
            ))}
          </div>

          <form className="messenger_form" onSubmit={sendMessage}>
            <textarea
              className="messenger_textarea"
              placeholder="Write a message"
              value={content}
              onChange={(event) => setContent(event.target.value)}
            />
            <div className="messenger_form_actions">
              <input type="file" onChange={(event) => setAttachment(event.target.files?.[0] || null)} />
              {attachment && <span className="messenger_attachment_selected">{attachment.name}</span>}
              <button type="submit" className="phase1_btn" disabled={sending}>
                {sending ? 'Sending...' : 'Send'}
              </button>
            </div>
          </form>
        </section>
      </div>

      {receiptViewer && (
        <div className="messenger_receipt_modal_backdrop" onClick={() => setReceiptViewer(null)} role="presentation">
          <div className="messenger_receipt_modal" onClick={(event) => event.stopPropagation()}>
            <div className="messenger_receipt_modal_header">
              <h4>Message receipts</h4>
              <button type="button" className="messenger_receipt_modal_close" onClick={() => setReceiptViewer(null)}>x</button>
            </div>
            <div className="messenger_receipt_modal_body">
              {(() => {
                const groups = buildReceiptGroups(receiptViewer.message);

                const renderUsers = (users) => (
                  users.map((user) => {
                    const avatarUrl = getUserAvatarUrl(user);
                    const displayName = user.displayName || user.email || 'Unknown user';

                    return (
                      <div className="messenger_receipt_modal_item" key={user.id || `${user.email}-${user.displayName}`}>
                        <div className="messenger_receipt_modal_item_left">
                          <span className="messenger_receipt_avatar">
                            {avatarUrl ? (
                              <img
                                src={resolveMediaUrl(avatarUrl)}
                                alt={`${displayName} avatar`}
                                className="messenger_receipt_avatar_img"
                              />
                            ) : getInitials(user)}
                          </span>
                          <div className="messenger_receipt_modal_item_txt">
                            <strong>{displayName}</strong>
                            <span>{user.email || ''}</span>
                          </div>
                        </div>
                      </div>
                    );
                  })
                );

                return (
                  <>
                    <div className="messenger_receipt_section">
                      <h5>Read ({groups.read.length})</h5>
                      {groups.read.length === 0 ? <p>No readers yet.</p> : renderUsers(groups.read)}
                    </div>
                    <div className="messenger_receipt_section">
                      <h5>Delivered (unread) ({groups.deliveredOnly.length})</h5>
                      {groups.deliveredOnly.length === 0 ? <p>No delivered-only users.</p> : renderUsers(groups.deliveredOnly)}
                    </div>
                    <div className="messenger_receipt_section">
                      <h5>Pending delivery ({groups.pending.length})</h5>
                      {groups.pending.length === 0 ? <p>Delivered to all recipients.</p> : renderUsers(groups.pending)}
                    </div>
                  </>
                );
              })()}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

