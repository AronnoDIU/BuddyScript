import {
  Home,
  Users,
  MessageCircle,
  Bell,
  Heart,
  Network,
  Search,
  Flag,
  CalendarDays,
  Store,
  ShieldAlert,
  ShieldCheck,
  Lock,
  Menu,
  X,
  Settings,
  LogOut,
} from 'lucide-react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { useState } from 'react';

export default function CommunityNav({ user, onLogout }) {
  const location = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const navItems = [
    { path: '/feed', icon: <Home size={20} />, label: 'Feed' },
    { path: '/social', icon: <Users size={20} />, label: 'Social' },
    { path: '/reactions', icon: <Heart size={20} />, label: 'Reactions' },
    { path: '/messenger', icon: <MessageCircle size={20} />, label: 'Messenger' },
    { path: '/notifications', icon: <Bell size={20} />, label: 'Notifications' },
    { path: '/marketplace', icon: <Store size={20} />, label: 'Marketplace' },
    { path: '/events', icon: <CalendarDays size={20} />, label: 'Events' },
    { path: '/pages', icon: <Flag size={20} />, label: 'Pages' },
    { path: '/groups', icon: <Users size={20} />, label: 'Groups' },
    { path: '/trust-safety', icon: <ShieldAlert size={20} />, label: 'Trust & Safety' },
    { path: '/privacy-checkup', icon: <ShieldCheck size={20} />, label: 'Privacy Checkup' },
    { path: '/security/2fa', icon: <Lock size={20} />, label: '2FA' },
  ];

  const handleSearch = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      // Navigate to search results
      console.log('Searching for:', searchQuery);
    }
  };

  return (
    <>
      {/* Mobile sidebar backdrop */}
      {sidebarOpen && (
        <div 
          className="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <div className={`fixed inset-y-0 left-0 z-50 w-72 bg-surface-elevated border-r border-border transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 ${
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      }`}>
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-border">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-lg">B</span>
              </div>
              <h1 className="text-xl font-bold text-primary">BuddyScript</h1>
            </div>
            <button
              className="btn btn-ghost btn-sm lg:hidden"
              onClick={() => setSidebarOpen(false)}
            >
              <X className="w-4 h-4" />
            </button>
          </div>

          {/* Search */}
          <div className="p-4">
            <form onSubmit={handleSearch} className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-tertiary" />
              <input
                type="text"
                placeholder="Search..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="input pl-10 pr-4 py-2 bg-surface border border-border rounded-lg text-sm placeholder-tertiary focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </form>
          </div>

          {/* Navigation */}
          <nav className="flex-1 px-4 space-y-1 overflow-y-auto">
            {navItems.map((item) => {
              const isActive = location.pathname === item.path;
              return (
                <NavLink
                  key={item.path}
                  to={item.path}
                  className={`flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm ${
                    isActive 
                      ? 'bg-primary-100 text-primary-700 font-medium' 
                      : 'text-secondary hover:bg-surface hover:text-primary'
                  }`}
                  onClick={() => setSidebarOpen(false)}
                >
                  {item.icon}
                  <span>{item.label}</span>
                  {item.label === 'Messenger' && (
                    <span className="badge badge-error ml-auto">3</span>
                  )}
                  {item.label === 'Notifications' && (
                    <span className="badge badge-warning ml-auto">5</span>
                  )}
                </NavLink>
              );
            })}
          </nav>

          {/* User Section */}
          {user && (
            <div className="p-4 border-t border-border">
              <div className="flex items-center gap-3 mb-4">
                <div className="avatar avatar-sm">
                  {user.avatar ? (
                    <img src={user.avatar} alt={user.displayName} />
                  ) : (
                    `${user.firstName?.[0]}${user.lastName?.[0]}`
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-primary truncate">
                    {user.displayName || `${user.firstName} ${user.lastName}`}
                  </p>
                  <p className="text-xs text-tertiary truncate">
                    {user.email}
                  </p>
                </div>
              </div>
              
              <div className="flex gap-2">
                <button className="btn btn-ghost btn-sm flex-1">
                  <Settings className="w-4 h-4 mr-2" />
                  Settings
                </button>
                <button 
                  className="btn btn-ghost btn-sm text-accent-600 hover:text-accent-700 hover:bg-accent-50"
                  onClick={onLogout}
                >
                  <LogOut className="w-4 h-4" />
                </button>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Mobile header */}
      <div className="lg:hidden fixed top-0 left-0 right-0 z-30 bg-surface-elevated border-b border-border">
        <div className="flex items-center justify-between p-4">
          <div className="flex items-center gap-3">
            <button
              className="btn btn-ghost btn-sm"
              onClick={() => setSidebarOpen(true)}
            >
              <Menu className="w-4 h-4" />
            </button>
            <div className="flex items-center gap-2">
              <div className="w-6 h-6 bg-gradient-to-br from-primary-500 to-primary-600 rounded flex items-center justify-center">
                <span className="text-white font-bold text-xs">B</span>
              </div>
              <span className="font-semibold text-primary">BuddyScript</span>
            </div>
          </div>
          
          <div className="flex items-center gap-2">
            <button className="btn btn-ghost btn-sm">
              <Search className="w-4 h-4" />
            </button>
            <button className="btn btn-ghost btn-sm relative">
              <Bell className="w-4 h-4" />
              <span className="absolute -top-1 -right-1 w-2 h-2 bg-accent-500 rounded-full"></span>
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
