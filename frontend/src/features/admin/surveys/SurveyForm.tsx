import React, { useState } from 'react';
import type { SurveyCreateParams, SurveyStatus } from './types';
import { toDatetimeLocal, fromDatetimeLocal } from './dateUtils';

interface SurveyFormProps {
  initialValues?: Partial<SurveyCreateParams>;
  onSubmit: (values: SurveyCreateParams) => Promise<void>;
  onCancel: () => void;
  submitLabel?: string;
}

const SurveyForm: React.FC<SurveyFormProps> = ({
  initialValues,
  onSubmit,
  onCancel,
  submitLabel = '保存',
}) => {
  const [values, setValues] = useState<SurveyCreateParams>({
    title: initialValues?.title || '',
    description: initialValues?.description || '',
    status: initialValues?.status || 'draft',
    questions_json: initialValues?.questions_json || { pages: [{ name: 'page1', elements: [] }] },
    allow_multiple: initialValues?.allow_multiple ?? false,
    allow_edit: initialValues?.allow_edit ?? false,
    starts_at: toDatetimeLocal(initialValues?.starts_at || null) || null,
    ends_at: toDatetimeLocal(initialValues?.ends_at || null) || null,
    send_confirmation_email: initialValues?.send_confirmation_email ?? true,
    include_answers_in_email: initialValues?.include_answers_in_email ?? true,
  });

  const [questionsRaw, setQuestionsRaw] = useState(
    JSON.stringify(values.questions_json, null, 2)
  );
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>
  ) => {
    const { name, value, type } = e.target;
    const checked = (e.target as HTMLInputElement).checked;

    setValues((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleQuestionsChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    setQuestionsRaw(e.target.value);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    let questionsJson;
    try {
      questionsJson = JSON.parse(questionsRaw);
    } catch (err) {
      setError('JSONの形式が正しくありません。');
      return;
    }

    try {
      setSubmitting(true);
      await onSubmit({
        ...values,
        questions_json: questionsJson,
        starts_at: fromDatetimeLocal(values.starts_at),
        ends_at: fromDatetimeLocal(values.ends_at),
      });
    } catch (err: any) {
      setError(err.message || '保存に失敗しました。');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="admin-form">
      {error && <div className="error-banner">{error}</div>}

      <div className="form-group">
        <label htmlFor="title">タイトル</label>
        <input
          type="text"
          id="title"
          name="title"
          value={values.title}
          onChange={handleChange}
          className="form-control"
          required
        />
      </div>

      <div className="form-group">
        <label htmlFor="description">説明文</label>
        <textarea
          id="description"
          name="description"
          value={values.description || ''}
          onChange={handleChange}
          className="form-control"
          rows={3}
        />
      </div>

      <div className="form-group">
        <label htmlFor="status">ステータス</label>
        <select
          id="status"
          name="status"
          value={values.status}
          onChange={handleChange}
          className="form-control"
        >
          <option value="draft">ドラフト</option>
          <option value="published">公開中</option>
          <option value="closed">終了</option>
          <option value="archived">アーカイブ</option>
        </select>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
        <div className="form-group">
          <label htmlFor="starts_at">開始日時</label>
          <input
            type="datetime-local"
            id="starts_at"
            name="starts_at"
            value={values.starts_at || ''}
            onChange={handleChange}
            className="form-control"
          />
        </div>
        <div className="form-group">
          <label htmlFor="ends_at">終了日時</label>
          <input
            type="datetime-local"
            id="ends_at"
            name="ends_at"
            value={values.ends_at || ''}
            onChange={handleChange}
            className="form-control"
          />
        </div>
      </div>

      <div className="form-group">
        <label className="form-checkbox">
          <input
            type="checkbox"
            name="allow_multiple"
            checked={values.allow_multiple}
            onChange={handleChange}
          />
          複数回の回答を許可する
        </label>
      </div>

      <div className="form-group">
        <label className="form-checkbox">
          <input
            type="checkbox"
            name="allow_edit"
            checked={values.allow_edit}
            onChange={handleChange}
          />
          回答後の編集を許可する
        </label>
      </div>

      <div className="form-group">
        <label className="form-checkbox">
          <input
            type="checkbox"
            name="send_confirmation_email"
            checked={values.send_confirmation_email}
            onChange={handleChange}
          />
          回答完了メールを送信する
        </label>
      </div>

      <div className="form-group">
        <label className="form-checkbox">
          <input
            type="checkbox"
            name="include_answers_in_email"
            checked={values.include_answers_in_email}
            onChange={handleChange}
          />
          メールに回答内容を含める
        </label>
      </div>

      <div className="form-group">
        <label htmlFor="questions_json">アンケート構成 (JSON)</label>
        <textarea
          id="questions_json"
          value={questionsRaw}
          onChange={handleQuestionsChange}
          className="form-control"
          rows={15}
          style={{ fontFamily: 'monospace', fontSize: '0.875rem' }}
        />
        <span className="form-help">SurveyJS形式のJSONを入力してください。</span>
      </div>

      <div className="form-actions">
        <button
          type="submit"
          className="btn btn-primary"
          disabled={submitting}
        >
          {submitting ? '保存中...' : submitLabel}
        </button>
        <button
          type="button"
          onClick={onCancel}
          className="btn btn-outline"
          disabled={submitting}
        >
          キャンセル
        </button>
      </div>
    </form>
  );
};

export default SurveyForm;
