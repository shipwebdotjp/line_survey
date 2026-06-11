import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { adminRespondentMasterApi, FetchError } from '../../features/admin/respondent-masters/adminRespondentMasterApi';
import type { RespondentMaster, UpdateRespondentMasterRequest } from '../../features/admin/respondent-masters/types';
import RespondentMasterForm from '../../features/admin/respondent-masters/components/RespondentMasterForm';
import { useToast } from '../../features/ui/ToastContext';

const RespondentMasterEditPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { showToast } = useToast();
  const [master, setMaster] = useState<RespondentMaster | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (id) {
      loadMaster(parseInt(id, 10));
    }
  }, [id]);

  const loadMaster = async (masterId: number) => {
    try {
      setIsLoading(true);
      const data = await adminRespondentMasterApi.get(masterId);
      setMaster(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  const handleSubmit = async (data: UpdateRespondentMasterRequest) => {
    if (!id) return;
    try {
      setIsSaving(true);
      setError(null);
      setValidationErrors({});
      await adminRespondentMasterApi.update(parseInt(id, 10), data);
      showToast('マスターを更新しました', 'success');
      navigate('/admin/respondent-masters');
    } catch (err: any) {
      if (err instanceof FetchError && err.data && err.data.code === 'VALIDATION_ERROR') {
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
  if (!master) return <div>データが見つかりません。</div>;

  return (
    <div>
      <div className="admin-page-header">
        <h1>回答者マスター編集</h1>
      </div>

      <div className="admin-card">
        <div className="admin-card-body">
          {error && <div className="admin-error-message mb-4">{error}</div>}
          <RespondentMasterForm
            initialData={master}
            onSubmit={handleSubmit}
            isSaving={isSaving}
            validationErrors={validationErrors}
            onCancel={() => navigate('/admin/respondent-masters')}
          />
        </div>
      </div>
    </div>
  );
};

export default RespondentMasterEditPage;
