/* React component utilities and hooks */

export const cn = (...classes) => {
  return classes.filter(Boolean).join(' ');
};

export const getInitials = (user) => {
  if (!user) return '';
  const displayName = user?.displayName || [user?.firstName, user?.lastName].filter(Boolean).join(' ') || user?.email || 'U';
  const parts = displayName.trim().split(/\s+/).filter(Boolean);
  return parts.slice(0, 2).map(p => p.charAt(0).toUpperCase()).join('') || '?';
};

export const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  const now = new Date();
  const diff = now - d;

  const seconds = Math.floor(diff / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);

  if (seconds < 60) return 'just now';
  if (minutes < 60) return `${minutes}m ago`;
  if (hours < 24) return `${hours}h ago`;
  if (days < 7) return `${days}d ago`;

  return d.toLocaleDateString();
};

export const truncateText = (text, length = 96) => {
  if (!text || text.length <= length) return text;
  return text.slice(0, length).trim() + '…';
};

export const formatNumber = (num) => {
  if (!num) return '0';
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num.toString();
};

export const getRandomColor = (seed) => {
  const colors = [
    '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
    '#F7DC6F', '#BB8FCE', '#85FFFF', '#FFB347', '#FF6348'
  ];
  if (!seed) return colors[Math.floor(Math.random() * colors.length)];
  return colors[seed.length % colors.length];
};

export const validateEmail = (email) => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
};

export const validatePassword = (password) => {
  return password && password.length >= 8;
};

export const getAvatarColor = (id) => {
  const colors = ['bg-primary', 'bg-success', 'bg-error', 'bg-warning', 'bg-accent'];
  const hash = id?.charCodeAt(0) || 0;
  return colors[hash % colors.length];
};

export const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

export const asyncDebounce = (fn, delay = 500) => {
  let timeoutId = null;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

export const asyncThrottle = (fn, limit = 500) => {
  let inThrottle;
  return (...args) => {
    if (!inThrottle) {
      fn.apply(this, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
};

