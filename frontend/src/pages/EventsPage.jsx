import { useEffect, useMemo, useState } from 'react';
import { CalendarDays, Clock3, Image, MapPin, Plus, Search, Users } from 'lucide-react';
import { eventsApi } from '../api/events';
import './EventsPage.css';

const INITIAL_EVENT_FORM = {
  name: '',
  description: '',
  type: 'online',
  startDate: '',
  endDate: '',
  location: '',
  onlineUrl: '',
  maxAttendees: '',
};

const formatDate = (value) => {
  if (!value) return 'TBA';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'TBA';
  return date.toLocaleString();
};

export default function EventsPage() {
  const [myEvents, setMyEvents] = useState([]);
  const [discoverEvents, setDiscoverEvents] = useState([]);
  const [selectedEvent, setSelectedEvent] = useState(null);
  const [posts, setPosts] = useState([]);
  const [activeTab, setActiveTab] = useState('my-events');
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [loadingPosts, setLoadingPosts] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [creating, setCreating] = useState(false);
  const [error, setError] = useState('');
  const [postContent, setPostContent] = useState('');
  const [postFile, setPostFile] = useState(null);
  const [posting, setPosting] = useState(false);
  const [createForm, setCreateForm] = useState(INITIAL_EVENT_FORM);

  const activeEvents = useMemo(
    () => (activeTab === 'my-events' ? myEvents : discoverEvents),
    [activeTab, myEvents, discoverEvents],
  );

  const fetchEvents = async (query = '') => {
    setLoading(true);
    setError('');
    try {
      const [myEventsResponse, publicEventsResponse] = await Promise.all([
        eventsApi.getEvents({ q: query, limit: 40 }),
        eventsApi.getPublicEvents({ q: query, limit: 40 }),
      ]);

      const own = myEventsResponse.data?.events || [];
      const discover = publicEventsResponse.data?.events || [];
      setMyEvents(own);
      setDiscoverEvents(discover);

      if (!selectedEvent && own.length > 0) {
        setSelectedEvent(own[0]);
      }
    } catch (loadError) {
      setError(loadError?.response?.data?.message || 'Failed to load events.');
    } finally {
      setLoading(false);
    }
  };

  const fetchPosts = async (event) => {
    if (!event?.id) {
      setPosts([]);
      return;
    }

    setLoadingPosts(true);
    try {
      const response = await eventsApi.getEventPosts(event.id, { limit: 30 });
      setPosts(response.data?.posts || []);
    } catch (loadError) {
      setError(loadError?.response?.data?.message || 'Failed to load event posts.');
    } finally {
      setLoadingPosts(false);
    }
  };

  useEffect(() => {
    fetchEvents();
  }, []);

  useEffect(() => {
    fetchPosts(selectedEvent);
  }, [selectedEvent?.id]);

  const onSearch = (event) => {
    const query = event.target.value;
    setSearchQuery(query);
    fetchEvents(query);
  };

  const onCreateEvent = async (event) => {
    event.preventDefault();
    setCreating(true);
    setError('');

    try {
      const response = await eventsApi.createEvent({
        ...createForm,
        maxAttendees: createForm.maxAttendees ? Number(createForm.maxAttendees) : 0,
      });
      const newEvent = response.data?.event;
      if (newEvent) {
        setMyEvents((prev) => [newEvent, ...prev]);
        setDiscoverEvents((prev) => [newEvent, ...prev.filter((item) => item.id !== newEvent.id)]);
        setSelectedEvent(newEvent);
      }
      setCreateForm(INITIAL_EVENT_FORM);
      setShowCreateModal(false);
    } catch (submitError) {
      setError(submitError?.response?.data?.message || 'Failed to create event.');
    } finally {
      setCreating(false);
    }
  };

  const onToggleAttendance = async (event) => {
    try {
      if (event.isMember) {
        await eventsApi.leaveEvent(event.id);
      } else {
        await eventsApi.joinEvent(event.id);
      }
      await fetchEvents(searchQuery);
      if (selectedEvent?.id === event.id) {
        const refreshed = await eventsApi.getEvent(event.id);
        setSelectedEvent(refreshed.data?.event || event);
      }
    } catch (toggleError) {
      setError(toggleError?.response?.data?.message || 'Failed to update attendance.');
    }
  };

  const onCreatePost = async (submitEvent) => {
    submitEvent.preventDefault();
    if (!selectedEvent?.id || !postContent.trim()) return;

    setPosting(true);
    setError('');
    try {
      const response = await eventsApi.createEventPost(selectedEvent.id, {
        content: postContent.trim(),
        image: postFile,
      });
      const newPost = response.data?.post;
      if (newPost) {
        setPosts((prev) => [newPost, ...prev]);
      }
      setPostContent('');
      setPostFile(null);
    } catch (submitError) {
      setError(submitError?.response?.data?.message || 'Failed to publish event post.');
    } finally {
      setPosting(false);
    }
  };

  return (
    <div className="fb-events">
      <header className="fb-events__header">
        <div>
          <h1>Events</h1>
          <p>Create events, manage attendance, and share updates with attendees.</p>
        </div>

        <div className="fb-events__header-actions">
          <label className="fb-events__search">
            <Search size={16} />
            <input value={searchQuery} onChange={onSearch} placeholder="Search events" />
          </label>

          <button type="button" className="events-btn events-btn--primary" onClick={() => setShowCreateModal(true)}>
            <Plus size={16} />
            Create Event
          </button>
        </div>
      </header>

      <div className="fb-events__layout">
        <aside className="fb-events__left card-ui">
          <div className="fb-events__tabs">
            <button type="button" className={activeTab === 'my-events' ? 'is-active' : ''} onClick={() => setActiveTab('my-events')}>Your Events</button>
            <button type="button" className={activeTab === 'discover' ? 'is-active' : ''} onClick={() => setActiveTab('discover')}>Discover</button>
          </div>

          {loading ? (
            <p className="fb-events__muted">Loading events...</p>
          ) : activeEvents.length === 0 ? (
            <p className="fb-events__muted">No events found.</p>
          ) : (
            <div className="fb-events__event-list">
              {activeEvents.map((event) => (
                <button
                  key={event.id}
                  type="button"
                  className={`fb-events__card ${selectedEvent?.id === event.id ? 'is-selected' : ''}`}
                  onClick={() => setSelectedEvent(event)}
                >
                  <span className="fb-events__avatar" aria-hidden="true">
                    {event.avatarUrl ? <img src={event.avatarUrl} alt="" /> : <CalendarDays size={18} />}
                  </span>
                  <span className="fb-events__card-content">
                    <strong>{event.name}</strong>
                    <span>{event.type || 'online'}</span>
                    <span>{formatDate(event.startDate)}</span>
                  </span>
                  <span className="fb-events__card-meta">
                    <span>{(event.attendeeCount || 0).toLocaleString()} attending</span>
                    {event.isMember ? 'Joined' : 'Open'}
                  </span>
                </button>
              ))}
            </div>
          )}
        </aside>

        <section className="fb-events__center">
          {!selectedEvent ? (
            <div className="card-ui fb-events__empty">Select an event to see details and updates.</div>
          ) : (
            <>
              <article className="card-ui fb-events__hero">
                <h2>{selectedEvent.name}</h2>
                <p>{selectedEvent.description || 'No description added yet.'}</p>
                <div className="fb-events__hero-stats">
                  <span><Users size={14} /> {(selectedEvent.attendeeCount || 0).toLocaleString()} attending</span>
                  <span><Clock3 size={14} /> {formatDate(selectedEvent.startDate)}</span>
                  <span><MapPin size={14} /> {selectedEvent.location || 'Online'}</span>
                </div>
                <div className="fb-events__hero-actions">
                  <button type="button" className="events-btn events-btn--secondary" onClick={() => onToggleAttendance(selectedEvent)}>
                    {selectedEvent.isMember ? 'Leave Event' : 'Join Event'}
                  </button>
                </div>
              </article>

              {selectedEvent.isOwner || selectedEvent.isMember || selectedEvent.permissions?.post ? (
                <form className="card-ui fb-events__composer" onSubmit={onCreatePost}>
                  <div className="fb-events__composer-top">
                    <span className="fb-events__avatar">E</span>
                    <textarea
                      value={postContent}
                      onChange={(event) => setPostContent(event.target.value)}
                      placeholder={`Share an update for ${selectedEvent.name}`}
                      rows={3}
                    />
                  </div>
                  {postFile && <p className="fb-events__muted">Image attached: {postFile.name}</p>}
                  <div className="fb-events__composer-actions">
                    <button type="button" className="events-btn events-btn--ghost" onClick={() => document.getElementById('event-post-image-input')?.click()}>
                      <Image size={16} />
                      Photo
                    </button>
                    <button type="submit" className="events-btn events-btn--primary" disabled={posting || !postContent.trim()}>
                      {posting ? 'Posting...' : 'Post'}
                    </button>
                  </div>
                  <input
                    id="event-post-image-input"
                    type="file"
                    accept="image/*"
                    hidden
                    onChange={(event) => setPostFile(event.target.files?.[0] || null)}
                  />
                </form>
              ) : (
                <div className="card-ui fb-events__empty">Join this event to post updates.</div>
              )}

              <div className="fb-events__feed">
                {loadingPosts ? (
                  <div className="card-ui fb-events__empty">Loading posts...</div>
                ) : posts.length === 0 ? (
                  <div className="card-ui fb-events__empty">No posts yet. Be the first to share an update.</div>
                ) : (
                  posts.map((post) => (
                    <article key={post.id} className="card-ui fb-events__post">
                      <div className="fb-events__post-head">
                        <span className="fb-events__avatar">{post.author?.displayName?.charAt(0)?.toUpperCase() || 'E'}</span>
                        <div>
                          <strong>{post.author?.displayName || 'Event member'}</strong>
                          <p>{formatDate(post.createdAt)}</p>
                        </div>
                      </div>
                      <p>{post.content}</p>
                      {post.imageUrl && <img src={post.imageUrl} alt="Event post attachment" className="fb-events__post-image" />}
                      <div className="fb-events__post-stats">
                        <span>❤️ {post.likesCount || 0}</span>
                        <span>💬 {post.commentsCount || 0}</span>
                      </div>
                    </article>
                  ))
                )}
              </div>
            </>
          )}
        </section>

        <aside className="fb-events__right">
          <div className="card-ui fb-events__panel">
            <h3>Upcoming</h3>
            <p>Keep attendees informed about schedules and reminders.</p>
          </div>

          <div className="card-ui fb-events__panel">
            <h3>Event stats</h3>
            <ul>
              <li>{myEvents.length} joined events</li>
              <li>{discoverEvents.length} discoverable events</li>
              <li>{posts.length} posts in current event</li>
            </ul>
          </div>
        </aside>
      </div>

      {error && <p className="fb-events__error">{error}</p>}

      {showCreateModal && (
        <div className="fb-events__modal-backdrop" onClick={() => setShowCreateModal(false)} role="presentation">
          <div className="fb-events__modal card-ui" onClick={(event) => event.stopPropagation()}>
            <h3>Create Event</h3>
            <form onSubmit={onCreateEvent} className="fb-events__modal-form">
              <label>
                Event name
                <input value={createForm.name} onChange={(event) => setCreateForm((prev) => ({ ...prev, name: event.target.value }))} required />
              </label>
              <label>
                Description
                <textarea value={createForm.description} onChange={(event) => setCreateForm((prev) => ({ ...prev, description: event.target.value }))} rows={3} />
              </label>
              <label>
                Type
                <select value={createForm.type} onChange={(event) => setCreateForm((prev) => ({ ...prev, type: event.target.value }))}>
                  <option value="online">Online</option>
                  <option value="offline">Offline</option>
                  <option value="hybrid">Hybrid</option>
                </select>
              </label>
              <div className="fb-events__modal-grid">
                <label>
                  Start date
                  <input type="datetime-local" value={createForm.startDate} onChange={(event) => setCreateForm((prev) => ({ ...prev, startDate: event.target.value }))} />
                </label>
                <label>
                  End date
                  <input type="datetime-local" value={createForm.endDate} onChange={(event) => setCreateForm((prev) => ({ ...prev, endDate: event.target.value }))} />
                </label>
              </div>
              <label>
                Location
                <input value={createForm.location} onChange={(event) => setCreateForm((prev) => ({ ...prev, location: event.target.value }))} />
              </label>
              <label>
                Online URL
                <input value={createForm.onlineUrl} onChange={(event) => setCreateForm((prev) => ({ ...prev, onlineUrl: event.target.value }))} />
              </label>
              <label>
                Max attendees
                <input type="number" min="0" value={createForm.maxAttendees} onChange={(event) => setCreateForm((prev) => ({ ...prev, maxAttendees: event.target.value }))} />
              </label>
              <div className="fb-events__modal-actions">
                <button type="button" className="events-btn events-btn--ghost" onClick={() => setShowCreateModal(false)}>Cancel</button>
                <button type="submit" className="events-btn events-btn--primary" disabled={creating}>{creating ? 'Creating...' : 'Create Event'}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}


