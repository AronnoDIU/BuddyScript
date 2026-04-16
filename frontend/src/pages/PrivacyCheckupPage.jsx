import { useEffect, useState } from 'react';
import { privacyApi } from '../api/privacy';

export default function PrivacyCheckupPage() {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [checkup, setCheckup] = useState(null);
  const [settings, setSettings] = useState({
    profileVisibility: 'public',
    discoverability: 'everyone',
    allowMessagesFrom: 'connections',
    shareActivityStatus: true,
    adPersonalization: false,
  });

  const loadCheckup = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await privacyApi.getCheckup();
      const payload = response.data || {};
      setCheckup(payload);
      setSettings((prev) => ({ ...prev, ...(payload.settings || {}) }));
    } catch (loadError) {
      setError(loadError?.response?.data?.message || 'Failed to load privacy checkup.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadCheckup();
  }, []);

  const saveSettings = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    try {
      const response = await privacyApi.updateCheckup(settings);
      setCheckup(response.data || null);
    } catch (saveError) {
      setError(saveError?.response?.data?.message || 'Failed to update privacy settings.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="profile_page">
      <div className="profile_page_header _feed_inner_area _b_radious6 _padd_t24 _padd_b24 _padd_r24 _padd_l24">
        <div className="profile_page_header_row">
          <div>
            <h4 className="_title5 profile_page_heading">Privacy Checkup</h4>
            <p className="profile_meta profile_page_heading_meta">Review your account privacy and recommendation checklist.</p>
          </div>
        </div>
      </div>

      <div className="profile_grid" style={{ marginTop: 16 }}>
        <aside className="profile_sidebar">
          <section className="profile_card">
            <h2 className="profile_section_title">Privacy settings</h2>
            <form onSubmit={saveSettings} style={{ display: 'grid', gap: 10 }}>
              <label className="profile_meta">
                Profile visibility
                <select className="form-control" value={settings.profileVisibility} onChange={(event) => setSettings((prev) => ({ ...prev, profileVisibility: event.target.value }))}>
                  <option value="public">Public</option>
                  <option value="connections">Connections</option>
                  <option value="private">Private</option>
                </select>
              </label>

              <label className="profile_meta">
                Discoverability
                <select className="form-control" value={settings.discoverability} onChange={(event) => setSettings((prev) => ({ ...prev, discoverability: event.target.value }))}>
                  <option value="everyone">Everyone</option>
                  <option value="connections">Connections</option>
                  <option value="nobody">Nobody</option>
                </select>
              </label>

              <label className="profile_meta">
                Messages from
                <select className="form-control" value={settings.allowMessagesFrom} onChange={(event) => setSettings((prev) => ({ ...prev, allowMessagesFrom: event.target.value }))}>
                  <option value="everyone">Everyone</option>
                  <option value="connections">Connections</option>
                  <option value="nobody">Nobody</option>
                </select>
              </label>

              <label className="profile_meta" style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                <input type="checkbox" checked={Boolean(settings.shareActivityStatus)} onChange={(event) => setSettings((prev) => ({ ...prev, shareActivityStatus: event.target.checked }))} />
                Share activity status
              </label>

              <label className="profile_meta" style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                <input type="checkbox" checked={Boolean(settings.adPersonalization)} onChange={(event) => setSettings((prev) => ({ ...prev, adPersonalization: event.target.checked }))} />
                Ad personalization
              </label>

              <button type="submit" className="profile_tab profile_tab_active" disabled={saving}>{saving ? 'Saving...' : 'Save settings'}</button>
            </form>
          </section>
        </aside>

        <section className="profile_timeline">
          {error && <div className="profile_card" style={{ color: '#b42318' }}>{error}</div>}

          <div className="profile_card">
            <h2 className="profile_section_title">Checklist</h2>
            {loading ? (
              <p className="profile_meta">Loading...</p>
            ) : (checkup?.checklist || []).length === 0 ? (
              <p className="profile_meta">No recommendations available.</p>
            ) : (
              checkup.checklist.map((item) => (
                <article key={item.key} className="profile_post_card" style={{ marginBottom: 12 }}>
                  <div className="profile_post_header">
                    <strong>{item.title}</strong>
                    <span className="profile_post_visibility">{item.status}</span>
                  </div>
                  <p className="profile_post_content">{item.recommendation}</p>
                </article>
              ))
            )}
          </div>

          <div className="profile_card" style={{ marginTop: 16 }}>
            <h2 className="profile_section_title">Security snapshot</h2>
            <p className="profile_meta">2FA enabled: {checkup?.security?.twoFactorEnabled ? 'Yes' : 'No'}</p>
            <p className="profile_meta">Account created: {checkup?.security?.accountCreatedAt ? new Date(checkup.security.accountCreatedAt).toLocaleString() : 'Unknown'}</p>
          </div>
        </section>
      </div>
    </div>
  );
}

