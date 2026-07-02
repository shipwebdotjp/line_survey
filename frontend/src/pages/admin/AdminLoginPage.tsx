import React, { useEffect, useRef, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { useAdminAuth } from '../../features/admin/auth/AdminAuthContext';

const AdminLoginPage: React.FC = () => {
  const { isLoggedIn, idToken } = useLiffContext();
  const { login, user, isLoading } = useAdminAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [isAuthenticating, setIsAuthenticating] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const hasAttemptedLoginRef = useRef(false);

  useEffect(() => {
    document.title = '管理画面ログイン';
  }, []);

  useEffect(() => {
    if (isLoading || !user) {
      return;
    }
    const params = new URLSearchParams(location.search);
    let from = params.get('from') || (location.state as any)?.from?.pathname || '/manage/surveys';

    // Validate redirect target: Must start with /manage and NOT be /manage/login
    const isValidManagePath = from.startsWith('/manage') && !from.startsWith('/manage/login');
    if (!isValidManagePath) {
      from = '/manage/surveys';
    }

    navigate(from, { replace: true });
  }, [user, isLoading, navigate, location]);

  useEffect(() => {
    if (isLoading || user || isAuthenticating || hasAttemptedLoginRef.current) {
      return;
    }

    if (!isLoggedIn) {
      return;
    }

    if (!idToken) {
      hasAttemptedLoginRef.current = true;
      setErrorMessage('LINE IDトークンが取得できませんでした。');
      return;
    }

    hasAttemptedLoginRef.current = true;
    setIsAuthenticating(true);
    setErrorMessage(null);

    const runLogin = async () => {
      try {
        await login(idToken);
      } catch (err) {
        console.error(err);
        setErrorMessage(err instanceof Error ? err.message : 'ログインに失敗しました。');
      } finally {
        setIsAuthenticating(false);
      }
    };

    void runLogin();
  }, [isLoading, user, isLoggedIn, idToken, login, isAuthenticating]);

  return (
    <div className="admin-login-page">
      <div className="admin-login-card">
        <h1 style={{ fontSize: '1.5rem', marginBottom: '2rem', textAlign: 'center' }}>Manage Login</h1>

        <div style={{ textAlign: 'center' }}>
          <p style={{ marginBottom: '2rem', color: '#6b7280' }}>
            管理画面にアクセスするため、LINE認証とログインを処理しています。
          </p>

          {isAuthenticating && (
            <p style={{ marginBottom: '1rem' }}>ログイン中...</p>
          )}

          {errorMessage && (
            <div className="error-banner" style={{ marginBottom: '1rem', textAlign: 'left' }}>
              {errorMessage}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminLoginPage;
