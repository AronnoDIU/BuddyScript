import { Home, Users, MessageCircle, Bell, Heart, Network, Search, Flag } from 'lucide-react';
import { Link, NavLink } from 'react-router-dom';
import './CommunityNav.css';

export default function CommunityNav() {
  const navItems = [
    { path: '/feed', icon: <Home size={20} />, label: 'Feed' },
    { path: '/groups', icon: <Users size={20} />, label: 'Groups' },
    { path: '/pages', icon: <Flag size={20} />, label: 'Pages' },
    { path: '/messenger', icon: <MessageCircle size={20} />, label: 'Messenger' },
    { path: '/notifications', icon: <Bell size={20} />, label: 'Notifications' },
    { path: '/social', icon: <Network size={20} />, label: 'Social' },
    { path: '/reactions', icon: <Heart size={20} />, label: 'Reactions' },
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
            <NavLink
              key={item.path}
              to={item.path}
              className={({ isActive }) => `community-nav__link${isActive ? ' active' : ''}`}
            >
              {item.icon}
              <span>{item.label}</span>
            </NavLink>
          ))}
        </div>
      </div>

    </nav>
  );
}
