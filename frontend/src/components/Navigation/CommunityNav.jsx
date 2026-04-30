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
} from 'lucide-react';
import { Link, NavLink } from 'react-router-dom';
import './CommunityNav.css';

export default function CommunityNav() {
  const navItems = [
    { path: '/feed', icon: <Home size={20} aria-hidden="true" />, label: 'Feed' },
    { path: '/groups', icon: <Users size={20} aria-hidden="true" />, label: 'Groups' },
    { path: '/pages', icon: <Flag size={20} aria-hidden="true" />, label: 'Pages' },
    { path: '/events', icon: <CalendarDays size={20} aria-hidden="true" />, label: 'Events' },
    { path: '/marketplace', icon: <Store size={20} aria-hidden="true" />, label: 'Marketplace' },
    { path: '/trust-safety', icon: <ShieldAlert size={20} aria-hidden="true" />, label: 'Trust & Safety' },
    { path: '/privacy-checkup', icon: <ShieldCheck size={20} aria-hidden="true" />, label: 'Privacy Checkup' },
    { path: '/security/2fa', icon: <Lock size={20} aria-hidden="true" />, label: '2FA' },
    { path: '/messenger', icon: <MessageCircle size={20} aria-hidden="true" />, label: 'Messenger' },
    { path: '/notifications', icon: <Bell size={20} aria-hidden="true" />, label: 'Notifications' },
    { path: '/social', icon: <Network size={20} aria-hidden="true" />, label: 'Social' },
    { path: '/reactions', icon: <Heart size={20} aria-hidden="true" />, label: 'Reactions' },
  ];

  return (
    <nav className="community-nav" aria-label="Main community navigation">
      <div className="community-nav__main">
        <Link to="/feed" className="community-nav__logo" aria-label="BuddyScript Home">
          <h1>BuddyScript</h1>
        </Link>

        <div className="community-nav__search" role="search">
          <label htmlFor="search-input" className="sr-only">Search BuddyScript</label> {/* sr-only for visually hidden label */}
          <Search size={20} aria-hidden="true" /> {/* Icon is decorative, label is for input */}
          <input
            id="search-input"
            type="text"
            placeholder="Search BuddyScript"
            className="community-nav__search-input"
            aria-label="Search BuddyScript"
          />
        </div>

        <div className="community-nav__links">
          {navItems.map((item) => (
            <NavLink
              key={item.path}
              to={item.path}
              className={({ isActive }) => `community-nav__link${isActive ? ' active' : ''}`}
              aria-current={({ isActive }) => (isActive ? 'page' : undefined)}
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
