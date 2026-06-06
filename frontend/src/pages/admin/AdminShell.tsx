import React from 'react';
import { Routes, Route, Link } from 'react-router-dom';

const AdminShell: React.FC = () => {
  return (
    <div style={{ display: 'flex', minHeight: '100vh' }}>
      <aside style={{ width: '200px', background: '#f0f0f0', padding: '1rem' }}>
        <nav>
          <ul>
            <li><Link to="/admin">Dashboard</Link></li>
            <li><Link to="/admin/surveys">Surveys</Link></li>
          </ul>
        </nav>
      </aside>
      <main style={{ flex: 1, padding: '1rem' }}>
        <Routes>
          <Route index element={<h1>Admin Dashboard</h1>} />
          <Route path="surveys" element={<h1>Surveys Management</h1>} />
          <Route path="*" element={<h1>404 Not Found</h1>} />
        </Routes>
      </main>
    </div>
  );
};

export default AdminShell;
