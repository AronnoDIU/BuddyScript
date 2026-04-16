import { api } from '../api';

export const privacyApi = {
  getCheckup: () => api.get('/v1/privacy-checkup'),
  updateCheckup: (settings) => api.put('/v1/privacy-checkup', settings),
};

