import { BrowserRouter, Routes, Route, Navigate, useLocation, Outlet } from 'react-router-dom';
import PublicHomePage from './pages/public-home/PublicHomePage';
import PublicSurveyPage from './pages/public-survey/PublicSurveyPage';
import EditResponsePage from './pages/public-survey/EditResponsePage';
import ShowResponsePage from './pages/public-survey/ShowResponsePage';
import ResponseHistoryPage from './pages/public-survey/ResponseHistoryPage';
import EditRespondentsPage from './pages/public-survey/EditRespondentsPage';
import PrivacyPolicyPage from './pages/public-survey/PrivacyPolicyPage';
import PublicLayout from './features/survey/PublicLayout';
import AdminShell from './pages/admin/AdminShell';
import SurveyListPage from './pages/admin/SurveyListPage';
import SurveyCreatePage from './pages/admin/SurveyCreatePage';
import SurveyEditPage from './pages/admin/SurveyEditPage';
import SurveySummaryPage from './pages/admin/SurveySummaryPage';
import ResponseListPage from './pages/admin/ResponseListPage';
import ResponseDetailPage from './pages/admin/ResponseDetailPage';
import ResponseEditPage from './pages/admin/ResponseEditPage';
import ResponseDraftListPage from './pages/admin/ResponseDraftListPage';
import ResponseDraftDetailPage from './pages/admin/ResponseDraftDetailPage';
import RespondentMasterListPage from './pages/admin/RespondentMasterListPage';
import RespondentMasterCreatePage from './pages/admin/RespondentMasterCreatePage';
import RespondentMasterEditPage from './pages/admin/RespondentMasterEditPage';
import RespondentListPage from './pages/admin/RespondentListPage';
import RespondentDetailPage from './pages/admin/RespondentDetailPage';
import RespondentEditPage from './pages/admin/RespondentEditPage';
import AdminProfileEditPage from './pages/admin/AdminProfileEditPage';
import AdminLoginPage from './pages/admin/AdminLoginPage';
import './App.css';
import { LiffProvider, useLiffContext } from './features/liff/LiffContext';
import { ToastProvider } from './features/ui/ToastContext';
import { ConfirmProvider } from './features/ui/ConfirmContext';
import { AdminAuthProvider } from './features/admin/auth/AdminAuthContext';
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
    location.pathname.startsWith('/manage/login') ||
    params.has('liff.state') ||
    params.has('code') ||
    params.has('liffClientId') ||
    params.has('liffRedirectUri');

  return (
    <LiffProvider enabled={isLiffRequired}>
      <LiffGate>
        <ConfirmProvider>
          <ToastProvider>
            <Routes>
          <Route element={<PublicLayout />}>
            <Route index element={<PublicHomePage />} />
            <Route path="s" element={<ResponseHistoryPage />} />
            <Route path="s/:public_id" element={<PublicSurveyPage />} />
            <Route path="s/:public_id/r/:edit_token" element={<ShowResponsePage />} />
            <Route path="s/:public_id/r/:edit_token/edit" element={<EditResponsePage />} />
            <Route path="respondent/edit" element={<EditRespondentsPage />} />
            <Route path="privacy-policy" element={<PrivacyPolicyPage />} />
            <Route path="about-us" element={<Navigate to="/privacy-policy" replace />} />
          </Route>

          <Route path="/manage" element={<AdminAuthProvider><Outlet /></AdminAuthProvider>}>
            <Route path="login" element={<AdminLoginPage />} />
            <Route element={<AdminShell />}>
              <Route index element={<Navigate to="surveys" replace />} />
              <Route path="surveys" element={<SurveyListPage />} />
              <Route path="surveys/new" element={<SurveyCreatePage />} />
              <Route path="surveys/:id/edit" element={<SurveyEditPage />} />
              <Route path="surveys/:id/responses" element={<ResponseListPage />} />
              <Route path="surveys/:id/summary" element={<SurveySummaryPage />} />
              <Route path="surveys/:id/responses/:responseId" element={<ResponseDetailPage />} />
              <Route path="surveys/:id/responses/:responseId/edit" element={<ResponseEditPage />} />
              <Route path="response-drafts" element={<ResponseDraftListPage />} />
              <Route path="response-drafts/:id" element={<ResponseDraftDetailPage />} />
              <Route path="respondent-masters" element={<RespondentMasterListPage />} />
              <Route path="respondent-masters/new" element={<RespondentMasterCreatePage />} />
              <Route path="respondent-masters/:id/edit" element={<RespondentMasterEditPage />} />
              <Route path="respondents" element={<RespondentListPage />} />
              <Route path="respondents/:id" element={<RespondentDetailPage />} />
              <Route path="respondents/:id/edit" element={<RespondentEditPage />} />
              <Route path="profile/edit" element={<AdminProfileEditPage />} />
              <Route path="*" element={<div>404 Not Found</div>} />
            </Route>
          </Route>

              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </ToastProvider>
        </ConfirmProvider>
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
