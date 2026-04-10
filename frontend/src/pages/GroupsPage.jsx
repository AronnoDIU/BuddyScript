import { useState, useEffect } from 'react';
import { Search, Plus, Users, Settings } from 'lucide-react';
import { groupsApi } from '../api/groups';
import CreateGroupModal from '../components/Groups/CreateGroupModal';
import GroupCard from '../components/Groups/GroupCard';
import GroupSidebar from '../components/Groups/GroupSidebar';
import './GroupsPage.css';

export default function GroupsPage() {
  const [groups, setGroups] = useState([]);
  const [publicGroups, setPublicGroups] = useState([]);
  const [selectedGroup, setSelectedGroup] = useState(null);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [activeTab, setActiveTab] = useState('my-groups');

  useEffect(() => {
    fetchGroups();
    fetchPublicGroups();
  }, []);

  const fetchGroups = async (query = '') => {
    try {
      setLoading(true);
      const response = await groupsApi.getGroups({ q: query, limit: 20 });
      setGroups(response.data.groups || []);
    } catch (error) {
      console.error('Failed to fetch groups:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchPublicGroups = async () => {
    try {
      const response = await groupsApi.getPublicGroups({ limit: 12 });
      setPublicGroups(response.data.groups || []);
    } catch (error) {
      console.error('Failed to fetch public groups:', error);
    }
  };

  const handleSearch = (e) => {
    const query = e.target.value;
    setSearchQuery(query);
    fetchGroups(query);
  };

  const handleGroupCreated = (newGroup) => {
    setGroups([newGroup.group, ...groups]);
    setShowCreateModal(false);
  };

  const handleGroupJoined = () => {
    fetchGroups();
  };

  const handleGroupLeft = (groupId) => {
    setGroups(groups.filter(g => g.id !== groupId));
    if (selectedGroup?.id === groupId) {
      setSelectedGroup(null);
    }
  };

  return (
    <div className="groups-page">
      <div className="groups-page__header">
        <div className="groups-page__header-content">
          <div className="groups-page__title-section">
            <h1 className="groups-page__title">Groups</h1>
            <p className="groups-page__subtitle">
              Connect with people who share your interests
            </p>
          </div>
          
          <div className="groups-page__actions">
            <div className="groups-page__search">
              <Search className="groups-page__search-icon" size={20} />
              <input
                type="text"
                placeholder="Search groups..."
                value={searchQuery}
                onChange={handleSearch}
                className="groups-page__search-input"
              />
            </div>
            
            <button
              onClick={() => setShowCreateModal(true)}
              className="groups-page__create-btn"
            >
              <Plus size={20} />
              Create Group
            </button>
          </div>
        </div>

        <div className="groups-page__tabs">
          <button
            className={`groups-page__tab ${activeTab === 'my-groups' ? 'active' : ''}`}
            onClick={() => setActiveTab('my-groups')}
          >
            Your Groups
          </button>
          <button
            className={`groups-page__tab ${activeTab === 'discover' ? 'active' : ''}`}
            onClick={() => setActiveTab('discover')}
          >
            Discover
          </button>
          <button
            className={`groups-page__tab ${activeTab === 'invites' ? 'active' : ''}`}
            onClick={() => setActiveTab('invites')}
          >
            Invites
          </button>
        </div>
      </div>

      <div className="groups-page__content">
        <div className="groups-page__main">
          {activeTab === 'my-groups' && (
            <>
              {loading ? (
                <div className="groups-page__loading">
                  <div className="groups-page__spinner"></div>
                  <p>Loading your groups...</p>
                </div>
              ) : groups.length === 0 ? (
                <div className="groups-page__empty">
                  <Users size={48} className="groups-page__empty-icon" />
                  <h3>No Groups Yet</h3>
                  <p>Join or create groups to connect with communities</p>
                  <button
                    onClick={() => setShowCreateModal(true)}
                    className="groups-page__empty-action"
                  >
                    Create Your First Group
                  </button>
                </div>
              ) : (
                <div className="groups-page__grid">
                  {groups.map((group) => (
                    <GroupCard
                      key={group.id}
                      group={group}
                      onJoin={handleGroupJoined}
                      onLeave={handleGroupLeft}
                      onSelect={setSelectedGroup}
                    />
                  ))}
                </div>
              )}
            </>
          )}

          {activeTab === 'discover' && (
            <div className="groups-page__discover">
              <h2 className="groups-page__section-title">Suggested Groups</h2>
              <div className="groups-page__grid">
                {publicGroups.map((group) => (
                  <GroupCard
                    key={group.id}
                    group={group}
                    onJoin={handleGroupJoined}
                    onLeave={handleGroupLeft}
                    onSelect={setSelectedGroup}
                    showJoinButton={true}
                  />
                ))}
              </div>
            </div>
          )}

          {activeTab === 'invites' && (
            <div className="groups-page__empty">
              <Settings size={48} className="groups-page__empty-icon" />
              <h3>No Pending Invites</h3>
              <p>You don&apos;t have any group invitations right now</p>
            </div>
          )}
        </div>

        {selectedGroup && (
          <GroupSidebar
            group={selectedGroup}
            onClose={() => setSelectedGroup(null)}
            onGroupUpdated={fetchGroups}
          />
        )}
      </div>

      {showCreateModal && (
        <CreateGroupModal
          onClose={() => setShowCreateModal(false)}
          onGroupCreated={handleGroupCreated}
        />
      )}
    </div>
  );
}
