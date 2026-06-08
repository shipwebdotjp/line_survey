import { BrowserRouter, Routes, Route, Navigate, Link, useLocation } from 'react-router-dom';
import PublicSurveyPage from './pages/public-survey/PublicSurveyPage';
import AdminShell from './pages/admin/AdminShell';
import { LiffProvider, useLiffContext } from './features/liff/LiffContext';
import LiffError from './features/liff/LiffError';
import React from 'react';

const LiffGate: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { isInitialized, error } = useLiffContext();

  if (error) {
    return <LiffError error={error} />;
  }

  if (!isInitialized) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <p>読み込み中...</p>
      </div>
    );
  }

  return <>{children}</>;
};

const AppContent = () => {
  const location = useLocation();
  const isLiffRequired =
    location.search.includes('liff.state=') ||
    location.pathname.startsWith('/s/');

  return (
    <LiffProvider enabled={isLiffRequired}>
      <LiffGate>
        <Routes>
          {/* Public Survey Route */}
          <Route path="/s/:public_id" element={<PublicSurveyPage />} />

          {/* Admin Routes */}
          <Route path="/admin/*" element={<AdminShell />} />

          {/* Root Route - Redirect to admin or show a landing page */}
          <Route path="/" element={
            <div style={{ padding: '2rem', textAlign: 'center' }}>
              <h1>Survey System</h1>
              <p><Link to="/admin">Go to Admin</Link></p>
            </div>
          } />

          {/* Fallback */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </LiffGate>
    </LiffProvider>
  );
};

function App() {
  return (
    <BrowserRouter>
      <AppContent />
    </BrowserRouter>
  );
}

export default App;
