import { api } from '../api';

const LOCAL_EVENTS_KEY = 'buddyscript_local_events';
const LOCAL_EVENT_POSTS_KEY = 'buddyscript_local_event_posts';

const safeParse = (value, fallback) => {
  try {
    return JSON.parse(value) || fallback;
  } catch {
    return fallback;
  }
};

const makeId = (prefix) => `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

const readLocalEvents = () => {
  const saved = safeParse(localStorage.getItem(LOCAL_EVENTS_KEY), []);
  if (saved.length) {
    return saved;
  }

  const seeded = [
    {
      id: 'local-event-1',
      name: 'BuddyScript Launch Meetup',
      description: 'A live product walkthrough and community meet-and-greet.',
      type: 'hybrid',
      status: 'upcoming',
      location: 'Dhaka, Bangladesh',
      onlineUrl: 'https://meet.example.test/buddyscript',
      avatarUrl: null,
      startDate: new Date(Date.now() + 86400000).toISOString(),
      endDate: new Date(Date.now() + 90000000).toISOString(),
      maxAttendees: 120,
      attendeeCount: 48,
      postCount: 0,
      isOwner: true,
      isMember: true,
      createdAt: new Date().toISOString(),
    },
    {
      id: 'local-event-2',
      name: 'Frontend Systems Workshop',
      description: 'Design systems, component libraries, and testing strategies.',
      type: 'online',
      status: 'upcoming',
      location: 'Remote',
      onlineUrl: 'https://meet.example.test/frontend',
      avatarUrl: null,
      startDate: new Date(Date.now() + 172800000).toISOString(),
      endDate: new Date(Date.now() + 176400000).toISOString(),
      maxAttendees: 60,
      attendeeCount: 36,
      postCount: 0,
      isOwner: false,
      isMember: false,
      createdAt: new Date().toISOString(),
    },
  ];

  localStorage.setItem(LOCAL_EVENTS_KEY, JSON.stringify(seeded));
  return seeded;
};

const writeLocalEvents = (events) => {
  localStorage.setItem(LOCAL_EVENTS_KEY, JSON.stringify(events));
};

const readLocalPosts = () => safeParse(localStorage.getItem(LOCAL_EVENT_POSTS_KEY), {});
const writeLocalPosts = (postsByEvent) => localStorage.setItem(LOCAL_EVENT_POSTS_KEY, JSON.stringify(postsByEvent));

const shouldUseLocalFallback = (error) => {
  const status = error?.response?.status;
  return !status || status === 404 || status === 405;
};

const applyLimitOffset = (items, { limit = 20, offset = 0 } = {}) => items.slice(offset, offset + limit);

const filterEvents = (events, query) => {
  const normalized = query.trim().toLowerCase();
  if (!normalized) return events;
  return events.filter((event) => {
    const bag = `${event.name} ${event.description || ''} ${event.location || ''} ${event.type || ''}`.toLowerCase();
    return bag.includes(normalized);
  });
};

const updateEvent = (eventId, updater) => {
  const events = readLocalEvents();
  const nextEvents = events.map((event) => (event.id === eventId ? updater(event) : event));
  writeLocalEvents(nextEvents);
  return nextEvents.find((event) => event.id === eventId) || null;
};

export const eventsApi = {
  createEvent: async (eventData) => {
    try {
      const formData = new FormData();
      formData.append('name', eventData.name);
      if (eventData.description) formData.append('description', eventData.description);
      if (eventData.type) formData.append('type', eventData.type);
      if (eventData.startDate) formData.append('startDate', eventData.startDate);
      if (eventData.endDate) formData.append('endDate', eventData.endDate);
      if (eventData.location) formData.append('location', eventData.location);
      if (eventData.onlineUrl) formData.append('onlineUrl', eventData.onlineUrl);
      if (eventData.maxAttendees !== undefined) formData.append('maxAttendees', String(eventData.maxAttendees));
      if (eventData.avatar) formData.append('avatar', eventData.avatar);
      if (eventData.settings) formData.append('settings', JSON.stringify(eventData.settings));

      return await api.post('/v1/events', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) throw error;

      const events = readLocalEvents();
      const newEvent = {
        id: makeId('local-event'),
        name: eventData.name,
        description: eventData.description || '',
        type: eventData.type || 'offline',
        status: 'upcoming',
        location: eventData.location || '',
        onlineUrl: eventData.onlineUrl || '',
        avatarUrl: null,
        startDate: eventData.startDate || new Date().toISOString(),
        endDate: eventData.endDate || new Date(Date.now() + 3600000).toISOString(),
        maxAttendees: Number(eventData.maxAttendees || 0),
        attendeeCount: 1,
        postCount: 0,
        isOwner: true,
        isMember: true,
        createdAt: new Date().toISOString(),
      };
      writeLocalEvents([newEvent, ...events]);
      return { data: { event: newEvent, membership: { role: 'organizer' } } };
    }
  },

  getEvents: async (params = {}) => {
    try {
      return await api.get('/v1/events', { params });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) throw error;
      const events = filterEvents(readLocalEvents().filter((event) => event.isMember), params.q || '');
      return { data: { events: applyLimitOffset(events, params) } };
    }
  },

  getPublicEvents: async (params = {}) => {
    try {
      return await api.get('/v1/events/public', { params });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) throw error;
      const events = filterEvents(readLocalEvents(), params.q || '');
      return { data: { events: applyLimitOffset(events, params) } };
    }
  },

  getEvent: async (eventId) => {
    try {
      return await api.get(`/v1/events/${eventId}`);
    } catch (error) {
      if (!shouldUseLocalFallback(error)) throw error;
      const event = readLocalEvents().find((item) => item.id === eventId) || null;
      return { data: { event } };
    }
  },

  joinEvent: async (eventId) => {
    try {
      return await api.post(`/v1/events/${eventId}/join`);
    } catch (error) {
      if (!shouldUseLocalFallback(error)) throw error;
      const event = updateEvent(eventId, (item) => ({ ...item, isMember: true, attendeeCount: (item.attendeeCount || 0) + 1 }));
      return { data: { event, membership: { role: 'attendee' } } };
    }
  },

  leaveEvent: async (eventId) => {
    try {
      return await api.post(`/v1/events/${eventId}/leave`);
    } catch (error) {
      if (!shouldUseLocalFallback(error)) throw error;
      const event = updateEvent(eventId, (item) => ({ ...item, isMember: false, attendeeCount: Math.max((item.attendeeCount || 1) - 1, 0) }));
      return { data: { event } };
    }
  },

  getEventMembers: async (eventId, params = {}) => api.get(`/v1/events/${eventId}/members`, { params }),

  updateMemberRole: async (eventId, userId, role) => api.put(`/v1/events/${eventId}/members/${userId}`, { role }),

  removeMember: async (eventId, userId) => api.delete(`/v1/events/${eventId}/members/${userId}`),

  createEventPost: async (eventId, postData) => {
    try {
      const formData = new FormData();
      formData.append('content', postData.content);
      if (postData.image) formData.append('image', postData.image);
      return await api.post(`/v1/events/${eventId}/posts`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) throw error;

      const postsByEvent = readLocalPosts();
      const post = {
        id: makeId('local-event-post'),
        eventId,
        content: postData.content,
        imageUrl: postData.image ? URL.createObjectURL(postData.image) : null,
        author: { displayName: 'You' },
        likesCount: 0,
        commentsCount: 0,
        createdAt: new Date().toISOString(),
      };
      postsByEvent[eventId] = [post, ...(postsByEvent[eventId] || [])];
      writeLocalPosts(postsByEvent);
      const event = updateEvent(eventId, (item) => ({ ...item, postCount: (item.postCount || 0) + 1 }));
      return { data: { post, event } };
    }
  },

  getEventPosts: async (eventId, params = {}) => {
    try {
      return await api.get(`/v1/events/${eventId}/posts`, { params });
    } catch (error) {
      if (!shouldUseLocalFallback(error)) throw error;
      const postsByEvent = readLocalPosts();
      const posts = postsByEvent[eventId] || [];
      return { data: { posts: applyLimitOffset(posts, params) } };
    }
  },
};


