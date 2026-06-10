import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { adminRespondentApi } from '../../features/admin/respondents/adminRespondentApi';
import type { RespondentDetail } from '../../features/admin/respondents/types';

const RespondentEditPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [respondent, setRespondent] = useState<RespondentDetail | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    honorific: '',
  });

  useEffect(() => {
    if (id && /^\d+$/.test(id)) {
      loadRespondent(parseInt(id, 10));
    } else if (id) {
      setError('無効な回答者 ID です。');
      setIsLoading(false);
    }
  }, [id]);

  const loadRespondent = async (respondentId: number) => {
    try {
      setIsLoading(true);
      const data = await adminRespondentApi.get(respondentId);
      setRespondent(data);
      setFormData({
        name: data.name || '',
        email: data.email || '',
        honorific: data.honorific || '',
      });
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    if (validationErrors[name]) {
      setValidationErrors(prev => {
        const next = { ...prev };
        delete next[name];
        return next;
      });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!respondent) return;

    try {
      setIsSaving(true);
      setError(null);
      setValidationErrors({});
      await adminRespondentApi.update(respondent.id, {
        name: formData.name,
        email: formData.email,
        honorific: formData.honorific || null,
      });
      navigate(`/admin/respondents/${respondent.id}`);
    } catch (err: any) {
      if (err.name === 'FetchError' && err.data && err.data.code === 'VALIDATION_ERROR') {
        setValidationErrors(err.data.details || {});
        setError('入力内容を確認してください。');
      } else {
        setError(err.message);
      }
    } finally {
      setIsSaving(false);
    }
  };

  if (isLoading) return <div>読み込み中...</div>;
  if (error && !respondent) return <div className="error-message">エラー: {error}</div>;
  if (!respondent) return <div>回答者が見つかりません。</div>;

  return (
    <div className="admin-respondent-edit">
      <div className="admin-page-header">
        <h1>回答者編集</h1>
      </div>

      <div className="admin-card">
        {error && <div className="error-alert mb-4">{error}</div>}

        <form onSubmit={handleSubmit} className="admin-form">
          <div className="form-group">
            <label>ID</label>
            <input type="text" value={respondent.id} disabled className="form-control" />
          </div>

          <div className="form-group">
            <label>LINE 表示名</label>
            <input type="text" value={respondent.line_display_name} disabled className="form-control" />
            <small className="form-text">LINE側での表示名です。管理画面からは変更できません。</small>
          </div>

          <div className="form-group">
            <label htmlFor="name">氏名 <span className="required">*</span></label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={handleChange}
              className={`form-control ${validationErrors.name ? 'is-invalid' : ''}`}
              required
            />
            {validationErrors.name && <div className="invalid-feedback">{validationErrors.name}</div>}
          </div>

          <div className="form-group">
            <label htmlFor="email">メールアドレス <span className="required">*</span></label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              className={`form-control ${validationErrors.email ? 'is-invalid' : ''}`}
              required
            />
            {validationErrors.email && <div className="invalid-feedback">{validationErrors.email}</div>}
          </div>

          <div className="form-group">
            <label htmlFor="honorific">敬称</label>
            <input
              type="text"
              id="honorific"
              name="honorific"
              value={formData.honorific}
              onChange={handleChange}
              className="form-control"
              placeholder="例: 様"
            />
            <small className="form-text">未入力の場合は null として保存されます。</small>
          </div>

          <div className="form-group">
            <label>LINE User ID</label>
            <input type="text" value={respondent.line_user_id} disabled className="form-control" />
          </div>

          <div className="form-group">
            <label>マスター紐付け ID</label>
            <input type="text" value={respondent.respondent_master_id || '(未紐付け)'} disabled className="form-control" />
          </div>

          <div className="admin-form-actions">
            <button type="submit" className="btn" disabled={isSaving}>
              {isSaving ? '保存中...' : '保存する'}
            </button>
            <Link to={`/admin/respondents/${respondent.id}`} className="btn btn-secondary">キャンセル</Link>
          </div>
        </form>
      </div>
    </div>
  );
};

export default RespondentEditPage;
