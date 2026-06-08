import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import SurveyForm from '../../features/admin/surveys/SurveyForm';
import { adminSurveyApi } from '../../features/admin/surveys/adminSurveyApi';
import type { Survey, SurveyCreateParams } from '../../features/admin/surveys/types';

const SurveyEditPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [survey, setSurvey] = useState<Survey | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchSurvey = async () => {
      if (!id) return;
      const surveyId = parseInt(id, 10);
      if (Number.isNaN(surveyId)) {
        setError('無効なアンケートIDです。');
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        const data = await adminSurveyApi.get(surveyId);
        setSurvey(data);
        setError(null);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'アンケートの取得に失敗しました。');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchSurvey();
  }, [id]);

  const handleSubmit = async (values: SurveyCreateParams) => {
    if (!id) return;
    const surveyId = parseInt(id, 10);
    if (Number.isNaN(surveyId)) {
      alert('無効なアンケートIDです。');
      return;
    }

    await adminSurveyApi.update(surveyId, values);
    navigate('/admin/surveys');
  };

  const handleCancel = () => {
    navigate('/admin/surveys');
  };

  if (loading) {
    return (
      <div className="loading-container">
        <p>読み込み中...</p>
      </div>
    );
  }

  if (error || !survey) {
    return (
      <div className="error-banner">
        {error || 'アンケートが見つかりませんでした。'}
        <div style={{ marginTop: '1rem' }}>
          <button onClick={handleCancel} className="btn btn-outline">
            一覧に戻る
          </button>
        </div>
      </div>
    );
  }

  return (
    <div>
      <div className="admin-header-actions" style={{ marginBottom: '1.5rem' }}>
        <h1>アンケート編集</h1>
      </div>
      <SurveyForm
        initialValues={survey}
        onSubmit={handleSubmit}
        onCancel={handleCancel}
        submitLabel="保存"
      />
    </div>
  );
};

export default SurveyEditPage;
