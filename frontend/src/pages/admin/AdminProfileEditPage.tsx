import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAdminAuth } from '../../features/admin/auth/AdminAuthContext';
import { adminAuthApi } from '../../features/admin/auth/adminAuthApi';
import { useToast } from '../../features/ui/ToastContext';
import AdminButton from '../../components/admin/AdminButton';

const AdminProfileEditPage: React.FC = () => {
  const { user, updateUser } = useAdminAuth();
  const navigate = useNavigate();
  const { showToast } = useToast();

  const [email, setEmail] = useState('');
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (user) {
      setEmail(user.email || '');
    }
  }, [user]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    setError(null);

    try {
      const updatedUser = await adminAuthApi.updateMe(email);
      updateUser(updatedUser);
      showToast('プロフィールを更新しました。', 'success');
    } catch (err: any) {
      console.error('Failed to update profile:', err);
      setError(err.message || '更新に失敗しました。');
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancel = () => {
    navigate(-1);
  };

  if (!user) return null;

  return (
    <div className="admin-profile-edit">
      <div className="admin-page-header">
        <h1>プロフィール編集</h1>
      </div>

      <div className="admin-card">
        <div className="admin-card-body">
          {error && (
            <div className="admin-error-message">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="admin-form" style={{ maxWidth: '100%', border: 'none', padding: 0 }}>
            <div className="form-group">
              <label htmlFor="email">メールアドレス</label>
              <input
                id="email"
                type="email"
                className="form-control"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                disabled={isSaving}
              />
              <span className="form-help">
                ログイン通知や連絡用のメールアドレスです。
              </span>
            </div>

            <div className="form-actions">
              <AdminButton
                type="submit"
                variant="primary"
                disabled={isSaving}
              >
                {isSaving ? '保存中...' : '保存'}
              </AdminButton>
              <AdminButton
                type="button"
                variant="outline"
                onClick={handleCancel}
                disabled={isSaving}
              >
                キャンセル
              </AdminButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default AdminProfileEditPage;
