import React, { useState } from 'react';
import { Users, Globe, Lock, EyeOff, MoreHorizontal, UserPlus, UserMinus } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import './GroupCard.css';

export default function GroupCard({ group, onJoin, onLeave, onSelect, showJoinButton = false }) {
  const [isLoading, setIsLoading] = useState(false);
  const [showMenu, setShowMenu] = useState(false);

  const getVisibilityIcon = (visibility) => {
    switch (visibility) {
      case 'public':
        return <Globe size={16} />;
      case 'private':
        return <Users size={16} />;
      case 'secret':
        return <Lock size={16} />;
      default:
        return <EyeOff size={16} />;
    }
  };

  const getVisibilityText = (visibility) => {
    switch (visibility) {
      case 'public':
        return 'Public group';
      case 'private':
        return 'Private group';
      case 'secret':
        return 'Secret group';
      default:
        return 'Group';
    }
  };

  const handleJoin = async (e) => {
    e.stopPropagation();
    try {
      setIsLoading(true);
      await groupsApi.joinGroup(group.id);
      onJoin(group.id);
    } catch (error) {
      console.error('Failed to join group:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleLeave = async (e) => {
    e.stopPropagation();
    if (window.confirm('Are you sure you want to leave this group?')) {
      try {
        setIsLoading(true);
        await groupsApi.leaveGroup(group.id);
        onLeave(group.id);
      } catch (error) {
        console.error('Failed to leave group:', error);
      } finally {
        setIsLoading(false);
      }
    }
  };

  const handleCardClick = () => {
    if (onSelect) {
      onSelect(group);
    }
  };

  const isMember = group.userRole && group.userRole !== 'guest';
  const isAdmin = group.userRole === 'admin';
  const canLeave = isMember && !isAdmin;

  return (
    <div className="group-card" onClick={handleCardClick}>
      <div className="group-card__cover">
        {group.avatarUrl ? (
          <img src={group.avatarUrl} alt={group.name} className="group-card__cover-image" />
        ) : (
          <div className="group-card__cover-placeholder">
            <Users size={32} />
          </div>
        )}
        
        {showMenu && (
          <div className="group-card__menu">
            <button
              className="group-card__menu-btn"
              onClick={(e) => {
                e.stopPropagation();
                setShowMenu(!showMenu);
              }}
            >
              <MoreHorizontal size={20} />
            </button>
            
            {showMenu && (
              <div className="group-card__dropdown">
                <button className="group-card__dropdown-item">
                  Report Group
                </button>
              </div>
            )}
          </div>
        )}
      </div>

      <div className="group-card__content">
        <h3 className="group-card__name">{group.name}</h3>
        
        <div className="group-card__meta">
          <div className="group-card__visibility">
            {getVisibilityIcon(group.visibility)}
            <span>{getVisibilityText(group.visibility)}</span>
          </div>
          
          <div className="group-card__members">
            <Users size={14} />
            <span>{group.memberCount?.toLocaleString() || 0} members</span>
          </div>
        </div>

        {group.description && (
          <p className="group-card__description">{group.description}</p>
        )}

        {group.postCount !== undefined && (
          <div className="group-card__stats">
            <span>{group.postCount} posts</span>
            {group.permissions?.admin && (
              <span>Admin</span>
            )}
          </div>
        )}
      </div>

      <div className="group-card__actions">
        {showJoinButton && !isMember && (
          <button
            className="group-card__join-btn"
            onClick={handleJoin}
            disabled={isLoading}
          >
            <UserPlus size={16} />
            {isLoading ? 'Joining...' : 'Join Group'}
          </button>
        )}

        {isMember && canLeave && (
          <button
            className="group-card__leave-btn"
            onClick={handleLeave}
            disabled={isLoading}
          >
            <UserMinus size={16} />
            {isLoading ? 'Leaving...' : 'Leave'}
          </button>
        )}

        {isAdmin && (
          <button className="group-card__admin-btn">
            <Users size={16} />
            Admin
          </button>
        )}
      </div>
    </div>
  );
}
