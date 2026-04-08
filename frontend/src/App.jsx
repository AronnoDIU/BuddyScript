import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import FeedPage from './pages/FeedPage';
import LoginPage from './pages/LoginPage';
import NotificationsPage from './pages/NotificationsPage';
import ProfilePage from './pages/ProfilePage';
import ReactionsPage from './pages/ReactionsPage';
import RegisterPage from './pages/RegisterPage';
import SocialGraphPage from './pages/SocialGraphPage';
import './App.css';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route
        path="/feed"
        element={
          <ProtectedRoute>
            <FeedPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/profile/:userId"
        element={
          <ProtectedRoute>
            <ProfilePage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/social"
        element={
          <ProtectedRoute>
            <SocialGraphPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/reactions"
        element={
          <ProtectedRoute>
            <ReactionsPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/notifications"
        element={
          <ProtectedRoute>
            <NotificationsPage />
          </ProtectedRoute>
        }
      />
      <Route path="*" element={<Navigate to="/feed" replace />} />
    </Routes>
  );
}
