import React, { useState, useRef } from 'react';
import { X, Upload, Globe, Users, Lock, EyeOff, Camera } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import './CreateGroupModal.css';

export default function CreateGroupModal({ onClose, onGroupCreated }) {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    visibility: 'public',
    settings: {
      allow_member_posts: true,
      require_approval: false,
      enable_discussion: true,
    }
  });
  const [avatarPreview, setAvatarPreview] = useState(null);
  const [avatarFile, setAvatarFile] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const fileInputRef = useRef(null);

  const visibilityOptions = [
    {
      value: 'public',
      label: 'Public',
      description: 'Anyone can find and join',
      icon: <Globe size={20} />
    },
    {
      value: 'private',
      label: 'Private',
      description: 'Only members can find and join',
      icon: <Users size={20} />
    },
    {
      value: 'secret',
      label: 'Secret',
      description: 'Only members can find, invite only',
      icon: <Lock size={20} />
    }
  ];

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    
    if (name.startsWith('settings.')) {
      const settingName = name.split('.')[1];
      setFormData(prev => ({
        ...prev,
        settings: {
          ...prev.settings,
          [settingName]: type === 'checkbox' ? checked : value
        }
      }));
    } else {
      setFormData(prev => ({
        ...prev,
        [name]: type === 'checkbox' ? checked : value
      }));
    }

    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file type and size
      if (!file.type.startsWith('image/')) {
        setErrors(prev => ({ ...prev, avatar: 'Please select an image file' }));
        return;
      }

      if (file.size > 5 * 1024 * 1024) { // 5MB
        setErrors(prev => ({ ...prev, avatar: 'Image must be less than 5MB' }));
        return;
      }

      setAvatarFile(file);
      const reader = new FileReader();
      reader.onload = (e) => {
        setAvatarPreview(e.target.result);
      };
      reader.readAsDataURL(file);
      
      if (errors.avatar) {
        setErrors(prev => ({ ...prev, avatar: null }));
      }
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Group name is required';
    } else if (formData.name.trim().length < 3) {
      newErrors.name = 'Group name must be at least 3 characters';
    } else if (formData.name.trim().length > 100) {
      newErrors.name = 'Group name must be less than 100 characters';
    }

    if (formData.description && formData.description.length > 1000) {
      newErrors.description = 'Description must be less than 1000 characters';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    try {
      setIsSubmitting(true);
      
      const groupData = {
        ...formData,
        avatar: avatarFile
      };

      const response = await groupsApi.createGroup(groupData);
      onGroupCreated(response.data);
      
    } catch (error) {
      console.error('Failed to create group:', error);
      
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({ submit: 'Failed to create group. Please try again.' });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    if (!isSubmitting) {
      onClose();
    }
  };

  const handleBackdropClick = (e) => {
    if (e.target === e.currentTarget) {
      handleClose();
    }
  };

  return (
    <div className="create-group-modal" onClick={handleBackdropClick}>
      <div className="create-group-modal__content">
        <div className="create-group-modal__header">
          <h2 className="create-group-modal__title">Create Group</h2>
          <button
            className="create-group-modal__close"
            onClick={handleClose}
            disabled={isSubmitting}
          >
            <X size={20} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="create-group-modal__form">
          {/* Avatar Upload */}
          <div className="create-group-modal__avatar-section">
            <div className="create-group-modal__avatar-upload">
              {avatarPreview ? (
                <img
                  src={avatarPreview}
                  alt="Group avatar"
                  className="create-group-modal__avatar-preview"
                />
              ) : (
                <div className="create-group-modal__avatar-placeholder">
                  <Camera size={32} />
                  <span>Add Group Photo</span>
                </div>
              )}
              <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                onChange={handleAvatarChange}
                className="create-group-modal__avatar-input"
              />
              <button
                type="button"
                className="create-group-modal__avatar-btn"
                onClick={() => fileInputRef.current?.click()}
              >
                <Upload size={16} />
                {avatarPreview ? 'Change' : 'Add'} Photo
              </button>
            </div>
            {errors.avatar && (
              <span className="create-group-modal__error">{errors.avatar}</span>
            )}
          </div>

          {/* Basic Info */}
          <div className="create-group-modal__section">
            <div className="create-group-modal__field">
              <label htmlFor="name" className="create-group-modal__label">
                Group Name *
              </label>
              <input
                type="text"
                id="name"
                name="name"
                value={formData.name}
                onChange={handleInputChange}
                placeholder="Enter group name"
                className={`create-group-modal__input ${errors.name ? 'error' : ''}`}
                maxLength={100}
              />
              {errors.name && (
                <span className="create-group-modal__error">{errors.name}</span>
              )}
            </div>

            <div className="create-group-modal__field">
              <label htmlFor="description" className="create-group-modal__label">
                Description
              </label>
              <textarea
                id="description"
                name="description"
                value={formData.description}
                onChange={handleInputChange}
                placeholder="What's your group about?"
                className={`create-group-modal__textarea ${errors.description ? 'error' : ''}`}
                maxLength={1000}
                rows={3}
              />
              {errors.description && (
                <span className="create-group-modal__error">{errors.description}</span>
              )}
            </div>
          </div>

          {/* Visibility */}
          <div className="create-group-modal__section">
            <h3 className="create-group-modal__section-title">Privacy</h3>
            <div className="create-group-modal__visibility-options">
              {visibilityOptions.map((option) => (
                <label
                  key={option.value}
                  className={`create-group-modal__visibility-option ${
                    formData.visibility === option.value ? 'selected' : ''
                  }`}
                >
                  <input
                    type="radio"
                    name="visibility"
                    value={option.value}
                    checked={formData.visibility === option.value}
                    onChange={handleInputChange}
                  />
                  <div className="create-group-modal__visibility-content">
                    <div className="create-group-modal__visibility-header">
                      {option.icon}
                      <div>
                        <div className="create-group-modal__visibility-label">
                          {option.label}
                        </div>
                        <div className="create-group-modal__visibility-description">
                          {option.description}
                        </div>
                      </div>
                    </div>
                  </div>
                </label>
              ))}
            </div>
          </div>

          {/* Settings */}
          <div className="create-group-modal__section">
            <h3 className="create-group-modal__section-title">Group Settings</h3>
            <div className="create-group-modal__settings">
              <label className="create-group-modal__setting">
                <input
                  type="checkbox"
                  name="settings.allow_member_posts"
                  checked={formData.settings.allow_member_posts}
                  onChange={handleInputChange}
                />
                <div className="create-group-modal__setting-content">
                  <div className="create-group-modal__setting-label">
                    Allow members to post
                  </div>
                  <div className="create-group-modal__setting-description">
                    Members can create posts in this group
                  </div>
                </div>
              </label>

              <label className="create-group-modal__setting">
                <input
                  type="checkbox"
                  name="settings.require_approval"
                  checked={formData.settings.require_approval}
                  onChange={handleInputChange}
                />
                <div className="create-group-modal__setting-content">
                  <div className="create-group-modal__setting-label">
                    Require approval to join
                  </div>
                  <div className="create-group-modal__setting-description">
                    New members must be approved before joining
                  </div>
                </div>
              </label>

              <label className="create-group-modal__setting">
                <input
                  type="checkbox"
                  name="settings.enable_discussion"
                  checked={formData.settings.enable_discussion}
                  onChange={handleInputChange}
                />
                <div className="create-group-modal__setting-content">
                  <div className="create-group-modal__setting-label">
                    Enable discussion
                  </div>
                  <div className="create-group-modal__setting-description">
                    Members can comment and interact with posts
                  </div>
                </div>
              </label>
            </div>
          </div>

          {/* Error Display */}
          {errors.submit && (
            <div className="create-group-modal__submit-error">
              {errors.submit}
            </div>
          )}

          {/* Actions */}
          <div className="create-group-modal__actions">
            <button
              type="button"
              className="create-group-modal__cancel-btn"
              onClick={handleClose}
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="create-group-modal__submit-btn"
              disabled={isSubmitting}
            >
              {isSubmitting ? 'Creating...' : 'Create Group'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
