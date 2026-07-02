import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { adminRespondentMasterApi } from '../../features/admin/respondent-masters/adminRespondentMasterApi';
import type { CreateRespondentMasterRequest } from '../../features/admin/respondent-masters/types';
import RespondentMasterForm from '../../features/admin/respondent-masters/components/RespondentMasterForm';
import { useToast } from '../../features/ui/ToastContext';
import { AdminApiError } from '../../features/admin/lib/adminFetch';

const RespondentMasterCreatePage: React.FC = () => {
  const navigate = useNavigate();
  const { showToast } = useToast();
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

  const handleSubmit = async (data: CreateRespondentMasterRequest) => {
    try {
      setIsSaving(true);
      setError(null);
      setValidationErrors({});
      await adminRespondentMasterApi.create(data);
      showToast('マスターを登録しました', 'success');
      navigate('/manage/respondent-masters');
    } catch (err: any) {
      if (err instanceof AdminApiError && err.data && err.data.code === 'VALIDATION_ERROR') {
        setValidationErrors(err.data.details || {});
        setError('入力内容を確認してください。');
      } else {
        setError(err.message);
      }
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div>
      <div className="admin-page-header">
        <h1>回答者マスター登録</h1>
      </div>

      <div className="admin-card">
        <div className="admin-card-body">
          {error && <div className="admin-error-message mb-4">{error}</div>}
          <RespondentMasterForm
            onSubmit={handleSubmit}
            isSaving={isSaving}
            validationErrors={validationErrors}
            onCancel={() => navigate('/manage/respondent-masters')}
          />
        </div>
      </div>
    </div>
  );
};

export default RespondentMasterCreatePage;
