import { useEffect, lazy, Suspense } from 'react';
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

// Loading fallback for Suspense
const LoadingFallback = () => (
  <div className="flex items-center justify-center min-h-screen bg-gray-100">
    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
  </div>
);

export default function App() {
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
                <AppShell />
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
