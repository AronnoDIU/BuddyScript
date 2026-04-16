import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import AppShell from './components/Navigation/AppShell';
import FeedPage from './pages/FeedPage';
import LoginPage from './pages/LoginPage';
import MessengerPage from './pages/MessengerPage';
import NotificationsPage from './pages/NotificationsPage';
import EventsPage from './pages/EventsPage';
import PagesPage from './pages/PagesPage';
import ProfilePage from './pages/ProfilePage';
import ReactionsPage from './pages/ReactionsPage';
import RegisterPage from './pages/RegisterPage';
import SocialGraphPage from './pages/SocialGraphPage';
import GroupsPage from './pages/GroupsPage';
import './App.css';

export default function App() {
  return (
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
        <Route path="/messenger" element={<MessengerPage />} />
        <Route path="/groups" element={<GroupsPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/feed" replace />} />
    </Routes>
  );
}
