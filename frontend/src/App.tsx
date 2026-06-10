import { BrowserRouter, Routes, Route, Navigate, Link, useLocation } from 'react-router-dom';
import PublicSurveyPage from './pages/public-survey/PublicSurveyPage';
import EditResponsePage from './pages/public-survey/EditResponsePage';
import ShowResponsePage from './pages/public-survey/ShowResponsePage';
import ResponseHistoryPage from './pages/public-survey/ResponseHistoryPage';
import EditRespondentsPage from './pages/public-survey/EditRespondentsPage';
import AboutUsPage from './pages/public-survey/AboutUsPage';
import PublicLayout from './features/survey/PublicLayout';
import AdminShell from './pages/admin/AdminShell';
import SurveyListPage from './pages/admin/SurveyListPage';
import SurveyCreatePage from './pages/admin/SurveyCreatePage';
import SurveyEditPage from './pages/admin/SurveyEditPage';
import ResponseListPage from './pages/admin/ResponseListPage';
import ResponseDetailPage from './pages/admin/ResponseDetailPage';
import ResponseEditPage from './pages/admin/ResponseEditPage';
import RespondentMasterListPage from './pages/admin/RespondentMasterListPage';
import RespondentListPage from './pages/admin/RespondentListPage';
import RespondentDetailPage from './pages/admin/RespondentDetailPage';
import RespondentEditPage from './pages/admin/RespondentEditPage';
import './App.css';
import { LiffProvider, useLiffContext } from './features/liff/LiffContext';
import { ToastProvider } from './features/ui/ToastContext';
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
    location.pathname === '/s' ||
    location.pathname.startsWith('/s/') ||
    location.pathname === '/respondent/edit' ||
    params.has('liff.state') ||
    params.has('code') ||
    params.has('liffClientId') ||
    params.has('liffRedirectUri');

  return (
    <LiffProvider enabled={isLiffRequired}>
      <LiffGate>
        <ToastProvider>
        <Routes>
          {/* Public Survey Routes */}
          <Route element={<PublicLayout />}>
            <Route path="/s" element={<ResponseHistoryPage />} />
            <Route path="/s/:public_id" element={<PublicSurveyPage />} />
            <Route path="/s/:public_id/r/:edit_token" element={<ShowResponsePage />} />
            <Route path="/s/:public_id/r/:edit_token/edit" element={<EditResponsePage />} />
            <Route path="/respondent/edit" element={<EditRespondentsPage />} />
            <Route path="/about-us" element={<AboutUsPage />} />
          </Route>

          {/* Admin Routes */}
          <Route path="/admin" element={<AdminShell />}>
            <Route index element={<Navigate to="surveys" replace />} />
            <Route path="surveys" element={<SurveyListPage />} />
            <Route path="surveys/new" element={<SurveyCreatePage />} />
            <Route path="surveys/:id/edit" element={<SurveyEditPage />} />
            <Route path="surveys/:id/responses" element={<ResponseListPage />} />
            <Route path="surveys/:id/responses/:responseId" element={<ResponseDetailPage />} />
            <Route path="surveys/:id/responses/:responseId/edit" element={<ResponseEditPage />} />
            <Route path="respondent-masters" element={<RespondentMasterListPage />} />
            <Route path="respondents" element={<RespondentListPage />} />
            <Route path="respondents/:id" element={<RespondentDetailPage />} />
            <Route path="respondents/:id/edit" element={<RespondentEditPage />} />
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
        </ToastProvider>
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
