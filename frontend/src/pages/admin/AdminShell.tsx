import React from 'react';
import { Outlet, NavLink } from 'react-router-dom';

const AdminShell: React.FC = () => {
  return (
    <div className="admin-layout">
      <aside className="admin-sidebar">
        <div className="admin-sidebar-brand">
          Admin Panel
        </div>
        <nav className="admin-nav">
          <ul>
            <li>
              <NavLink
                to="/admin/surveys"
                className={({ isActive }) => isActive ? 'active' : ''}
              >
                アンケート管理
              </NavLink>
            </li>
          </ul>
        </nav>
      </aside>
      <div className="admin-main-container">
        <header className="admin-header">
          <div className="admin-breadcrumb">
            {/* Breadcrumb space */}
            <span>Admin</span> / <span>アンケート管理</span>
          </div>
        </header>
        <main className="admin-content">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default AdminShell;
