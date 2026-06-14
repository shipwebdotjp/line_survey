import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { adminDraftApi } from '../../features/admin/surveys/adminDraftApi';
import type { ResponseDraft } from '../../features/survey/types';
import { formatDisplayDate } from '../../features/admin/surveys/dateUtils';
import SurveyRenderer from '../../features/survey/SurveyRenderer';
import AdminButton from '../../components/admin/AdminButton';

const ResponseDraftDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [draft, setDraft] = useState<ResponseDraft | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchDraft = async () => {
      if (!id) return;
      try {
        setLoading(true);
        const data = await adminDraftApi.get(parseInt(id, 10));
        setDraft(data);
        setError(null);
      } catch (err: unknown) {
        setError('下書きの取得に失敗しました。');
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchDraft();
  }, [id]);

  if (loading) {
    return <div className="loading-container"><p>読み込み中...</p></div>;
  }

  if (error || !draft) {
    return (
      <div className="error-container">
        <p>{error || '下書きが見つかりませんでした。'}</p>
        <AdminButton to="/admin/response-drafts">一覧へ戻る</AdminButton>
      </div>
    );
  }

  return (
    <div>
      <div className="admin-page-header">
        <h1>下書き詳細</h1>
        <div className="admin-actions">
          <AdminButton to="/admin/response-drafts">
            一覧へ戻る
          </AdminButton>
        </div>
      </div>

      <div className="admin-card" style={{ marginBottom: '2rem' }}>
        <table className="admin-detail-table">
          <tbody>
            <tr>
              <th>アンケート</th>
              <td>{draft.survey_title}</td>
            </tr>
            <tr>
              <th>回答者</th>
              <td>
                {draft.respondent_name}
                {draft.respondent_email && ` (${draft.respondent_email})`}
              </td>
            </tr>
            <tr>
              <th>最終更新日時</th>
              <td>{formatDisplayDate(draft.updated_at)}</td>
            </tr>
            <tr>
              <th>作成日時</th>
              <td>{formatDisplayDate(draft.created_at)}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div className="admin-card">
        <h2 style={{ fontSize: '1.25rem', marginBottom: '1rem' }}>回答内容 (途中経過)</h2>
        {draft.survey_questions_json ? (
          <SurveyRenderer
            questions={draft.survey_questions_json}
            data={draft.answer_json}
            readOnly={true}
          />
        ) : (
          <div className="error-banner">
            アンケートの設問データが見つからないため、プレビューを表示できません。
          </div>
        )}
      </div>

      <div className="admin-card" style={{ marginTop: '1rem' }}>
          <h3 style={{ fontSize: '1rem', marginBottom: '0.5rem' }}>生のJSONデータ</h3>
          <pre style={{
            background: '#f8f9fa',
            padding: '1rem',
            borderRadius: '4px',
            overflowX: 'auto',
            fontSize: '0.8rem'
          }}>
            {JSON.stringify(draft.answer_json, null, 2)}
          </pre>
      </div>
    </div>
  );
};

export default ResponseDraftDetailPage;
