import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { adminSurveyApi } from '../../features/admin/surveys/adminSurveyApi';
import type { ResponseDetail } from '../../features/admin/surveys/types';
import { formatDisplayDate } from '../../features/admin/surveys/dateUtils';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import AdminButton from '../../components/admin/AdminButton';

const ResponseDetailPage: React.FC = () => {
  const { id, responseId } = useParams<{ id: string; responseId: string }>();
  const surveyId = Number(id);
  const rid = Number(responseId);

  const [response, setResponse] = useState<ResponseDetail | null>(null);
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
              ? '/admin/surveys'
              : `/admin/surveys/${surveyId}/responses`
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
          <AdminButton to={`/admin/surveys/${surveyId}/responses/${rid}/edit`}>
            編集
          </AdminButton>
          <AdminButton to={`/admin/surveys/${surveyId}/responses`}>
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
