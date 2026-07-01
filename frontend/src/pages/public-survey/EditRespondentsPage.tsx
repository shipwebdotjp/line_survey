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
  const public_id = queryParams.get('public_id') || undefined;

  const [formData, setFormData] = useState({ name: '', email: '' });
  // const [lineDisplayName, setLineDisplayName] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{ name?: string; email?: string }>({});

  const validateEmail = (email: string): string | undefined => {
    const value = email.trim();
    if (!value) return undefined; // Required check is done by backend or can be added if needed, but per instructions we focus on normalization/format

    // 途中の空白
    if (/\s/.test(value)) {
      return 'メールアドレスの途中に空白が含まれています。空白を削除してください。';
    }

    // カンマ
    if (value.includes(',')) {
      return 'カンマ（,）が含まれています。ドット（.）の打ち間違いではないかご確認ください。';
    }

    // @ の個数
    const atParts = value.split('@');
    if (atParts.length !== 2) {
      return '有効なメールアドレスを入力してください。';
    }

    const localPart = atParts[0];
    const domainPart = atParts[1];

    // @ 前後
    if (!localPart || !domainPart) {
      return '有効なメールアドレスを入力してください。';
    }

    // . の位置 (ドメイン部分)
    if (!domainPart.includes('.')) {
      return '有効なメールアドレスを入力してください。';
    }
    if (domainPart.startsWith('.')) {
      return '有効なメールアドレスを入力してください。';
    }
    if (domainPart.endsWith('.')) {
      return '有効なメールアドレスを入力してください。';
    }
    if (domainPart.includes('..')) {
      return '有効なメールアドレスを入力してください。';
    }

    return undefined;
  };

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
        const respondent = await getRespondentProfile(() => identify(public_id));
        setFormData({
          name: respondent.name,
          email: respondent.email,
        });
        // setLineDisplayName(respondent.line_display_name || '');
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

  const handleEmailBlur = () => {
    const trimmedEmail = formData.email.trim();
    setFormData((prev) => ({ ...prev, email: trimmedEmail }));

    const emailError = validateEmail(trimmedEmail);
    setFieldErrors((prev) => ({ ...prev, email: emailError }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setIsSubmitting(true);
      setError(null);
      setFieldErrors({});

      const normalizedFormData = {
        ...formData,
        name: formData.name.trim(),
        email: formData.email.trim(),
      };

      const emailError = validateEmail(normalizedFormData.email);
      if (emailError) {
        setFieldErrors({ email: emailError });
        setIsSubmitting(false);
        return;
      }

      await updateRespondentProfile(normalizedFormData, () => identify(public_id));

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
        <form onSubmit={handleSubmit} noValidate>
          <div style={{ marginBottom: '1.5rem' }}>
            <label htmlFor="name" style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#374151' }}>お名前</label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              aria-invalid={!!fieldErrors.name}
              aria-describedby={fieldErrors.name ? 'name-error' : undefined}
              style={{
                width: '100%',
                padding: '0.75rem',
                fontSize: '1rem',
                border: '1px solid #d1d5db',
                borderRadius: '0.375rem',
                boxSizing: 'border-box',
                borderColor: fieldErrors.name ? '#ef4444' : '#d1d5db'
              }}
            />
            {fieldErrors.name && (
              <div id="name-error" style={{ color: '#ef4444', fontSize: '0.8rem', marginTop: '0.25rem' }}>
                {fieldErrors.name}
              </div>
            )}
          </div>

          <div style={{ marginBottom: '2rem' }}>
            <label htmlFor="email" style={{ display: 'block', fontWeight: 'bold', marginBottom: '0.5rem', color: '#374151' }}>メールアドレス</label>
            <input
              type="text"
              id="email"
              name="email"
              inputMode="email"
              autoComplete="email"
              value={formData.email}
              onChange={handleChange}
              onBlur={handleEmailBlur}
              aria-invalid={!!fieldErrors.email}
              aria-describedby={fieldErrors.email ? 'email-error' : undefined}
              style={{
                width: '100%',
                padding: '0.75rem',
                fontSize: '1rem',
                border: '1px solid #d1d5db',
                borderRadius: '0.375rem',
                boxSizing: 'border-box',
                borderColor: fieldErrors.email ? '#ef4444' : '#d1d5db'
              }}
            />
            {fieldErrors.email && (
              <div id="email-error" style={{ color: '#ef4444', fontSize: '0.8rem', marginTop: '0.25rem' }}>
                {fieldErrors.email}
              </div>
            )}
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
