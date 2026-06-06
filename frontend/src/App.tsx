import { BrowserRouter, Routes, Route, Navigate, Link } from 'react-router-dom';
import PublicSurveyPage from './pages/public-survey/PublicSurveyPage';
import AdminShell from './pages/admin/AdminShell';

function App() {
  return (
    <BrowserRouter>
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
    </BrowserRouter>
  );
}

export default App;
