import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { SkeletonCardRows, SkeletonLine } from '../components/Skeleton';
import StatePanel from '../components/StatePanel';

export default function ReactionsPage() {
  const [catalog, setCatalog] = useState({ targetTypes: [], reactionTypes: [] });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;

    api.get('/v1/reactions/catalog')
      .then((response) => {
        if (!active) return;
        setCatalog({
          targetTypes: response.data?.targetTypes || [],
          reactionTypes: response.data?.reactionTypes || [],
        });
      })
      .catch((loadError) => {
        if (!active) return;
        setError(loadError.response?.data?.message || 'Failed to load reaction catalog.');
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
        <h2>Reactions</h2>
        <p>Phase 1 skeleton for multi-reaction support across posts, comments, and replies.</p>
        <Link to="/feed" className="phase1_back_link">Back to Feed</Link>
      </header>

      {loading && (
        <div className="phase1_notice" aria-hidden="true">
          <SkeletonLine width="42%" />
          <SkeletonCardRows rows={3} />
        </div>
      )}
      {error && <StatePanel variant="error" title="Could not load reactions" message={error} className="phase1_notice" />}

      {!loading && !error && (
        <div className="phase1_grid">
          <section className="phase1_card">
            <h3>Target Types</h3>
            {catalog.targetTypes.length === 0 ? (
              <StatePanel variant="empty" title="No target types" compact />
            ) : (
              <div className="phase1_chip_row">
                {catalog.targetTypes.map((item) => <span key={item} className="phase1_chip">{item}</span>)}
              </div>
            )}
          </section>

          <section className="phase1_card">
            <h3>Reaction Types</h3>
            {catalog.reactionTypes.length === 0 ? (
              <StatePanel variant="empty" title="No reaction types" compact />
            ) : (
              <div className="phase1_chip_row">
                {catalog.reactionTypes.map((item) => <span key={item} className="phase1_chip">{item}</span>)}
              </div>
            )}
          </section>
        </div>
      )}
    </div>
  );
}

