import { Outlet } from 'react-router-dom';
import CommunityNav from './CommunityNav';

export default function AppShell() {
  return (
    <div className="app-shell">
      <CommunityNav />
      <main className="app-shell__content">
        <Outlet />
      </main>
    </div>
  );
}
