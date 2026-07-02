import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Model } from 'survey-core';
import { adminSurveyApi } from '../../features/admin/surveys/adminSurveyApi';
import type { ResponseDetail } from '../../features/admin/surveys/types';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import { useToast } from '../../features/ui/ToastContext';
import AdminButton from '../../components/admin/AdminButton';

const ResponseEditPage: React.FC = () => {
  const { id, responseId } = useParams<{ id: string; responseId: string }>();
  const surveyId = Number(id);
  const rid = Number(responseId);
  const navigate = useNavigate();
  const { showToast } = useToast();

  const [response, setResponse] = useState<ResponseDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      if (Number.isNaN(surveyId) || Number.isNaN(rid)) {
        setError('無効なパラメータです。');
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        const responseData = await adminSurveyApi.getResponse(surveyId, rid);
        setResponse(responseData);
        setError(null);
      } catch (err) {
        setError('データの取得に失敗しました。');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [surveyId, rid]);

  const handleComplete = async (sender: Model) => {
    setIsSubmitting(true);
    try {
      await adminSurveyApi.updateResponse(surveyId, rid, sender.data);
      showToast('回答を更新しました');
      navigate(`/manage/surveys/${surveyId}/responses/${rid}`);
    } catch (err) {
      console.error(err);
      showToast('更新に失敗しました。', 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="loading-container">
        <p>読み込み中...</p>
      </div>
    );
  }

  if (error || !response) {
    return (
      <div className="error-container">
        <p>{error || 'データが見つかりませんでした。'}</p>
        <AdminButton
          to={
            Number.isNaN(surveyId)
              ? '/manage/surveys'
              : `/manage/surveys/${surveyId}/responses`
          }
        >
          回答一覧に戻る
        </AdminButton>
      </div>
    );
  }

  return (
    <div>
      <div className="admin-page-header">
        <h1>回答編集</h1>
        <div className="admin-actions">
          <AdminButton to={`/manage/surveys/${surveyId}/responses/${rid}`}>
            詳細に戻る
          </AdminButton>
        </div>
      </div>

      <div className="admin-card">
        <div className="admin-card-header">
          <h3>回答内容の編集</h3>
        </div>
        <div className="admin-card-body">
          <SurveyRenderer
            questions={response.survey_snapshot_json}
            initialValues={response.answer_json}
            onComplete={handleComplete}
            isSubmitting={isSubmitting}
          />
        </div>
      </div>
    </div>
  );
};

export default ResponseEditPage;
