import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { adminSurveyApi } from '../../features/admin/surveys/adminSurveyApi';
import { useToast } from '../../features/ui/ToastContext';
import type { ResponseSummary, Survey } from '../../features/admin/surveys/types';
import { formatDisplayDate } from '../../features/admin/surveys/dateUtils';
import AdminButton from '../../components/admin/AdminButton';
import SurveyResultsNav from '../../features/admin/surveys/SurveyResultsNav';

const ResponseListPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const surveyId = Number(id);

  const [survey, setSurvey] = useState<Survey | null>(null);
  const [responses, setResponses] = useState<ResponseSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const { showToast } = useToast();

  useEffect(() => {
    const fetchData = async () => {
      if (Number.isNaN(surveyId)) {
        setError('無効なパラメータです。');
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        const [surveyData, responsesData] = await Promise.all([
          adminSurveyApi.get(surveyId),
          adminSurveyApi.listResponses(surveyId),
        ]);
        setSurvey(surveyData);
        setResponses(responsesData);
        setError(null);
      } catch (err) {
        setError('データの取得に失敗しました。');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [surveyId]);

  const handleDelete = async (responseId: number) => {
    if (!window.confirm('回答を削除してもよろしいですか？')) {
      return;
    }

    try {
      await adminSurveyApi.deleteResponse(surveyId, responseId);
      showToast('回答を削除しました');
      setResponses((prev) => prev.filter((r) => r.id !== responseId));
    } catch (err) {
      console.error(err);
      showToast('削除に失敗しました。', 'error');
    }
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
      <div className="error-container">
        <p>{error || 'アンケートが見つかりませんでした。'}</p>
        <AdminButton to="/admin/surveys">アンケート一覧に戻る</AdminButton>
      </div>
    );
  }

  return (
    <div>
      <div className="admin-page-header">
        <h1>{survey.title} - 回答一覧</h1>
        <div className="admin-actions">
          <AdminButton href={adminSurveyApi.getCsvUrl(surveyId)} download>
            CSVダウンロード
          </AdminButton>
          <AdminButton to="/admin/surveys">アンケート一覧に戻る</AdminButton>
        </div>
      </div>

      <SurveyResultsNav surveyId={surveyId} />

      <div className="admin-table-container">
        {responses.length === 0 ? (
          <div className="empty-state">
            <p>回答がまだありません。</p>
          </div>
        ) : (
          <table className="admin-table">
            <thead>
              <tr>
                <th>回答者名</th>
                <th>LINE表示名</th>
                <th>メールアドレス</th>
                <th>回答日時</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              {responses.map((res) => (
                <tr key={res.id}>
                  <td>
                    {res.respondent_name}
                    {res.respondent_honorific}
                  </td>
                  <td>{res.respondent_line_display_name}</td>
                  <td>{res.respondent_email}</td>
                  <td>{formatDisplayDate(res.submitted_at)}</td>
                  <td>
                    <div className="admin-actions">
                      <AdminButton
                        to={`/admin/surveys/${surveyId}/responses/${res.id}`}
                        size="sm"
                      >
                        詳細
                      </AdminButton>
                      <AdminButton
                        to={`/admin/surveys/${surveyId}/responses/${res.id}/edit`}
                        size="sm"
                      >
                        編集
                      </AdminButton>
                      <AdminButton
                        variant="danger"
                        size="sm"
                        onClick={() => handleDelete(res.id)}
                      >
                        削除
                      </AdminButton>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
};

export default ResponseListPage;
