import { useState, useEffect } from 'react';
import { Search, Users, Shield, UserCheck, UserMinus, Crown } from 'lucide-react';
import { groupsApi } from '../../api/groups';
import './GroupMembers.css';
export default function GroupMembers({ groupId, members, isAdmin, onMembersUpdated }) {
  const [memberList, setMemberList] = useState(members);
  const [loading, setLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedRole, setSelectedRole] = useState('all');
  useEffect(() => {
    setMemberList(members);
  }, [members]);
  const loadMembers = async (query = searchQuery, role = selectedRole) => {
    if (!groupId) return;
    try {
      setLoading(true);
      const response = await groupsApi.getGroupMembers(groupId, {
        q: query,
        role: role === 'all' ? '' : role,
        limit: 50,
      });
      setMemberList(response.data.members || []);
    } catch (error) {
      console.error('Failed to search members:', error);
    } finally {
      setLoading(false);
    }
  };
  useEffect(() => {
    if (!groupId) return undefined;
    const timeout = setTimeout(() => {
      loadMembers(searchQuery, selectedRole);
    }, 250);
    return () => clearTimeout(timeout);
  }, [groupId, searchQuery, selectedRole]);
  const handleSearch = (query) => {
    setSearchQuery(query);
  };
  const handleSearchInput = (event) => {
    handleSearch(event.target.value);
  };
  const handleRoleFilter = (role) => {
    setSelectedRole(role);
  };
  const handleFilterAll = () => handleRoleFilter('all');
  const handleFilterAdmin = () => handleRoleFilter('admin');
  const handleFilterModerator = () => handleRoleFilter('moderator');
  const handleFilterMember = () => handleRoleFilter('member');
  const handleMemberRoleChange = async (event) => {
    const memberId = event.currentTarget.dataset.memberId;
    await handleRoleChange(memberId, event.target.value);
  };

  const handleMemberRemove = async (event) => {
    const memberId = event.currentTarget.dataset.memberId;
    await handleRemoveMember(memberId);
  };
  const handleRoleChange = async (memberId, newRole) => {
    try {
      await groupsApi.updateMemberRole(groupId, memberId, newRole);
      setMemberList((prev) => prev.map((member) => (
        member.user.id === memberId
          ? { ...member, role: newRole }
          : member
      )));
      onMembersUpdated();
    } catch (error) {
      console.error('Failed to update member role:', error);
    }
  };
  const handleRemoveMember = async (memberId) => {
    if (window.confirm('Are you sure you want to remove this member?')) {
      try {
        await groupsApi.removeMember(groupId, memberId);
        setMemberList((prev) => prev.filter((member) => member.user.id !== memberId));
        onMembersUpdated();
      } catch (error) {
        console.error('Failed to remove member:', error);
      }
    }
  };
  const getRoleIcon = (role) => {
    switch (role) {
      case 'admin':
        return <Crown size={16} />;
      case 'moderator':
        return <Shield size={16} />;
      default:
        return <UserCheck size={16} />;
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
  const getRoleColor = (role) => {
    switch (role) {
      case 'admin':
        return '#fa383e';
      case 'moderator':
        return '#0866ff';
      default:
        return '#65676b';
    }
  };
  const memberCounts = {
    all: memberList.length,
    admin: memberList.filter((m) => m.role === 'admin').length,
    moderator: memberList.filter((m) => m.role === 'moderator').length,
    member: memberList.filter((m) => m.role === 'member').length,
  };

  const renderMember = (member) => (
    <div key={member.id} className="group-members__member">
      <div className="group-members__member-avatar">
        <div className="group-members__member-avatar-placeholder">
          {member.user.displayName.charAt(0).toUpperCase()}
        </div>
      </div>
      <div className="group-members__member-info">
        <div className="group-members__member-name">
          {member.user.displayName}
        </div>
        <div className="group-members__member-meta">
          <div className="group-members__member-role" style={{ color: getRoleColor(member.role) }}>
            {getRoleIcon(member.role)}
            {getRoleText(member.role)}
          </div>
          <div className="group-members__member-joined">
            Joined {new Date(member.joinedAt).toLocaleDateString()}
          </div>
        </div>
      </div>
      {isAdmin && member.role !== 'admin' && (
        <div className="group-members__member-actions">
          <select
            value={member.role}
            data-member-id={member.user.id}
            onChange={handleMemberRoleChange}
            className="group-members__role-select"
          >
            <option value="member">Member</option>
            <option value="moderator">Moderator</option>
          </select>
          <button
            type="button"
            className="group-members__remove-btn"
            data-member-id={member.user.id}
            onClick={handleMemberRemove}
          >
            <UserMinus size={16} />
          </button>
        </div>
      )}
    </div>
  );
  return (
    <div className="group-members">
      <div className="group-members__header">
        <div className="group-members__search">
          <Search size={20} />
          <input
            type="text"
            placeholder="Search members..."
            value={searchQuery}
            onChange={handleSearchInput}
            className="group-members__search-input"
          />
        </div>
        {isAdmin && (
          <button
            className="group-members__invite-btn"
            onClick={() => {}}
            type="button"
          >
            <UserCheck size={16} />
            Invite Members
          </button>
        )}
      </div>
      <div className="group-members__filters">
        <button
          type="button"
          className={`group-members__filter ${selectedRole === 'all' ? 'active' : ''}`}
          onClick={handleFilterAll}
        >
          <Users size={16} />
          All ({memberCounts.all})
        </button>
        <button
          type="button"
          className={`group-members__filter ${selectedRole === 'admin' ? 'active' : ''}`}
          onClick={handleFilterAdmin}
        >
          <Crown size={16} />
          Admins ({memberCounts.admin})
        </button>
        <button
          type="button"
          className={`group-members__filter ${selectedRole === 'moderator' ? 'active' : ''}`}
          onClick={handleFilterModerator}
        >
          <Shield size={16} />
          Moderators ({memberCounts.moderator})
        </button>
        <button
          type="button"
          className={`group-members__filter ${selectedRole === 'member' ? 'active' : ''}`}
          onClick={handleFilterMember}
        >
          <UserCheck size={16} />
          Members ({memberCounts.member})
        </button>
      </div>
      <div className="group-members__list">
        {loading ? (
          <div className="group-members__loading">
            <div className="group-members__spinner"></div>
            <p>Loading members...</p>
          </div>
        ) : memberList.length === 0 ? (
          <div className="group-members__empty">
            <Users size={48} className="group-members__empty-icon" />
            <h3>No Members Found</h3>
            <p>Try adjusting your search or filters</p>
          </div>
        ) : (
          memberList.map(renderMember)
        )}
      </div>
    </div>
  );
}
