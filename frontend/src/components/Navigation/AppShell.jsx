import { Outlet, useLocation } from 'react-router-dom';
import CommunityNav from './CommunityNav';

export default function AppShell() {
  const location = useLocation();
  const showShellNav = location.pathname !== '/feed';

  return (
    <div className="app-shell">
      {showShellNav && <CommunityNav />}
      <main className="app-shell__content">
        <Outlet />
      </main>
    </div>
  );
}
