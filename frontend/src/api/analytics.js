import { api } from '../api';

export const trackPageView = async (pageName, data = {}) => {
  try {
    await api.post('/v1/analytics/page-view', { pageName, data });
  } catch (error) {
    console.error('Failed to track page view:', error);
  }
};

export const trackFeatureUsage = async (featureName, data = {}) => {
  try {
    await api.post('/v1/analytics/feature-usage', { featureName, data });
  } catch (error) {
    console.error('Failed to track feature usage:', error);
  }
};
