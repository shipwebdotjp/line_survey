import React from 'react';
import AdminButton from '../../../../components/admin/AdminButton';
import type { CreateRespondentMasterRequest } from '../types';

interface Props {
  initialData?: CreateRespondentMasterRequest;
  onSubmit: (data: CreateRespondentMasterRequest) => Promise<void>;
  isSaving: boolean;
  validationErrors?: Record<string, string>;
  onCancel: () => void;
}

const RespondentMasterForm: React.FC<Props> = ({
  initialData,
  onSubmit,
  isSaving,
  validationErrors = {},
  onCancel,
}) => {
  const [formData, setFormData] = React.useState<CreateRespondentMasterRequest>({
    master_code: initialData?.master_code || '',
    line_display_name: initialData?.line_display_name || '',
    name: initialData?.name || '',
    email: initialData?.email || '',
    honorific: initialData?.honorific || '',
    note: initialData?.note || '',
  });

  React.useEffect(() => {
    if (initialData) {
      setFormData({
        master_code: initialData.master_code || '',
        line_display_name: initialData.line_display_name || '',
        name: initialData.name || '',
        email: initialData.email || '',
        honorific: initialData.honorific || '',
        note: initialData.note || '',
      });
    }
  }, [initialData]);

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="admin-form">
      <div className="form-group">
        <label htmlFor="master_code">
          マスターコード <span className="required">*</span>
        </label>
        <input
          type="text"
          id="master_code"
          name="master_code"
          value={formData.master_code}
          onChange={handleChange}
          className={`form-control ${validationErrors.master_code ? 'is-invalid' : ''}`}
          required
        />
        {validationErrors.master_code && (
          <div className="invalid-feedback">{validationErrors.master_code}</div>
        )}
      </div>

      <div className="form-group">
        <label htmlFor="line_display_name">
          LINE表示名 <span className="required">*</span>
        </label>
        <input
          type="text"
          id="line_display_name"
          name="line_display_name"
          value={formData.line_display_name}
          onChange={handleChange}
          className={`form-control ${validationErrors.line_display_name ? 'is-invalid' : ''}`}
          required
        />
        {validationErrors.line_display_name && (
          <div className="invalid-feedback">{validationErrors.line_display_name}</div>
        )}
      </div>

      <div className="form-group">
        <label htmlFor="name">
          氏名 <span className="required">*</span>
        </label>
        <input
          type="text"
          id="name"
          name="name"
          value={formData.name}
          onChange={handleChange}
          className={`form-control ${validationErrors.name ? 'is-invalid' : ''}`}
          required
        />
        {validationErrors.name && (
          <div className="invalid-feedback">{validationErrors.name}</div>
        )}
      </div>

      <div className="form-group">
        <label htmlFor="email">
          メールアドレス <span className="required">*</span>
        </label>
        <input
          type="email"
          id="email"
          name="email"
          value={formData.email}
          onChange={handleChange}
          className={`form-control ${validationErrors.email ? 'is-invalid' : ''}`}
          required
        />
        {validationErrors.email && (
          <div className="invalid-feedback">{validationErrors.email}</div>
        )}
      </div>

      <div className="form-group">
        <label htmlFor="honorific">敬称</label>
        <input
          type="text"
          id="honorific"
          name="honorific"
          value={formData.honorific || ''}
          onChange={handleChange}
          className="form-control"
          placeholder="例: 様"
        />
      </div>

      <div className="form-group">
        <label htmlFor="note">備考</label>
        <textarea
          id="note"
          name="note"
          value={formData.note || ''}
          onChange={handleChange}
          className="form-control"
          rows={3}
        />
      </div>

      <div className="form-actions">
        <AdminButton type="submit" variant="primary" disabled={isSaving}>
          {isSaving ? '保存中...' : '保存する'}
        </AdminButton>
        <AdminButton type="button" onClick={onCancel}>
          キャンセル
        </AdminButton>
      </div>
    </form>
  );
};

export default RespondentMasterForm;
