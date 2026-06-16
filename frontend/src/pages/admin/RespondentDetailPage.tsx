import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { adminRespondentApi } from '../../features/admin/respondents/adminRespondentApi';
import type { RespondentDetail } from '../../features/admin/respondents/types';
import AdminButton from '../../components/admin/AdminButton';
import { useConfirm } from '../../features/ui/ConfirmContext';
import { useToast } from '../../features/ui/ToastContext';

const RespondentDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const confirm = useConfirm();
  const { showToast } = useToast();
  const [respondent, setRespondent] = useState<RespondentDetail | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (id && /^\d+$/.test(id)) {
      loadRespondent(parseInt(id, 10));
    } else if (id) {
      setError('無効な回答者 ID です。');
      setIsLoading(false);
    }
  }, [id]);

  const loadRespondent = async (respondentId: number) => {
    try {
      setIsLoading(true);
      const data = await adminRespondentApi.get(respondentId);
      setRespondent(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!respondent) return;

    const name = respondent.name || respondent.line_display_name;
    const responseCount = respondent.responses.length;

    if (!(await confirm({
      message: `回答者「${name}」を削除しますか？\n紐づいている ${responseCount} 件の回答もすべて削除されます。\nこの操作は取り消せません。`,
      danger: true
    }))) {
      return;
    }

    try {
      await adminRespondentApi.delete(respondent.id);
      showToast('回答者を削除しました');
      navigate('/admin/respondents');
    } catch (err: any) {
      showToast(err.message, 'error');
    }
  };

  if (isLoading) return <div>読み込み中...</div>;
  if (error) return <div className="error-message">エラー: {error}</div>;
  if (!respondent) return <div>回答者が見つかりません。</div>;

  return (
    <div className="admin-respondent-detail">
      <div className="admin-page-header">
        <h1>回答者詳細</h1>
        <div className="admin-actions">
          <AdminButton to={`/admin/respondents/${respondent.id}/edit`}>
            編集
          </AdminButton>
          <AdminButton variant="danger" onClick={handleDelete}>
            削除
          </AdminButton>
          <AdminButton to="/admin/respondents">一覧へ戻る</AdminButton>
        </div>
      </div>

      <div className="admin-card mb-4">
        <h2>プロフィール</h2>
        <table className="admin-detail-table">
          <tbody>
            <tr>
              <th>ID</th>
              <td>{respondent.id}</td>
            </tr>
            <tr>
              <th>LINE 表示名</th>
              <td>{respondent.line_display_name}</td>
            </tr>
            <tr>
              <th>氏名</th>
              <td>{respondent.name}</td>
            </tr>
            <tr>
              <th>メールアドレス</th>
              <td>{respondent.email}</td>
            </tr>
            <tr>
              <th>敬称</th>
              <td>{respondent.honorific || '(未設定)'}</td>
            </tr>
            <tr>
              <th>LINE User ID</th>
              <td><code>{respondent.line_user_id}</code></td>
            </tr>
            <tr>
              <th>マスター紐付け ID</th>
              <td>{respondent.respondent_master_id || '(未紐付け)'}</td>
            </tr>
            <tr>
              <th>作成日時</th>
              <td>{respondent.created_at}</td>
            </tr>
            <tr>
              <th>最終更新日時</th>
              <td>{respondent.updated_at}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div className="admin-card">
        <h2>回答履歴</h2>
        <table className="admin-table">
          <thead>
            <tr>
              <th>回答 ID</th>
              <th>アンケートタイトル</th>
              <th>回答日時</th>
              <th>更新日時</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            {respondent.responses.length === 0 ? (
              <tr>
                <td colSpan={5} style={{ textAlign: 'center' }}>回答履歴はありません。</td>
              </tr>
            ) : (
              respondent.responses.map((history) => (
                <tr key={history.response_id}>
                  <td>{history.response_id}</td>
                  <td>{history.survey_title || '(削除済みアンケート)'}</td>
                  <td>{history.submitted_at}</td>
                  <td>{history.updated_at}</td>
                  <td>
                    {history.survey_id && (
                      <div className="admin-actions">
                        <AdminButton
                          to={`/admin/surveys/${history.survey_id}/responses/${history.response_id}`}
                          size="sm"
                        >
                          詳細
                        </AdminButton>
                      </div>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default RespondentDetailPage;
