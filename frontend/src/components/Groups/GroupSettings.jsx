import { useState, useRef } from 'react';
import { X, Upload, Globe, Users, Lock, Camera, Save, Trash2 } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import './GroupSettings.css';

export default function GroupSettings({ group, onGroupUpdated }) {
  const [formData, setFormData] = useState({
    name: group.name,
    description: group.description || '',
    visibility: group.visibility,
    settings: group.settings || {
      allow_member_posts: true,
      require_approval: false,
      enable_discussion: true,
    }
  });
  const [avatarPreview, setAvatarPreview] = useState(group.avatarUrl);
  const [avatarFile, setAvatarFile] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [activeTab, setActiveTab] = useState('general');
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
      if (!file.type.startsWith('image/')) {
        setErrors(prev => ({ ...prev, avatar: 'Please select an image file' }));
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
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

  const removeAvatar = () => {
    setAvatarFile(null);
    setAvatarPreview(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
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

      const response = await groupsApi.updateGroup(group.id, groupData);
      onGroupUpdated(response.data.group);
      
    } catch (error) {
      console.error('Failed to update group:', error);
      
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({ submit: 'Failed to update group. Please try again.' });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDeleteGroup = async () => {
    if (window.confirm('Are you sure you want to delete this group? This action cannot be undone.')) {
      try {
        await groupsApi.deleteGroup(group.id);
        // Redirect to groups page
        window.location.href = '/groups';
      } catch (error) {
        console.error('Failed to delete group:', error);
        setErrors({ submit: 'Failed to delete group. Please try again.' });
      }
    }
  };

  return (
    <div className="group-settings">
      <div className="group-settings__header">
        <h2 className="group-settings__title">Group Settings</h2>
      </div>

      <div className="group-settings__tabs">
        <button
          className={`group-settings__tab ${activeTab === 'general' ? 'active' : ''}`}
          onClick={() => setActiveTab('general')}
        >
          General
        </button>
        <button
          className={`group-settings__tab ${activeTab === 'privacy' ? 'active' : ''}`}
          onClick={() => setActiveTab('privacy')}
        >
          Privacy
        </button>
        <button
          className={`group-settings__tab ${activeTab === 'permissions' ? 'active' : ''}`}
          onClick={() => setActiveTab('permissions')}
        >
          Permissions
        </button>
        <button
          className={`group-settings__tab ${activeTab === 'danger' ? 'active' : ''}`}
          onClick={() => setActiveTab('danger')}
        >
          Danger Zone
        </button>
      </div>

      <form onSubmit={handleSubmit} className="group-settings__form">
        {activeTab === 'general' && (
          <div className="group-settings__section">
            {/* Avatar */}
            <div className="group-settings__avatar-section">
              <label className="group-settings__label">Group Avatar</label>
              <div className="group-settings__avatar-upload">
                {avatarPreview ? (
                  <div className="group-settings__avatar-preview">
                    <img src={avatarPreview} alt="Group avatar" />
                    <button
                      type="button"
                      className="group-settings__avatar-remove"
                      onClick={removeAvatar}
                    >
                      <X size={16} />
                    </button>
                  </div>
                ) : (
                  <div className="group-settings__avatar-placeholder">
                    <Camera size={32} />
                    <span>No avatar</span>
                  </div>
                )}
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/*"
                  onChange={handleAvatarChange}
                  className="group-settings__avatar-input"
                />
                <button
                  type="button"
                  className="group-settings__avatar-btn"
                  onClick={() => fileInputRef.current?.click()}
                >
                  <Upload size={16} />
                  {avatarPreview ? 'Change' : 'Add'} Avatar
                </button>
              </div>
              {errors.avatar && (
                <span className="group-settings__error">{errors.avatar}</span>
              )}
            </div>

            {/* Basic Info */}
            <div className="group-settings__field">
              <label htmlFor="name" className="group-settings__label">
                Group Name *
              </label>
              <input
                type="text"
                id="name"
                name="name"
                value={formData.name}
                onChange={handleInputChange}
                className={`group-settings__input ${errors.name ? 'error' : ''}`}
                maxLength={100}
              />
              {errors.name && (
                <span className="group-settings__error">{errors.name}</span>
              )}
            </div>

            <div className="group-settings__field">
              <label htmlFor="description" className="group-settings__label">
                Description
              </label>
              <textarea
                id="description"
                name="description"
                value={formData.description}
                onChange={handleInputChange}
                className={`group-settings__textarea ${errors.description ? 'error' : ''}`}
                maxLength={1000}
                rows={3}
              />
              {errors.description && (
                <span className="group-settings__error">{errors.description}</span>
              )}
            </div>
          </div>
        )}

        {activeTab === 'privacy' && (
          <div className="group-settings__section">
            <h3 className="group-settings__section-title">Group Privacy</h3>
            <div className="group-settings__visibility-options">
              {visibilityOptions.map((option) => (
                <label
                  key={option.value}
                  className={`group-settings__visibility-option ${
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
                  <div className="group-settings__visibility-content">
                    <div className="group-settings__visibility-header">
                      {option.icon}
                      <div>
                        <div className="group-settings__visibility-label">
                          {option.label}
                        </div>
                        <div className="group-settings__visibility-description">
                          {option.description}
                        </div>
                      </div>
                    </div>
                  </div>
                </label>
              ))}
            </div>
          </div>
        )}

        {activeTab === 'permissions' && (
          <div className="group-settings__section">
            <h3 className="group-settings__section-title">Member Permissions</h3>
            <div className="group-settings__settings">
              <label className="group-settings__setting">
                <input
                  type="checkbox"
                  name="settings.allow_member_posts"
                  checked={formData.settings.allow_member_posts}
                  onChange={handleInputChange}
                />
                <div className="group-settings__setting-content">
                  <div className="group-settings__setting-label">
                    Allow members to post
                  </div>
                  <div className="group-settings__setting-description">
                    Members can create posts in this group
                  </div>
                </div>
              </label>

              <label className="group-settings__setting">
                <input
                  type="checkbox"
                  name="settings.require_approval"
                  checked={formData.settings.require_approval}
                  onChange={handleInputChange}
                />
                <div className="group-settings__setting-content">
                  <div className="group-settings__setting-label">
                    Require approval to join
                  </div>
                  <div className="group-settings__setting-description">
                    New members must be approved before joining
                  </div>
                </div>
              </label>

              <label className="group-settings__setting">
                <input
                  type="checkbox"
                  name="settings.enable_discussion"
                  checked={formData.settings.enable_discussion}
                  onChange={handleInputChange}
                />
                <div className="group-settings__setting-content">
                  <div className="group-settings__setting-label">
                    Enable discussion
                  </div>
                  <div className="group-settings__setting-description">
                    Members can comment and interact with posts
                  </div>
                </div>
              </label>
            </div>
          </div>
        )}

        {activeTab === 'danger' && (
          <div className="group-settings__section">
            <h3 className="group-settings__section-title">Danger Zone</h3>
            <div className="group-settings__danger-zone">
              <div className="group-settings__danger-item">
                <div className="group-settings__danger-info">
                  <h4>Delete Group</h4>
                  <p>Permanently delete this group and all its content. This action cannot be undone.</p>
                </div>
                <button
                  type="button"
                  className="group-settings__danger-btn"
                  onClick={handleDeleteGroup}
                >
                  <Trash2 size={16} />
                  Delete Group
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Error Display */}
        {errors.submit && (
          <div className="group-settings__submit-error">
            {errors.submit}
          </div>
        )}

        {/* Actions */}
        <div className="group-settings__actions">
          <button
            type="submit"
            className="group-settings__submit-btn"
            disabled={isSubmitting}
          >
            {isSubmitting ? (
              <>
                <div className="group-settings__spinner"></div>
                Saving...
              </>
            ) : (
              <>
                <Save size={16} />
                Save Changes
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
}
