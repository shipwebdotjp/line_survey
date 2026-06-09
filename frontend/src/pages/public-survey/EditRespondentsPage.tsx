import React, { useEffect, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { getRespondentProfile, updateRespondentProfile } from '../../lib/publicApi';
import type { ApiError } from '../../lib/publicApi';
import Footer from '../../features/survey/Footer';

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
  }, [isLoggedIn, identify]);

  const validateReturnTo = (path: string | null): string => {
    if (!path) return '/s';
    try {
      const decoded = decodeURIComponent(path);
      // Normalize and tighten validation:
      // 1. Must start with exactly one '/' (rejects //, \, or schemes like http:)
      if (!/^\/([^\/\\]|$)/.test(decoded)) {
        return '/s';
      }
      // 2. Reject percent-encoded slashes/backslashes or schemes after decoding
      if (/[%:]|(\.\.\/)/i.test(decoded)) {
        return '/s';
      }
      // 3. Simple character whitelist for the path itself
      if (!/^[a-zA-Z0-9\/\-\._~?=&%#]+$/.test(decoded)) {
        return '/s';
      }
      return decoded;
    } catch (e) {
      return '/s';
    }
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
        if (apiErr.code === 'VALIDATION_ERROR' && apiErr.details) {
          setFieldErrors(apiErr.details);
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
    return <div style={{ padding: '2rem', textAlign: 'center' }}>読み込み中...</div>;
  }

  if (error && !isSubmitting) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>エラー</h1>
        <p>{error}</p>
        <button
          onClick={() => navigate(validateReturnTo(returnTo))}
          style={{ marginTop: '1rem', padding: '0.5rem 1rem' }}
        >
          戻る
        </button>
      </div>
    );
  }

  return (
    <div style={{ padding: '1rem', maxWidth: '500px', margin: '0 auto' }}>
      <h1 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', textAlign: 'center' }}>本人情報の編集</h1>

      <div style={{ marginBottom: '2rem', padding: '1rem', background: '#f8f9fa', borderRadius: '8px' }}>
        <p style={{ fontSize: '0.85rem', color: '#666', marginBottom: '0.25rem' }}>LINE表示名</p>
        <p style={{ fontWeight: 'bold' }}>{lineDisplayName || '未取得'}</p>
        <p style={{ fontSize: '0.75rem', color: '#999', marginTop: '0.5rem' }}>※LINE表示名は変更できません</p>
      </div>

      <form onSubmit={handleSubmit}>
        <div style={{ marginBottom: '1.5rem' }}>
          <label htmlFor="name" style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem' }}>お名前</label>
          <input
            type="text"
            id="name"
            name="name"
            value={formData.name}
            onChange={handleChange}
            style={{
              width: '100%',
              padding: '0.75rem',
              border: '1px solid #ccc',
              borderRadius: '4px',
              boxSizing: 'border-box',
              borderColor: fieldErrors.name ? '#ff4d4f' : '#ccc'
            }}
          />
          {fieldErrors.name && <div style={{ color: '#ff4d4f', fontSize: '0.8rem', marginTop: '0.25rem' }}>{fieldErrors.name}</div>}
        </div>

        <div style={{ marginBottom: '2rem' }}>
          <label htmlFor="email" style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem' }}>メールアドレス</label>
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            style={{
              width: '100%',
              padding: '0.75rem',
              border: '1px solid #ccc',
              borderRadius: '4px',
              boxSizing: 'border-box',
              borderColor: fieldErrors.email ? '#ff4d4f' : '#ccc'
            }}
          />
          {fieldErrors.email && <div style={{ color: '#ff4d4f', fontSize: '0.8rem', marginTop: '0.25rem' }}>{fieldErrors.email}</div>}
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <button
            type="submit"
            disabled={isSubmitting}
            style={{
              backgroundColor: '#00b900',
              color: 'white',
              padding: '1rem',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer',
              fontSize: '1rem',
              fontWeight: 'bold'
            }}
          >
            {isSubmitting ? '保存中...' : '保存する'}
          </button>

          <button
            type="button"
            onClick={() => navigate(validateReturnTo(returnTo))}
            disabled={isSubmitting}
            style={{
              backgroundColor: 'transparent',
              color: '#666',
              padding: '1rem',
              border: '1px solid #ccc',
              borderRadius: '4px',
              cursor: 'pointer',
              fontSize: '1rem'
            }}
          >
            キャンセル
          </button>
        </div>
      </form>
      <Footer />
    </div>
  );
};

export default EditRespondentsPage;
