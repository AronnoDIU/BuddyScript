import { api } from '../api';

export const securityApi = {
  getTwoFactorStatus: () => api.get('/v1/2fa/status'),
  initTwoFactorSetup: () => api.post('/v1/2fa/setup/init'),
  confirmTwoFactorSetup: (code) => api.post('/v1/2fa/setup/confirm', { code }),
  disableTwoFactor: (code) => api.post('/v1/2fa/disable', { code }),
  verifyLoginChallenge: ({ challengeId, code }) => api.post('/v1/2fa/verify', { challengeId, code }, { skipAuth: true }),
};

