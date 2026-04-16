import { api } from '../api';

export const marketplaceApi = {
  listListings: (params = {}) => api.get('/v1/marketplace/listings', { params }),
  getListing: (id) => api.get(`/v1/marketplace/listings/${id}`),
  createListing: (payload) => {
    const formData = new FormData();
    formData.append('title', payload.title);
    formData.append('description', payload.description);
    formData.append('priceAmount', String(payload.priceAmount || 0));
    formData.append('currency', payload.currency || 'USD');
    formData.append('category', payload.category || 'general');
    formData.append('conditionType', payload.conditionType || 'used');
    if (payload.location) formData.append('location', payload.location);
    if (payload.tags) formData.append('tags', payload.tags);
    if (payload.image) formData.append('image', payload.image);

    return api.post('/v1/marketplace/listings', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  updateListing: (id, payload) => {
    const formData = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;
      formData.append(key, value);
    });

    return api.put(`/v1/marketplace/listings/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  deleteListing: (id) => api.delete(`/v1/marketplace/listings/${id}`),
  markSold: (id) => api.post(`/v1/marketplace/listings/${id}/mark-sold`),
  myListings: () => api.get('/v1/marketplace/my/listings'),
};

