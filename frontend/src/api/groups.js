import { api } from '../api';

export const groupsApi = {
  // Group Management
  createGroup: async (groupData) => {
    const formData = new FormData();
    formData.append('name', groupData.name);
    if (groupData.description) formData.append('description', groupData.description);
    formData.append('visibility', groupData.visibility || 'public');
    if (groupData.avatar) formData.append('avatar', groupData.avatar);
    if (groupData.settings) formData.append('settings', JSON.stringify(groupData.settings));

    return api.post('/v1/groups', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },

  getGroups: async (params = {}) => {
    return api.get('/v1/groups', { params });
  },

  getPublicGroups: async (params = {}) => {
    return api.get('/v1/groups/public', { params });
  },

  getGroup: async (groupId) => {
    return api.get(`/v1/groups/${groupId}`);
  },

  updateGroup: async (groupId, groupData) => {
    const formData = new FormData();
    if (groupData.name) formData.append('name', groupData.name);
    if (groupData.description) formData.append('description', groupData.description);
    if (groupData.visibility) formData.append('visibility', groupData.visibility);
    if (groupData.avatar) formData.append('avatar', groupData.avatar);
    if (groupData.settings) formData.append('settings', JSON.stringify(groupData.settings));

    return api.put(`/v1/groups/${groupId}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },

  deleteGroup: async (groupId) => {
    return api.delete(`/v1/groups/${groupId}`);
  },

  joinGroup: async (groupId) => {
    return api.post(`/v1/groups/${groupId}/join`);
  },

  leaveGroup: async (groupId) => {
    return api.post(`/v1/groups/${groupId}/leave`);
  },

  // Member Management
  getGroupMembers: async (groupId, params = {}) => {
    return api.get(`/v1/groups/${groupId}/members`, { params });
  },

  updateMemberRole: async (groupId, userId, role) => {
    return api.put(`/v1/groups/${groupId}/members/${userId}`, { role });
  },

  removeMember: async (groupId, userId) => {
    return api.delete(`/v1/groups/${groupId}/members/${userId}`);
  },

  // Group Posts
  createGroupPost: async (groupId, postData) => {
    const formData = new FormData();
    formData.append('content', postData.content);
    if (postData.image) formData.append('image', postData.image);

    return api.post(`/v1/groups/${groupId}/posts`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },

  getGroupPosts: async (groupId, params = {}) => {
    return api.get(`/v1/groups/${groupId}/posts`, { params });
  },

  getGroupPost: async (postId) => {
    return api.get(`/v1/group-posts/${postId}`);
  },

  deletePost: async (postId) => {
    return api.delete(`/v1/group-posts/${postId}`);
  },

  // Post Interactions
  togglePostLike: async (postId) => {
    return api.post(`/v1/group-posts/${postId}/likes/toggle`);
  },

  addPostComment: async (postId, content) => {
    return api.post(`/v1/group-posts/${postId}/comments`, { content });
  },

  toggleCommentLike: async (commentId) => {
    return api.post(`/v1/group-post-comments/${commentId}/likes/toggle`);
  }
};
