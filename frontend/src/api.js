import axios from 'axios';

export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000';

export const TOKEN_KEY = 'buddyscript_token';

export const api = axios.create({
  baseURL: `${API_BASE_URL}/api`,
  withCredentials: true,
});

let isRefreshing = false;
let requestQueue = [];

const flushQueue = (error, token = null) => {
  requestQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error);
      return;
    }

    resolve(token);
  });

  requestQueue = [];
};

api.interceptors.request.use((config) => {
  if (config.skipAuth === true) {
    return config;
  }

  const token = localStorage.getItem(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;
    const status = error.response?.status;
    const requestUrl = originalRequest?.url || '';

    const isRefreshRequest = requestUrl.includes('/refresh');
    const isLoginRequest = requestUrl.includes('/login_check');

    if (
      status !== 401
      || !originalRequest
      || originalRequest._retry
      || isRefreshRequest
      || isLoginRequest
    ) {
      return Promise.reject(error);
    }

    if (isRefreshing) {
      return new Promise((resolve, reject) => {
        requestQueue.push({ resolve, reject });
      }).then((token) => {
        originalRequest.headers.Authorization = `Bearer ${token}`;
        return api(originalRequest);
      });
    }

    originalRequest._retry = true;
    isRefreshing = true;

    try {
      const refreshResponse = await api.post('/v1/refresh', {}, { skipAuth: true });
      const newToken = refreshResponse?.data?.token;

      if (!newToken) {
        return Promise.reject(new Error('Refresh endpoint did not return an access token.'));
      }

      setToken(newToken);
      flushQueue(null, newToken);

      originalRequest.headers.Authorization = `Bearer ${newToken}`;
      return api(originalRequest);
    } catch (refreshError) {
      clearToken();
      flushQueue(refreshError, null);
      return Promise.reject(refreshError);
    } finally {
      isRefreshing = false;
    }
  },
);

export const setToken = (token) => {
  localStorage.setItem(TOKEN_KEY, token);
};

export const clearToken = () => {
  localStorage.removeItem(TOKEN_KEY);
};

export const getToken = () => localStorage.getItem(TOKEN_KEY);

const collectErrorMessages = (value, messages = []) => {
  if (!value) {
    return messages;
  }

  if (typeof value === 'string') {
    const trimmed = value.trim();
    if (trimmed) messages.push(trimmed);
    return messages;
  }

  if (Array.isArray(value)) {
    value.forEach((item) => collectErrorMessages(item, messages));
    return messages;
  }

  if (typeof value === 'object') {
    if ('errors' in value) {
      return collectErrorMessages(value.errors, messages);
    }

    Object.values(value).forEach((item) => collectErrorMessages(item, messages));
  }

  return messages;
};

export const getApiFieldErrors = (error) => {
  const responseErrors = error?.response?.data?.errors;
  if (!responseErrors || typeof responseErrors !== 'object' || Array.isArray(responseErrors)) {
    return {};
  }

  return Object.fromEntries(
    Object.entries(responseErrors).map(([field, value]) => [field, collectErrorMessages(value).join(' ')])
  );
};

export const getApiErrorMessage = (error, fallback = 'Something went wrong. Please try again.') => {
  const status = error?.response?.status;
  const response = error?.response?.data;

  const fieldMessages = collectErrorMessages(response?.errors);
  if (fieldMessages.length > 0) {
    return fieldMessages[0];
  }

  if (typeof response?.message === 'string' && response.message.trim()) {
    return response.message.trim();
  }

  if (status === 401 && fallback) {
    return fallback;
  }

  if (status === 403 && fallback) {
    return fallback;
  }

  return fallback;
};

export const apiErrorUtils = {
  getApiErrorMessage,
  getApiFieldErrors,
};

void getApiErrorMessage;
void getApiFieldErrors;

export const resolveMediaUrl = (path) => {
  if (!path) return '';
  if (/^https?:\/\//i.test(path)) return path;
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${API_BASE_URL}${normalizedPath}`;
};

