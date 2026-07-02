import React, { useEffect, useState, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { adminSurveyApi } from '../../features/admin/surveys/adminSurveyApi';
import type { ResponseDetail, ResponseSummary } from '../../features/admin/surveys/types';
import { formatDisplayDate } from '../../features/admin/surveys/dateUtils';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import AdminButton from '../../components/admin/AdminButton';
import { useToast } from '../../features/ui/ToastContext';
import { useConfirm } from '../../features/ui/ConfirmContext';

const ResponseDetailPage: React.FC = () => {
  const { id, responseId } = useParams<{ id: string; responseId: string }>();
  const navigate = useNavigate();
  const surveyId = Number(id);
  const rid = Number(responseId);
  const { showToast } = useToast();
  const confirm = useConfirm();

  const [response, setResponse] = useState<ResponseDetail | null>(null);
  const [responses, setResponses] = useState<ResponseSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      if (Number.isNaN(surveyId) || Number.isNaN(rid)) {
        setError('無効なパラメータです。');
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        const [responseData, responsesData] = await Promise.allSettled([
          adminSurveyApi.getResponse(surveyId, rid),
          adminSurveyApi.listResponses(surveyId),
        ]);

        if (responseData.status === 'fulfilled') {
          setResponse(responseData.value);
          setError(null);
        } else {
          throw responseData.reason;
        }

        if (responsesData.status === 'fulfilled') {
          setResponses(responsesData.value);
        }
      } catch (err) {
        setError('データの取得に失敗しました。');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [surveyId, rid]);

  const handleDelete = async () => {
    if (!(await confirm({ message: '回答を削除してもよろしいですか？', danger: true }))) {
      return;
    }

    try {
      await adminSurveyApi.deleteResponse(surveyId, rid);
      showToast('回答を削除しました');
      navigate(`/manage/surveys/${surveyId}/responses`, { replace: true });
    } catch (err) {
      console.error(err);
      showToast('削除に失敗しました。', 'error');
    }
  };

  const { prevId, nextId } = useMemo(() => {
    if (responses.length === 0) return { prevId: null, nextId: null };
    const currentIndex = responses.findIndex((r) => r.id === rid);
    if (currentIndex === -1) return { prevId: null, nextId: null };

    return {
      prevId: currentIndex > 0 ? responses[currentIndex - 1].id : null,
      nextId: currentIndex < responses.length - 1 ? responses[currentIndex + 1].id : null,
    };
  }, [responses, rid]);

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
        <h1>回答詳細</h1>
        <div className="admin-actions">
          <AdminButton
            to={`/manage/surveys/${surveyId}/responses/${prevId}`}
            disabled={prevId === null}
          >
            前の回答
          </AdminButton>
          <AdminButton
            to={`/manage/surveys/${surveyId}/responses/${nextId}`}
            disabled={nextId === null}
          >
            次の回答
          </AdminButton>
          <AdminButton to={`/manage/surveys/${surveyId}/responses/${rid}/edit`}>
            編集
          </AdminButton>
          <AdminButton variant="danger" onClick={handleDelete}>
            削除
          </AdminButton>
          <AdminButton to={`/manage/surveys/${surveyId}/responses`}>
            回答一覧に戻る
          </AdminButton>
        </div>
      </div>

      <div className="admin-card" style={{ marginBottom: '2rem' }}>
        <div className="admin-card-header">
          <h3>回答者情報</h3>
        </div>
        <div className="admin-card-body">
          <table className="admin-detail-table">
            <tbody>
              <tr>
                <th>氏名</th>
                <td>
                  {response.respondent.name}
                  {response.respondent.honorific}
                </td>
              </tr>
              <tr>
                <th>LINE表示名</th>
                <td>{response.respondent.line_display_name}</td>
              </tr>
              <tr>
                <th>メールアドレス</th>
                <td>{response.respondent.email}</td>
              </tr>
              <tr>
                <th>回答日時</th>
                <td>{formatDisplayDate(response.submitted_at)}</td>
              </tr>
              {response.updated_at !== response.submitted_at && (
                <tr>
                  <th>最終更新日時</th>
                  <td>{formatDisplayDate(response.updated_at)}</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      <div className="admin-card">
        <div className="admin-card-header">
          <h3>回答内容</h3>
        </div>
        <div className="admin-card-body">
          <SurveyRenderer
            questions={response.survey_snapshot_json}
            initialValues={response.answer_json}
            readOnly={true}
          />
        </div>
      </div>
    </div>
  );
};

export default ResponseDetailPage;
