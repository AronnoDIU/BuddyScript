import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { SkeletonCardRows, SkeletonLine } from '../components/Skeleton';
import StatePanel from '../components/StatePanel';

function ConnectionCard({ item }) {
  const user = item.counterparty || {};

  return (
    <div className="phase1_card_item">
      <div>
        <h4>{user.displayName || 'Unknown user'}</h4>
        <p>{user.email || 'No email available'}</p>
      </div>
      <span className={`phase1_status phase1_status_${item.status}`}>{item.status}</span>
    </div>
  );
}

export default function SocialGraphPage() {
  const [data, setData] = useState({ incomingRequests: [], outgoingRequests: [], friends: [] });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [actioningId, setActioningId] = useState('');

  const refreshOverview = () => api.get('/v1/social/overview').then((response) => {
    setData({
      incomingRequests: response.data?.incomingRequests || [],
      outgoingRequests: response.data?.outgoingRequests || [],
      friends: response.data?.friends || [],
    });
  });

  const respondRequest = async (id, status) => {
    setActioningId(id);
    setError('');

    try {
      await api.post(`/v1/social/requests/${id}/respond`, { status });
      await refreshOverview();
    } catch (actionError) {
      setError(actionError.response?.data?.message || 'Failed to respond to friend request.');
    } finally {
      setActioningId('');
    }
  };

  useEffect(() => {
    let active = true;

    api.get('/v1/social/overview')
      .then((response) => {
        if (!active) return;
        setData({
          incomingRequests: response.data?.incomingRequests || [],
          outgoingRequests: response.data?.outgoingRequests || [],
          friends: response.data?.friends || [],
        });
      })
      .catch((loadError) => {
        if (!active) return;
        setError(loadError.response?.data?.message || 'Failed to load social graph overview.');
      })
      .finally(() => {
        if (!active) return;
        setLoading(false);
      });

    return () => {
      active = false;
    };
  }, []);

  return (
    <div className="phase1_page">
      <header className="phase1_page_header">
        <h2>Social Graph</h2>
        <p>Phase 1 skeleton for friend requests, friend lists, and accepted connections.</p>
        <Link to="/feed" className="phase1_back_link">Back to Feed</Link>
      </header>

      {loading && (
        <div className="phase1_notice" aria-hidden="true">
          <SkeletonLine width="38%" />
          <SkeletonCardRows rows={5} />
        </div>
      )}
      {error && <StatePanel variant="error" title="Could not load social graph" message={error} className="phase1_notice" />}

      {!loading && !error && (
        <div className="phase1_grid">
          <section className="phase1_card">
            <h3>Incoming Requests ({data.incomingRequests.length})</h3>
            {data.incomingRequests.length === 0 ? <StatePanel variant="empty" title="No incoming requests" compact /> : data.incomingRequests.map((item) => (
              <div key={item.id} className="phase1_social_request_row">
                <ConnectionCard item={item} />
                <div className="phase1_social_request_actions">
                  <button
                    type="button"
                    className="phase1_btn"
                    disabled={actioningId === item.id}
                    onClick={() => respondRequest(item.id, 'accepted')}
                  >
                    Accept
                  </button>
                  <button
                    type="button"
                    className="phase1_btn phase1_btn_danger"
                    disabled={actioningId === item.id}
                    onClick={() => respondRequest(item.id, 'rejected')}
                  >
                    Reject
                  </button>
                </div>
              </div>
            ))}
          </section>

          <section className="phase1_card">
            <h3>Outgoing Requests ({data.outgoingRequests.length})</h3>
            {data.outgoingRequests.length === 0 ? <StatePanel variant="empty" title="No outgoing requests" compact /> : data.outgoingRequests.map((item) => <ConnectionCard key={item.id} item={item} />)}
          </section>

          <section className="phase1_card">
            <h3>Friends ({data.friends.length})</h3>
            {data.friends.length === 0 ? <StatePanel variant="empty" title="No friends yet" compact /> : data.friends.map((item) => <ConnectionCard key={item.id} item={item} />)}
          </section>
        </div>
      )}
    </div>
  );
}

