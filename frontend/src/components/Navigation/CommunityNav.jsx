import React from 'react';
import { Home, Users, Calendar, Settings, Search, Bell } from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import './CommunityNav.css';

export default function CommunityNav() {
  const location = useLocation();

  const navItems = [
    { path: '/feed', icon: <Home size={20} />, label: 'Feed' },
    { path: '/groups', icon: <Users size={20} />, label: 'Groups' },
    { path: '/pages', icon: <Users size={20} />, label: 'Pages' },
    { path: '/events', icon: <Calendar size={20} />, label: 'Events' },
  ];

  return (
    <nav className="community-nav">
      <div className="community-nav__main">
        <Link to="/feed" className="community-nav__logo">
          <h1>BuddyScript</h1>
        </Link>

        <div className="community-nav__search">
          <Search size={20} />
          <input
            type="text"
            placeholder="Search BuddyScript"
            className="community-nav__search-input"
          />
        </div>

        <div className="community-nav__links">
          {navItems.map((item) => (
            <Link
              key={item.path}
              to={item.path}
              className={`community-nav__link ${
                location.pathname === item.path ? 'active' : ''
              }`}
            >
              {item.icon}
              <span>{item.label}</span>
            </Link>
          ))}
        </div>
      </div>

      <div className="community-nav__actions">
        <button className="community-nav__action">
          <Bell size={20} />
        </button>
        <button className="community-nav__action">
          <Settings size={20} />
        </button>
      </div>
    </nav>
  );
}
