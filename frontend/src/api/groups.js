import api from './api';

export const groupsApi = {
  // Group Management
  createGroup: async (groupData) => {
    const formData = new FormData();
    formData.append('name', groupData.name);
    if (groupData.description) formData.append('description', groupData.description);
    formData.append('visibility', groupData.visibility || 'public');
    if (groupData.avatar) formData.append('avatar', groupData.avatar);
    if (groupData.settings) formData.append('settings', JSON.stringify(groupData.settings));

    return api.post('/groups', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },

  getGroups: async (params = {}) => {
    return api.get('/groups', { params });
  },

  getPublicGroups: async (params = {}) => {
    return api.get('/groups/public', { params });
  },

  getGroup: async (groupId) => {
    return api.get(`/groups/${groupId}`);
  },

  updateGroup: async (groupId, groupData) => {
    const formData = new FormData();
    if (groupData.name) formData.append('name', groupData.name);
    if (groupData.description) formData.append('description', groupData.description);
    if (groupData.visibility) formData.append('visibility', groupData.visibility);
    if (groupData.avatar) formData.append('avatar', groupData.avatar);
    if (groupData.settings) formData.append('settings', JSON.stringify(groupData.settings));

    return api.put(`/groups/${groupId}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },

  joinGroup: async (groupId) => {
    return api.post(`/groups/${groupId}/join`);
  },

  leaveGroup: async (groupId) => {
    return api.post(`/groups/${groupId}/leave`);
  },

  // Member Management
  getGroupMembers: async (groupId, params = {}) => {
    return api.get(`/groups/${groupId}/members`, { params });
  },

  updateMemberRole: async (groupId, userId, role) => {
    return api.put(`/groups/${groupId}/members/${userId}`, { role });
  },

  removeMember: async (groupId, userId) => {
    return api.delete(`/groups/${groupId}/members/${userId}`);
  },

  // Group Posts
  createGroupPost: async (groupId, postData) => {
    const formData = new FormData();
    formData.append('content', postData.content);
    if (postData.image) formData.append('image', postData.image);

    return api.post(`/groups/${groupId}/posts`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },

  getGroupPosts: async (groupId, params = {}) => {
    return api.get(`/groups/${groupId}/posts`, { params });
  },

  getGroupPost: async (postId) => {
    return api.get(`/group-posts/${postId}`);
  },

  // Post Interactions
  togglePostLike: async (postId) => {
    return api.post(`/group-posts/${postId}/likes/toggle`);
  },

  addPostComment: async (postId, content) => {
    return api.post(`/group-posts/${postId}/comments`, { content });
  },

  toggleCommentLike: async (commentId) => {
    return api.post(`/group-post-comments/${commentId}/likes/toggle`);
  }
};
