import { api } from '../api';

export const safetyApi = {
  submitReport: (payload) => api.post('/v1/safety/reports', payload),
  myReports: () => api.get('/v1/safety/reports/me'),
  blockUser: (userId) => api.post(`/v1/safety/blocks/${userId}`),
  unblockUser: (userId) => api.delete(`/v1/safety/blocks/${userId}`),
  blockedUsers: () => api.get('/v1/safety/blocks'),
};

