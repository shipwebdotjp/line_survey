import React, { useState } from 'react';
import type { Respondent, IdentifyStatus } from './types';

const DEFAULT_HONORIFICS = ['さん', '様', '先生'];

const parseHonorificOptions = (value: string | undefined): string[] => {
  const parsed = (value ?? '')
    .split(/[,、]/)
    .map((item) => item.trim())
    .filter(Boolean);

  const unique = Array.from(new Set(parsed));
  return unique.length > 0 ? unique : DEFAULT_HONORIFICS;
};

const HONORIFIC_OPTIONS = parseHonorificOptions(import.meta.env.VITE_RESPONDENT_HONORIFICS);
const DEFAULT_HONORIFIC = HONORIFIC_OPTIONS[0] ?? '';

interface RespondentIdentificationProps {
  status: IdentifyStatus;
  respondent: Respondent | null;
  onManualSubmit: (data: { name: string; email: string; honorific: string }) => void;
  isSubmitting: boolean;
  submitError: string | null;
}

const RespondentIdentification: React.FC<RespondentIdentificationProps> = ({
  status,
  respondent,
  onManualSubmit,
  isSubmitting,
  submitError,
}) => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    honorific: DEFAULT_HONORIFIC,
  });
  const [errors, setErrors] = useState<{ name?: string; email?: string }>({});

  const isReadOnly = status === 'existing' || status === 'matched' || status === 'manual_saved';

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    setErrors((prev) => ({ ...prev, [name]: undefined }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const newErrors: { name?: string; email?: string } = {};
    if (!formData.name.trim()) {
      newErrors.name = 'お名前は必須です。';
    }
    if (!formData.email.trim()) {
      newErrors.email = 'メールアドレスは必須です。';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }
    onManualSubmit(formData);
  };

  const displayData = isReadOnly && respondent ? respondent : formData;

  const containerStyle: React.CSSProperties = {
    padding: '1.5rem',
    border: '1px solid #ddd',
    borderRadius: '8px',
    backgroundColor: '#f9f9f9',
    marginBottom: '2rem',
  };

  const fieldStyle: React.CSSProperties = {
    marginBottom: '1rem',
  };

  const labelStyle: React.CSSProperties = {
    display: 'block',
    fontWeight: 'bold',
    marginBottom: '0.25rem',
  };

  const inputStyle: React.CSSProperties = {
    width: '100%',
    padding: '0.5rem',
    border: '1px solid #ccc',
    borderRadius: '4px',
    boxSizing: 'border-box',
  };

  const buttonStyle: React.CSSProperties = {
    backgroundColor: '#00b900',
    color: 'white',
    padding: '0.75rem 1.5rem',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '1rem',
    width: '100%',
  };

  const errorTextStyle: React.CSSProperties = {
    color: '#ff4d4f',
    fontSize: '0.8rem',
    marginTop: '0.25rem',
  };

  if (isReadOnly) {
    return (
      <div style={containerStyle}>
        <h2 style={{ fontSize: '1.2rem', marginBottom: '1rem' }}>ご本人確認</h2>
        <div style={fieldStyle}>
          <label style={labelStyle}>お名前</label>
          <div>{displayData.name} {displayData.honorific}</div>
        </div>
        <div style={fieldStyle}>
          <label style={labelStyle}>メールアドレス</label>
          <div>{displayData.email}</div>
        </div>
        <p style={{ fontSize: '0.9rem', color: '#666', marginTop: '1rem' }}>
          {status === 'manual_saved' ? '情報を保存しました。' : 'ご登録済みの情報で回答を受け付けます。'}
        </p>
      </div>
    );
  }

  return (
    <div style={containerStyle}>
      <h2 style={{ fontSize: '1.2rem', marginBottom: '1rem' }}>お客様情報の入力</h2>
      {submitError && (
        <div style={{ ...errorTextStyle, marginBottom: '1rem', padding: '0.5rem', border: '1px solid #ff4d4f', borderRadius: '4px', backgroundColor: '#fff2f0' }}>
          {submitError}
        </div>
      )}
      <form onSubmit={handleSubmit}>
        <div style={fieldStyle}>
          <label htmlFor="name" style={labelStyle}>お名前</label>
          <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'flex-start' }}>
            <div style={{ flex: 1 }}>
              <input
                type="text"
                id="name"
                name="name"
                value={formData.name}
                onChange={handleChange}
                style={{
                  ...inputStyle,
                  borderColor: errors.name ? '#ff4d4f' : '#ccc',
                }}
              />
              {errors.name && <div style={errorTextStyle}>{errors.name}</div>}
            </div>
            <select
              name="honorific"
              value={formData.honorific}
              onChange={handleChange}
              style={{ ...inputStyle, width: 'auto' }}
            >
              {HONORIFIC_OPTIONS.map((honorific) => (
                <option key={honorific} value={honorific}>
                  {honorific}
                </option>
              ))}
              <option value="">（なし）</option>
            </select>
          </div>
        </div>
        <div style={fieldStyle}>
          <label htmlFor="email" style={labelStyle}>メールアドレス</label>
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            style={{
              ...inputStyle,
              borderColor: errors.email ? '#ff4d4f' : '#ccc',
            }}
          />
          {errors.email && <div style={errorTextStyle}>{errors.email}</div>}
        </div>
        <button type="submit" style={buttonStyle} disabled={isSubmitting}>
          {isSubmitting ? '送信中...' : '次へ進む'}
        </button>
      </form>
    </div>
  );
};

export default RespondentIdentification;
