import React from 'react';
import { Outlet } from 'react-router-dom';
import ModernNav from './ModernNav';

const ModernAppShell = ({ user, onLogout }) => {
  return (
    <div className="min-h-screen bg-surface">
      <ModernNav user={user} onLogout={onLogout} />
      
      {/* Main content */}
      <div className="lg:pl-72">
        {/* Mobile spacer */}
        <div className="lg:hidden h-16"></div>
        
        {/* Page content */}
        <main className="container mx-auto px-4 py-6 lg:px-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default ModernAppShell;
