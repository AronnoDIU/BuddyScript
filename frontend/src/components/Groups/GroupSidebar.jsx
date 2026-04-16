import { useState, useEffect } from 'react';
import { X, Users, Settings, Globe, Lock, EyeOff, UserPlus, UserMinus, Shield, UserCheck } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import GroupFeed from './GroupFeed.jsx';
import GroupMembers from './GroupMembers.jsx';
import GroupSettings from './GroupSettings.jsx';
import './GroupSidebar.css';

export default function GroupSidebar({ group, onClose, onGroupUpdated }) {
  const [activeTab, setActiveTab] = useState('posts');
  const [groupDetails, setGroupDetails] = useState(group);
  const [members, setMembers] = useState([]);

  useEffect(() => {
    if (group) {
      setGroupDetails(group);
      fetchMembers();
    }
  }, [group?.id]);

  const fetchMembers = async () => {
    if (!group) return;
    
    try {
      const response = await groupsApi.getGroupMembers(group.id, { limit: 20 });
      setMembers(response.data.members || []);
    } catch (error) {
      console.error('Failed to fetch members:', error);
    }
  };

  const handleLeaveGroup = async () => {
    if (!groupDetails || window.confirm('Are you sure you want to leave this group?')) {
      try {
        await groupsApi.leaveGroup(groupDetails.id);
        onClose();
        onGroupUpdated();
      } catch (error) {
        console.error('Failed to leave group:', error);
      }
    }
  };

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

  const getRoleIcon = (role) => {
    switch (role) {
      case 'admin':
        return <Shield size={14} />;
      case 'moderator':
        return <UserCheck size={14} />;
      default:
        return <Users size={14} />;
    }
  };

  const getRoleText = (role) => {
    switch (role) {
      case 'admin':
        return 'Admin';
      case 'moderator':
        return 'Moderator';
      default:
        return 'Member';
    }
  };

  if (!groupDetails) return null;

  return (
    <div className="group-sidebar">
      <div className="group-sidebar__header">
        <div className="group-sidebar__cover">
          {groupDetails.avatarUrl ? (
            <img src={groupDetails.avatarUrl} alt={groupDetails.name} className="group-sidebar__cover-image" />
          ) : (
            <div className="group-sidebar__cover-placeholder">
              <Users size={48} />
            </div>
          )}
          <button className="group-sidebar__close" onClick={onClose}>
            <X size={20} />
          </button>
        </div>

        <div className="group-sidebar__info">
          <h2 className="group-sidebar__name">{groupDetails.name}</h2>
          
          <div className="group-sidebar__meta">
            <div className="group-sidebar__visibility">
              {getVisibilityIcon(groupDetails.visibility)}
              <span>{getVisibilityText(groupDetails.visibility)}</span>
            </div>
            
            <div className="group-sidebar__stats">
              <Users size={14} />
              <span>{groupDetails.memberCount?.toLocaleString() || 0} members</span>
              <span>·</span>
              <span>{groupDetails.postCount || 0} posts</span>
            </div>

            {groupDetails.userRole && (
              <div className="group-sidebar__role">
                {getRoleIcon(groupDetails.userRole)}
                <span>{getRoleText(groupDetails.userRole)}</span>
              </div>
            )}
          </div>

          {groupDetails.description && (
            <p className="group-sidebar__description">{groupDetails.description}</p>
          )}

          <div className="group-sidebar__actions">
            {groupDetails.userRole ? (
              <>
                <button className="group-sidebar__action-btn primary">
                  <Users size={16} />
                  Invite Members
                </button>
                
                {groupDetails.permissions?.post && (
                  <button className="group-sidebar__action-btn">
                    Create Post
                  </button>
                )}
                
                {groupDetails.permissions?.admin && (
                  <button className="group-sidebar__action-btn">
                    <Settings size={16} />
                    Settings
                  </button>
                )}
                
                {groupDetails.userRole !== 'admin' && (
                  <button 
                    className="group-sidebar__action-btn danger"
                    onClick={handleLeaveGroup}
                  >
                    <UserMinus size={16} />
                    Leave Group
                  </button>
                )}
              </>
            ) : (
              <button className="group-sidebar__join-btn">
                <UserPlus size={16} />
                Join Group
              </button>
            )}
          </div>
        </div>
      </div>

      <div className="group-sidebar__tabs">
        <button
          className={`group-sidebar__tab ${activeTab === 'posts' ? 'active' : ''}`}
          onClick={() => setActiveTab('posts')}
        >
          Posts
        </button>
        <button
          className={`group-sidebar__tab ${activeTab === 'members' ? 'active' : ''}`}
          onClick={() => setActiveTab('members')}
        >
          Members
        </button>
        <button
          className={`group-sidebar__tab ${activeTab === 'about' ? 'active' : ''}`}
          onClick={() => setActiveTab('about')}
        >
          About
        </button>
        {groupDetails.permissions?.admin && (
          <button
            className={`group-sidebar__tab ${activeTab === 'settings' ? 'active' : ''}`}
            onClick={() => setActiveTab('settings')}
          >
            <Settings size={16} />
            Settings
          </button>
        )}
      </div>

      <div className="group-sidebar__content">
        {activeTab === 'posts' && (
          <GroupFeed 
            group={groupDetails} 
            permissions={groupDetails.permissions}
            onPostCreated={() => {
              // Refresh group stats
              onGroupUpdated();
            }}
          />
        )}

        {activeTab === 'members' && (
          <GroupMembers 
            groupId={groupDetails.id}
            members={members}
            isAdmin={groupDetails.permissions?.admin}
            onMembersUpdated={fetchMembers}
          />
        )}

        {activeTab === 'about' && (
          <div className="group-sidebar__about">
            <div className="group-sidebar__about-section">
              <h3>Group Information</h3>
              <div className="group-sidebar__about-item">
                <strong>Visibility:</strong> {getVisibilityText(groupDetails.visibility)}
              </div>
              <div className="group-sidebar__about-item">
                <strong>Created:</strong> {new Date(groupDetails.createdAt).toLocaleDateString()}
              </div>
              <div className="group-sidebar__about-item">
                <strong>Members:</strong> {groupDetails.memberCount?.toLocaleString() || 0}
              </div>
            </div>

            {groupDetails.description && (
              <div className="group-sidebar__about-section">
                <h3>Description</h3>
                <p>{groupDetails.description}</p>
              </div>
            )}

            <div className="group-sidebar__about-section">
              <h3>Group Rules</h3>
              <ul>
                <li>Be respectful and kind to all members</li>
                <li>Stay on topic and keep discussions relevant</li>
                <li>No spam or promotional content</li>
                <li>Protect everyone&apos;s privacy and personal information</li>
              </ul>
            </div>
          </div>
        )}

        {activeTab === 'settings' && groupDetails.permissions?.admin && (
          <GroupSettings 
            group={groupDetails}
            onGroupUpdated={(updatedGroup) => {
              setGroupDetails(updatedGroup);
              onGroupUpdated();
            }}
          />
        )}
      </div>
    </div>
  );
}
