import React, { useEffect, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { getRespondentProfile, updateRespondentProfile } from '../../lib/publicApi';
import type { ApiError } from '../../lib/publicApi';

const EditRespondentsPage: React.FC = () => {
  const { isLoggedIn, identify } = useLiffContext();
  const navigate = useNavigate();
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const returnTo = queryParams.get('return_to');

  const [formData, setFormData] = useState({ name: '', email: '' });
  const [lineDisplayName, setLineDisplayName] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{ name?: string; email?: string }>({});

  useEffect(() => {
    document.title = '本人情報編集';

    if (!isLoggedIn) {
      setError('LINEログインが必要です。');
      setIsLoading(false);
      return;
    }

    const fetchProfile = async () => {
      try {
        setIsLoading(true);
        const respondent = await getRespondentProfile(identify);
        setFormData({
          name: respondent.name,
          email: respondent.email,
        });
        setLineDisplayName(respondent.line_display_name || '');
      } catch (err) {
        setError('情報の取得に失敗しました。');
      } finally {
        setIsLoading(false);
      }
    };

    fetchProfile();
  }, [isLoggedIn]);

  const validateReturnTo = (path: string | null): string => {
    if (!path) return '/s';
    // Only allow absolute paths within the app (starting with / but not //)
    if (path.startsWith('/') && !path.startsWith('//')) {
      return path;
    }
    return '/s';
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    setFieldErrors((prev) => ({ ...prev, [name]: undefined }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setIsSubmitting(true);
      setError(null);
      setFieldErrors({});

      await updateRespondentProfile(formData, identify);

      const target = validateReturnTo(returnTo);
      navigate(target);
    } catch (err) {
      if (err instanceof Error) {
        const apiErr = err as ApiError;
        if (apiErr.code === 'VALIDATION_ERROR' && (apiErr as any).details) {
          setFieldErrors((apiErr as any).details);
        } else {
          setError(err.message || '更新に失敗しました。');
        }
      } else {
        setError('予期せぬエラーが発生しました。');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) {
    return (
      <div className="public-container">
        <div style={{ textAlign: 'center', padding: '2rem' }}>読み込み中...</div>
      </div>
    );
  }

  if (error && !isSubmitting) {
    return (
      <div className="public-container">
        <div className="public-card" style={{ textAlign: 'center' }}>
          <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>エラー</h1>
          <p>{error}</p>
          <button
            onClick={() => navigate(validateReturnTo(returnTo))}
            className="public-btn public-btn-secondary"
            style={{ marginTop: '1.5rem' }}
          >
            戻る
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="public-container">
      <div className="public-card">
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', textAlign: 'center' }}>本人情報の編集</h1>

        <div style={{ marginBottom: '2rem', padding: '1rem', background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '8px' }}>
          <p style={{ fontSize: '0.85rem', color: '#6b7280', marginBottom: '0.25rem' }}>LINE表示名</p>
          <p style={{ fontWeight: 'bold', color: '#111827' }}>{lineDisplayName || '未取得'}</p>
          <p style={{ fontSize: '0.75rem', color: '#9ca3af', marginTop: '0.5rem' }}>※LINE表示名は変更できません</p>
        </div>

        <form onSubmit={handleSubmit}>
          <div style={{ marginBottom: '1.5rem' }}>
            <label htmlFor="name" style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#374151' }}>お名前</label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              style={{
                width: '100%',
                padding: '0.75rem',
                border: '1px solid #d1d5db',
                borderRadius: '0.375rem',
                boxSizing: 'border-box',
                borderColor: fieldErrors.name ? '#ef4444' : '#d1d5db'
              }}
            />
            {fieldErrors.name && <div style={{ color: '#ef4444', fontSize: '0.8rem', marginTop: '0.25rem' }}>{fieldErrors.name}</div>}
          </div>

          <div style={{ marginBottom: '2rem' }}>
            <label htmlFor="email" style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#374151' }}>メールアドレス</label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              style={{
                width: '100%',
                padding: '0.75rem',
                border: '1px solid #d1d5db',
                borderRadius: '0.375rem',
                boxSizing: 'border-box',
                borderColor: fieldErrors.email ? '#ef4444' : '#d1d5db'
              }}
            />
            {fieldErrors.email && <div style={{ color: '#ef4444', fontSize: '0.8rem', marginTop: '0.25rem' }}>{fieldErrors.email}</div>}
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            <button
              type="submit"
              disabled={isSubmitting}
              className="public-btn public-btn-primary public-btn-full"
            >
              {isSubmitting ? '保存中...' : '保存する'}
            </button>

            <button
              type="button"
              onClick={() => navigate(validateReturnTo(returnTo))}
              disabled={isSubmitting}
              className="public-btn public-btn-secondary public-btn-full"
            >
              キャンセル
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default EditRespondentsPage;
