import React, { useState, useRef } from 'react';
import { X, Image, Send, Hash } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import './CreateGroupPost.css';

export default function CreateGroupPost({ group, onClose, onPostCreated }) {
  const [content, setContent] = useState('');
  const [imagePreview, setImagePreview] = useState(null);
  const [imageFile, setImageFile] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const textareaRef = useRef(null);
  const fileInputRef = useRef(null);

  const handleContentChange = (e) => {
    const value = e.target.value;
    setContent(value);
    
    // Auto-resize textarea
    if (textareaRef.current) {
      textareaRef.current.style.height = 'auto';
      textareaRef.current.style.height = `${textareaRef.current.scrollHeight}px`;
    }

    // Clear error for this field
    if (errors.content) {
      setErrors(prev => ({ ...prev, content: null }));
    }
  };

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file type and size
      if (!file.type.startsWith('image/')) {
        setErrors(prev => ({ ...prev, image: 'Please select an image file' }));
        return;
      }

      if (file.size > 5 * 1024 * 1024) { // 5MB
        setErrors(prev => ({ ...prev, image: 'Image must be less than 5MB' }));
        return;
      }

      setImageFile(file);
      const reader = new FileReader();
      reader.onload = (e) => {
        setImagePreview(e.target.result);
      };
      reader.readAsDataURL(file);
      
      if (errors.image) {
        setErrors(prev => ({ ...prev, image: null }));
      }
    }
  };

  const removeImage = () => {
    setImageFile(null);
    setImagePreview(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const extractHashtags = (text) => {
    const hashtags = text.match(/#(\w+)/g);
    return hashtags ? hashtags.map(tag => tag.substring(1)) : [];
  };

  const validateForm = () => {
    const newErrors = {};

    if (!content.trim()) {
      newErrors.content = 'Post content is required';
    } else if (content.trim().length < 1) {
      newErrors.content = 'Post content cannot be empty';
    } else if (content.length > 2000) {
      newErrors.content = 'Post content must be less than 2000 characters';
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
      
      const postData = {
        content: content.trim(),
        image: imageFile
      };

      const response = await groupsApi.createGroupPost(group.id, postData);
      onPostCreated(response.data);
      
    } catch (error) {
      console.error('Failed to create post:', error);
      
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({ submit: 'Failed to create post. Please try again.' });
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

  const hashtags = extractHashtags(content);
  const characterCount = content.length;
  const maxCharacters = 2000;

  return (
    <div className="create-group-post" onClick={handleBackdropClick}>
      <div className="create-group-post__content">
        <div className="create-group-post__header">
          <h2 className="create-group-post__title">Create Post</h2>
          <button
            className="create-group-post__close"
            onClick={handleClose}
            disabled={isSubmitting}
          >
            <X size={20} />
          </button>
        </div>

        <div className="create-group-post__group-info">
          <div className="create-group-post__group-avatar">
            {group.avatarUrl ? (
              <img src={group.avatarUrl} alt={group.name} />
            ) : (
              <div className="create-group-post__group-avatar-placeholder">
                {group.name.charAt(0).toUpperCase()}
              </div>
            )}
          </div>
          <div className="create-group-post__group-details">
            <div className="create-group-post__group-name">{group.name}</div>
            <div className="create-group-post__post-visibility">
              {group.visibility === 'public' ? 'Public' : 'Private'}
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="create-group-post__form">
          <div className="create-group-post__content-section">
            <textarea
              ref={textareaRef}
              value={content}
              onChange={handleContentChange}
              placeholder="What's on your mind?"
              className={`create-group-post__content-input ${errors.content ? 'error' : ''}`}
              maxLength={maxCharacters}
              rows={3}
            />
            
            {errors.content && (
              <span className="create-group-post__error">{errors.content}</span>
            )}

            <div className="create-group-post__content-meta">
              {hashtags.length > 0 && (
                <div className="create-group-post__hashtags">
                  <Hash size={14} />
                  {hashtags.slice(0, 3).map((tag, index) => (
                    <span key={index} className="create-group-post__hashtag">
                      #{tag}
                    </span>
                  ))}
                  {hashtags.length > 3 && (
                    <span className="create-group-post__hashtag-more">
                      +{hashtags.length - 3} more
                    </span>
                  )}
                </div>
              )}
              
              <div className="create-group-post__character-count">
                <span className={characterCount > maxCharacters * 0.9 ? 'warning' : ''}>
                  {characterCount}/{maxCharacters}
                </span>
              </div>
            </div>
          </div>

          {/* Image Upload */}
          <div className="create-group-post__image-section">
            {!imagePreview ? (
              <div className="create-group-post__image-upload">
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/*"
                  onChange={handleImageChange}
                  className="create-group-post__image-input"
                />
                <button
                  type="button"
                  className="create-group-post__image-btn"
                  onClick={() => fileInputRef.current?.click()}
                >
                  <Image size={20} />
                  Add Photo
                </button>
                <span className="create-group-post__image-hint">
                  JPG, PNG, GIF up to 5MB
                </span>
              </div>
            ) : (
              <div className="create-group-post__image-preview">
                <img src={imagePreview} alt="Preview" />
                <button
                  type="button"
                  className="create-group-post__image-remove"
                  onClick={removeImage}
                >
                  <X size={16} />
                </button>
              </div>
            )}
            
            {errors.image && (
              <span className="create-group-post__error">{errors.image}</span>
            )}
          </div>

          {/* Error Display */}
          {errors.submit && (
            <div className="create-group-post__submit-error">
              {errors.submit}
            </div>
          )}

          {/* Actions */}
          <div className="create-group-post__actions">
            <button
              type="button"
              className="create-group-post__cancel-btn"
              onClick={handleClose}
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="create-group-post__submit-btn"
              disabled={isSubmitting || !content.trim()}
            >
              {isSubmitting ? (
                <>
                  <div className="create-group-post__spinner"></div>
                  Posting...
                </>
              ) : (
                <>
                  <Send size={16} />
                  Post
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
