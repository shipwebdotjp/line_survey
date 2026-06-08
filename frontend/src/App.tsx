import { BrowserRouter, Routes, Route, Navigate, Link, useLocation } from 'react-router-dom';
import PublicSurveyPage from './pages/public-survey/PublicSurveyPage';
import AdminShell from './pages/admin/AdminShell';
import SurveyListPage from './pages/admin/SurveyListPage';
import SurveyCreatePage from './pages/admin/SurveyCreatePage';
import SurveyEditPage from './pages/admin/SurveyEditPage';
import ResponseListPage from './pages/admin/ResponseListPage';
import ResponseDetailPage from './pages/admin/ResponseDetailPage';
import RespondentMasterListPage from './pages/admin/RespondentMasterListPage';
import './App.css';
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
  const params = new URLSearchParams(location.search);

  const isLiffRequired =
    location.pathname.startsWith('/s/') ||
    params.has('liff.state') ||
    params.has('code') ||
    params.has('liffClientId') ||
    params.has('liffRedirectUri');

  return (
    <LiffProvider enabled={isLiffRequired}>
      <LiffGate>
        <Routes>
          {/* Public Survey Route */}
          <Route
            path="/s/:public_id"
            element={
              <div className="public-survey-root">
                <PublicSurveyPage />
              </div>
            }
          />

          {/* Admin Routes */}
          <Route path="/admin" element={<AdminShell />}>
            <Route index element={<Navigate to="surveys" replace />} />
            <Route path="surveys" element={<SurveyListPage />} />
            <Route path="surveys/new" element={<SurveyCreatePage />} />
            <Route path="surveys/:id/edit" element={<SurveyEditPage />} />
            <Route path="surveys/:id/responses" element={<ResponseListPage />} />
            <Route path="surveys/:id/responses/:responseId" element={<ResponseDetailPage />} />
            <Route path="respondent-masters" element={<RespondentMasterListPage />} />
            <Route path="*" element={<div>404 Not Found</div>} />
          </Route>

          {/* Root Route - Redirect to admin or show a landing page */}
          <Route path="/" element={
            <div className="public-survey-root" style={{ padding: '2rem' }}>
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
