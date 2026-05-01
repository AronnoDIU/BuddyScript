import { useEffect, lazy, Suspense, useState } from 'react';
import { Navigate, Route, Routes, useLocation } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import AppShell from './components/Navigation/AppShell';
import { trackPageView } from './api/analytics';
import './App.css';

// Lazy load pages for performance
const FeedPage = lazy(() => import('./pages/FeedPage'));
const LoginPage = lazy(() => import('./pages/LoginPage'));
const MessengerPage = lazy(() => import('./pages/MessengerPage'));
const MarketplacePage = lazy(() => import('./pages/MarketplacePage'));
const NotificationsPage = lazy(() => import('./pages/NotificationsPage'));
const EventsPage = lazy(() => import('./pages/EventsPage'));
const PagesPage = lazy(() => import('./pages/PagesPage'));
const PrivacyCheckupPage = lazy(() => import('./pages/PrivacyCheckupPage'));
const ProfilePage = lazy(() => import('./pages/ProfilePage'));
const ReactionsPage = lazy(() => import('./pages/ReactionsPage'));
const RegisterPage = lazy(() => import('./pages/RegisterPage'));
const SocialGraphPage = lazy(() => import('./pages/SocialGraphPage'));
const TrustSafetyPage = lazy(() => import('./pages/TrustSafetyPage'));
const TwoFactorPage = lazy(() => import('./pages/TwoFactorPage'));
const GroupsPage = lazy(() => import('./pages/GroupsPage'));

function AnalyticsTracker() {
  const location = useLocation();

  useEffect(() => {
    trackPageView(location.pathname);
  }, [location]);

  return null;
}

// Modern Loading fallback for Suspense
const LoadingFallback = () => (
  <div className="min-h-screen bg-surface flex items-center justify-center">
    <div className="text-center">
      <div className="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-4 animate-pulse">
        <span className="text-white font-bold text-2xl">B</span>
      </div>
      <div className="flex items-center justify-center gap-2">
        <div className="w-2 h-2 bg-primary-600 rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></div>
        <div className="w-2 h-2 bg-primary-600 rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></div>
        <div className="w-2 h-2 bg-primary-600 rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></div>
      </div>
      <p className="text-secondary mt-4">Loading...</p>
    </div>
  </div>
);

export default function App() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Check if user is authenticated
    const token = localStorage.getItem('auth_token');
    if (token) {
      // You could validate the token here or fetch user data
      setUser({
        id: '1',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        displayName: 'John Doe',
        avatar: null
      });
    }
    setLoading(false);
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    setUser(null);
  };

  if (loading) {
    return <LoadingFallback />;
  }

  return (
    <>
      <AnalyticsTracker />
      <Suspense fallback={<LoadingFallback />}>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route
            element={
              <ProtectedRoute>
                <AppShell user={user} onLogout={handleLogout} />
              </ProtectedRoute>
            }
          >
            <Route path="/feed" element={<FeedPage />} />
            <Route path="/profile/:userId" element={<ProfilePage />} />
            <Route path="/social" element={<SocialGraphPage />} />
            <Route path="/reactions" element={<ReactionsPage />} />
            <Route path="/notifications" element={<NotificationsPage />} />
            <Route path="/pages" element={<PagesPage />} />
            <Route path="/events" element={<EventsPage />} />
            <Route path="/marketplace" element={<MarketplacePage />} />
            <Route path="/trust-safety" element={<TrustSafetyPage />} />
            <Route path="/privacy-checkup" element={<PrivacyCheckupPage />} />
            <Route path="/security/2fa" element={<TwoFactorPage />} />
            <Route path="/messenger" element={<MessengerPage />} />
            <Route path="/groups" element={<GroupsPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/feed" replace />} />
        </Routes>
      </Suspense>
    </>
  );
}
