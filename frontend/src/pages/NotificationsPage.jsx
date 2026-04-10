import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { SkeletonCardRows, SkeletonLine } from '../components/Skeleton';
import StatePanel from '../components/StatePanel';

export default function NotificationsPage() {
  const [data, setData] = useState({ notifications: [], unreadCount: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [markingAll, setMarkingAll] = useState(false);
  const [markingOneId, setMarkingOneId] = useState('');

  const loadNotifications = () => api.get('/v1/notifications', { params: { limit: 50 } }).then((response) => {
    setData({
      notifications: response.data?.notifications || [],
      unreadCount: response.data?.unreadCount || 0,
    });
  });

  useEffect(() => {
    let active = true;

    loadNotifications()
      .catch((loadError) => {
        if (!active) return;
        setError(loadError.response?.data?.message || 'Failed to load notifications.');
      })
      .finally(() => {
        if (!active) return;
        setLoading(false);
      });

    return () => {
      active = false;
    };
  }, []);

  const markAllRead = async () => {
    setMarkingAll(true);
    setError('');

    try {
      await api.post('/v1/notifications/read-all');
      await loadNotifications();
    } catch (markError) {
      setError(markError.response?.data?.message || 'Failed to mark all notifications as read.');
    } finally {
      setMarkingAll(false);
    }
  };

  const markOneRead = async (id) => {
    setMarkingOneId(id);
    setError('');

    try {
      await api.post(`/v1/notifications/${id}/read`);
      setData((prev) => {
        const notifications = prev.notifications.map((item) => (
          item.id === id ? { ...item, isRead: true } : item
        ));
        const unreadCount = notifications.filter((item) => !item.isRead).length;
        return { notifications, unreadCount };
      });
    } catch (markError) {
      setError(markError.response?.data?.message || 'Failed to mark notification as read.');
    } finally {
      setMarkingOneId('');
    }
  };

  return (
    <div className="phase1_page">
      <header className="phase1_page_header">
        <h2>Notifications</h2>
        <p>Phase 1 skeleton for inbox listing and read/unread notification state.</p>
        <div className="phase1_header_actions">
          <Link to="/feed" className="phase1_back_link">Back to Feed</Link>
          <span className="phase1_unread_badge">{data.unreadCount} unread</span>
          <button type="button" className="phase1_btn" onClick={markAllRead} disabled={markingAll || loading}>
            {markingAll ? 'Marking...' : 'Mark All Read'}
          </button>
        </div>
      </header>

      {loading && (
        <div className="phase1_notice" aria-hidden="true">
          <SkeletonLine width="34%" />
          <SkeletonCardRows rows={4} />
        </div>
      )}
      {error && <StatePanel variant="error" title="Could not load notifications" message={error} className="phase1_notice" />}

      {!loading && !error && (
        <section className="phase1_card">
          <h3>Inbox ({data.unreadCount} unread)</h3>
          {data.notifications.length === 0 ? (
            <StatePanel variant="empty" title="All caught up" message="You have no notifications right now." compact />
          ) : (
            data.notifications.map((item) => (
              <div className="phase1_card_item" key={item.id}>
                <div>
                  <h4>{item.type}</h4>
                  <p>{item.actor?.displayName || 'System'} • {new Date(item.createdAt).toLocaleString()}</p>
                </div>
                <span className={`phase1_status ${item.isRead ? 'phase1_status_accepted' : 'phase1_status_pending'}`}>
                  {item.isRead ? 'read' : 'unread'}
                </span>
                {!item.isRead && (
                  <button
                    type="button"
                    className="phase1_btn phase1_btn_sm"
                    disabled={markingOneId === item.id}
                    onClick={() => markOneRead(item.id)}
                  >
                    {markingOneId === item.id ? 'Saving...' : 'Mark Read'}
                  </button>
                )}
              </div>
            ))
          )}
        </section>
      )}
    </div>
  );
}

