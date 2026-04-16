import { useEffect, useState } from 'react';
import { safetyApi } from '../api/safety';

const INITIAL_REPORT = {
  targetType: 'user',
  targetId: '',
  category: 'abuse',
  reason: '',
};

export default function TrustSafetyPage() {
  const [report, setReport] = useState(INITIAL_REPORT);
  const [reports, setReports] = useState([]);
  const [blockedUsers, setBlockedUsers] = useState([]);
  const [blockUserId, setBlockUserId] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const loadSafetyData = async () => {
    setLoading(true);
    setError('');
    try {
      const [reportsResponse, blocksResponse] = await Promise.all([
        safetyApi.myReports(),
        safetyApi.blockedUsers(),
      ]);
      setReports(reportsResponse.data?.reports || []);
      setBlockedUsers(blocksResponse.data?.blockedUsers || []);
    } catch (loadError) {
      setError(loadError?.response?.data?.message || 'Failed to load trust & safety data.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadSafetyData();
  }, []);

  const submitReport = async (event) => {
    event.preventDefault();
    setError('');
    try {
      await safetyApi.submitReport(report);
      setReport(INITIAL_REPORT);
      await loadSafetyData();
    } catch (submitError) {
      setError(submitError?.response?.data?.message || 'Failed to submit report.');
    }
  };

  const submitBlock = async (event) => {
    event.preventDefault();
    if (!blockUserId.trim()) return;
    setError('');
    try {
      await safetyApi.blockUser(blockUserId.trim());
      setBlockUserId('');
      await loadSafetyData();
    } catch (blockError) {
      setError(blockError?.response?.data?.message || 'Failed to block user.');
    }
  };

  const unblockUser = async (userId) => {
    setError('');
    try {
      await safetyApi.unblockUser(userId);
      await loadSafetyData();
    } catch (unblockError) {
      setError(unblockError?.response?.data?.message || 'Failed to unblock user.');
    }
  };

  return (
    <div className="profile_page">
      <div className="profile_page_header _feed_inner_area _b_radious6 _padd_t24 _padd_b24 _padd_r24 _padd_l24">
        <div className="profile_page_header_row">
          <div>
            <h4 className="_title5 profile_page_heading">Trust & Safety</h4>
            <p className="profile_meta profile_page_heading_meta">Report abuse and manage blocked users.</p>
          </div>
        </div>
      </div>

      <div className="profile_grid" style={{ marginTop: 16 }}>
        <aside className="profile_sidebar">
          <section className="profile_card">
            <h2 className="profile_section_title">Submit report</h2>
            <form onSubmit={submitReport} style={{ display: 'grid', gap: 8 }}>
              <input className="form-control" placeholder="Target type (user/post/group_post/marketplace_listing)" value={report.targetType} onChange={(event) => setReport((prev) => ({ ...prev, targetType: event.target.value }))} required />
              <input className="form-control" placeholder="Target ID" value={report.targetId} onChange={(event) => setReport((prev) => ({ ...prev, targetId: event.target.value }))} required />
              <input className="form-control" placeholder="Category" value={report.category} onChange={(event) => setReport((prev) => ({ ...prev, category: event.target.value }))} required />
              <textarea className="form-control" rows={4} placeholder="Describe what happened" value={report.reason} onChange={(event) => setReport((prev) => ({ ...prev, reason: event.target.value }))} required />
              <button type="submit" className="profile_tab profile_tab_active">Submit Report</button>
            </form>
          </section>

          <section className="profile_card" style={{ marginTop: 16 }}>
            <h2 className="profile_section_title">Block user</h2>
            <form onSubmit={submitBlock} style={{ display: 'flex', gap: 8 }}>
              <input className="form-control" placeholder="User ID" value={blockUserId} onChange={(event) => setBlockUserId(event.target.value)} />
              <button type="submit" className="profile_tab">Block</button>
            </form>
          </section>
        </aside>

        <section className="profile_timeline">
          {error && <div className="profile_card" style={{ color: '#b42318' }}>{error}</div>}

          <div className="profile_card">
            <h2 className="profile_section_title">My reports</h2>
            {loading ? (
              <p className="profile_meta">Loading...</p>
            ) : reports.length === 0 ? (
              <p className="profile_meta">No reports yet.</p>
            ) : (
              reports.map((entry) => (
                <article key={entry.id} className="profile_post_card" style={{ marginBottom: 12 }}>
                  <div className="profile_post_header">
                    <strong>{entry.targetType} • {entry.category}</strong>
                    <span className="profile_post_visibility">{entry.status}</span>
                  </div>
                  <p className="profile_meta profile_post_meta">Target: {entry.targetId}</p>
                  <p className="profile_post_content">{entry.reason}</p>
                  <div className="profile_post_footer">
                    <span>Submitted {new Date(entry.createdAt).toLocaleString()}</span>
                    <span>{entry.resolutionNote || 'Awaiting moderation review'}</span>
                  </div>
                </article>
              ))
            )}
          </div>

          <div className="profile_card" style={{ marginTop: 16 }}>
            <h2 className="profile_section_title">Blocked users</h2>
            {loading ? (
              <p className="profile_meta">Loading...</p>
            ) : blockedUsers.length === 0 ? (
              <p className="profile_meta">No blocked users.</p>
            ) : (
              blockedUsers.map((entry) => (
                <div key={entry.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 }}>
                  <div>
                    <strong>{entry.blockedUser?.displayName || 'Unknown user'}</strong>
                    <p className="profile_meta profile_post_meta">{entry.blockedUser?.id}</p>
                  </div>
                  <button type="button" className="profile_tab" onClick={() => unblockUser(entry.blockedUser?.id)}>Unblock</button>
                </div>
              ))
            )}
          </div>
        </section>
      </div>
    </div>
  );
}

