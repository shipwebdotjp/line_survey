import React, { useEffect, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { useAdminAuth } from '../../features/admin/auth/AdminAuthContext';
import { useToast } from '../../features/ui/ToastContext';

const AdminLoginPage: React.FC = () => {
  const { isLoggedIn, idToken, liff } = useLiffContext();
  const { login, user } = useAdminAuth();
  const { showToast } = useToast();
  const navigate = useNavigate();
  const location = useLocation();
  const [isAuthenticating, setIsAuthenticating] = useState(false);

  useEffect(() => {
    document.title = '管理者ログイン';
  }, []);

  useEffect(() => {
    if (user) {
      const params = new URLSearchParams(location.search);
      let from = params.get('from') || (location.state as any)?.from?.pathname || '/admin/surveys';

      // Validate redirect target: Must start with /admin and NOT be /admin/login
      const isValidAdminPath = from.startsWith('/admin') && !from.startsWith('/admin/login');
      if (!isValidAdminPath) {
        from = '/admin/surveys';
      }

      navigate(from, { replace: true });
    }
  }, [user, navigate, location]);

  const handleLogin = async () => {
    if (!isLoggedIn) {
      liff.login({ redirectUri: window.location.href });
      return;
    }

    if (!idToken) {
      showToast('LINE IDトークンが取得できませんでした。', 'error');
      return;
    }

    try {
      setIsAuthenticating(true);
      await login(idToken);
      showToast('ログインしました。', 'success');
    } catch (err) {
      console.error(err);
      showToast(err instanceof Error ? err.message : 'ログインに失敗しました。', 'error');
    } finally {
      setIsAuthenticating(false);
    }
  };

  return (
    <div className="admin-login-container" style={{
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: '100vh',
      backgroundColor: '#f3f4f6'
    }}>
      <div className="admin-card" style={{ width: '100%', maxWidth: '400px', padding: '2rem' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '2rem', textAlign: 'center' }}>Admin Login</h1>

        <div style={{ textAlign: 'center' }}>
          <p style={{ marginBottom: '2rem', color: '#6b7280' }}>
            管理者パネルにアクセスするには、LINEでログインしてください。
          </p>

          <button
            onClick={handleLogin}
            disabled={isAuthenticating}
            className="admin-btn admin-btn-primary"
            style={{ width: '100%', padding: '0.75rem', fontSize: '1rem' }}
          >
            {isAuthenticating ? '認証中...' : 'LINEでログイン'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default AdminLoginPage;
