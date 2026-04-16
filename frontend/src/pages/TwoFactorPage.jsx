import { useEffect, useState } from 'react';
import { securityApi } from '../api/security';
import { getApiErrorMessage, getApiFieldErrors } from '../api';

export default function TwoFactorPage() {
  const [status, setStatus] = useState({ twoFactorEnabled: false, hasPendingSetup: false });
  const [setup, setSetup] = useState(null);
  const [verifyCode, setVerifyCode] = useState('');
  const [disableCode, setDisableCode] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});

  const loadStatus = async () => {
    setLoading(true);
    setError('');
    setFieldErrors({});
    try {
      const response = await securityApi.getTwoFactorStatus();
      setStatus(response.data || { twoFactorEnabled: false, hasPendingSetup: false });
    } catch (loadError) {
      setError(getApiErrorMessage(loadError, 'Failed to load two-factor status.'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadStatus();
  }, []);

  const startSetup = async () => {
    setError('');
    try {
      const response = await securityApi.initTwoFactorSetup();
      setSetup(response.data || null);
      await loadStatus();
    } catch (setupError) {
      setError(getApiErrorMessage(setupError, 'Failed to start 2FA setup.'));
    }
  };

  const confirmSetup = async (event) => {
    event.preventDefault();
    setError('');
    setFieldErrors({});
    try {
      await securityApi.confirmTwoFactorSetup(verifyCode);
      setVerifyCode('');
      setSetup(null);
      await loadStatus();
    } catch (confirmError) {
      setFieldErrors(getApiFieldErrors(confirmError));
      setError(getApiErrorMessage(confirmError, 'Failed to confirm 2FA setup.'));
    }
  };

  const disableTwoFactor = async (event) => {
    event.preventDefault();
    setError('');
    setFieldErrors({});
    try {
      await securityApi.disableTwoFactor(disableCode);
      setDisableCode('');
      await loadStatus();
    } catch (disableError) {
      setFieldErrors(getApiFieldErrors(disableError));
      setError(getApiErrorMessage(disableError, 'Failed to disable 2FA.'));
    }
  };

  return (
    <div className="profile_page">
      <div className="profile_page_header _feed_inner_area _b_radious6 _padd_t24 _padd_b24 _padd_r24 _padd_l24">
        <div className="profile_page_header_row">
          <div>
            <h4 className="_title5 profile_page_heading">Two-Factor Authentication</h4>
            <p className="profile_meta profile_page_heading_meta">Protect your account with authenticator app verification codes.</p>
          </div>
        </div>
      </div>

      <div className="profile_grid" style={{ marginTop: 16 }}>
        <aside className="profile_sidebar">
          <section className="profile_card">
            <h2 className="profile_section_title">Current status</h2>
            {loading ? (
              <p className="profile_meta">Loading...</p>
            ) : (
              <>
                <p className="profile_meta">Enabled: {status.twoFactorEnabled ? 'Yes' : 'No'}</p>
                <p className="profile_meta">Pending setup: {status.hasPendingSetup ? 'Yes' : 'No'}</p>
              </>
            )}
          </section>

          {!status.twoFactorEnabled && (
            <section className="profile_card" style={{ marginTop: 16 }}>
              <h2 className="profile_section_title">Enable 2FA</h2>
              <button type="button" className="profile_tab profile_tab_active" onClick={startSetup}>Start setup</button>
            </section>
          )}

          {status.twoFactorEnabled && (
            <section className="profile_card" style={{ marginTop: 16 }}>
              <h2 className="profile_section_title">Disable 2FA</h2>
              <form onSubmit={disableTwoFactor} style={{ display: 'grid', gap: 8 }}>
                <input className="form-control" placeholder="Current 2FA code" value={disableCode} onChange={(event) => setDisableCode(event.target.value)} required />
                {fieldErrors.code && <small className="text-danger">{fieldErrors.code}</small>}
                <button type="submit" className="profile_tab">Disable</button>
              </form>
            </section>
          )}
        </aside>

        <section className="profile_timeline">
          {error && <div className="profile_card" style={{ color: '#b42318' }}>{error}</div>}

          {setup && (
            <div className="profile_card">
              <h2 className="profile_section_title">Complete setup</h2>
              <p className="profile_meta">Secret: <code>{setup.secret}</code></p>
              <p className="profile_meta" style={{ wordBreak: 'break-all' }}>otpauth URI: {setup.otpauthUri}</p>
              <form onSubmit={confirmSetup} style={{ display: 'grid', gap: 8, marginTop: 12 }}>
                <input className="form-control" placeholder="6-digit code from authenticator app" value={verifyCode} onChange={(event) => setVerifyCode(event.target.value)} required />
                {fieldErrors.code && <small className="text-danger">{fieldErrors.code}</small>}
                <button type="submit" className="profile_tab profile_tab_active">Confirm setup</button>
              </form>
            </div>
          )}

          <div className="profile_card" style={{ marginTop: 16 }}>
            <h2 className="profile_section_title">How it works</h2>
            <ol className="profile_meta" style={{ marginBottom: 0 }}>
              <li>Start setup to generate your account secret.</li>
              <li>Add the secret to your authenticator app.</li>
              <li>Confirm with a 6-digit code to enable 2FA.</li>
              <li>At login, you will verify a challenge before access tokens are issued.</li>
            </ol>
          </div>
        </section>
      </div>
    </div>
  );
}

