import { Outlet, useLocation } from 'react-router-dom';
import CommunityNav from './CommunityNav';

export default function AppShell({ user, onLogout }) {
  const location = useLocation();
  const showShellNav = location.pathname !== '/feed';

  return (
    <div className="min-h-screen bg-surface">
      {/* Modern Navigation for all pages except feed */}
      {showShellNav && <CommunityNav user={user} onLogout={onLogout} />}
      
      {/* Main content with proper spacing */}
      <main className={`${showShellNav ? 'lg:pl-72' : ''} min-h-screen`}>
        {/* Mobile spacer for navigation */}
        {showShellNav && <div className="lg:hidden h-16"></div>}
        
        {/* Page content */}
        <div className="container mx-auto px-4 py-6 lg:px-8">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
