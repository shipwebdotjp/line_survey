import React, { useEffect, useState } from 'react';
import { adminDraftApi } from '../../features/admin/surveys/adminDraftApi';
import type { ResponseDraft } from '../../features/survey/types';
import { formatDisplayDate } from '../../features/admin/surveys/dateUtils';
import AdminButton from '../../components/admin/AdminButton';
import { useConfirm } from '../../features/ui/ConfirmContext';
import { useToast } from '../../features/ui/ToastContext';

const ResponseDraftListPage: React.FC = () => {
  const [drafts, setDrafts] = useState<ResponseDraft[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const confirm = useConfirm();
  const { showToast } = useToast();

  const fetchDrafts = async () => {
    try {
      setLoading(true);
      const data = await adminDraftApi.list();
      setDrafts(data);
      setError(null);
    } catch (err) {
      setError('下書き一覧の取得に失敗しました。');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDrafts();
  }, []);

  const handleCleanup = async () => {
    if (!(await confirm({
      message: '最終更新から30日以上経過した下書きをすべて削除します。よろしいですか？',
      danger: true
    }))) {
      return;
    }

    try {
      const result = await adminDraftApi.cleanup();
      showToast(result.message);
      await fetchDrafts();
    } catch (err: unknown) {
      if (err instanceof Error) {
        showToast(err.message, 'error');
      } else {
        showToast('クリーンアップに失敗しました。', 'error');
      }
    }
  };

  if (loading) {
    return (
      <div className="loading-container">
        <p>読み込み中...</p>
      </div>
    );
  }

  return (
    <div>
      <div className="admin-page-header">
        <h1>下書き一覧</h1>
        <div className="admin-actions">
          <AdminButton onClick={handleCleanup} variant="danger">
            クリーンアップ(30日経過)
          </AdminButton>
        </div>
      </div>

      {error && <div className="error-banner">{error}</div>}

      <div className="admin-table-container">
        {drafts.length === 0 ? (
          <div className="empty-state">
            <p>下書きはありません。</p>
          </div>
        ) : (
          <table className="admin-table">
            <thead>
              <tr>
                <th>アンケートタイトル</th>
                <th>回答者名</th>
                <th>最終更新日時</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              {drafts.map((draft) => (
                <tr key={draft.id}>
                  <td>{draft.survey_title}</td>
                  <td>
                    {draft.respondent_name}
                    {draft.respondent_email && (
                      <div style={{ fontSize: '0.8rem', color: '#666' }}>{draft.respondent_email}</div>
                    )}
                  </td>
                  <td>{formatDisplayDate(draft.updated_at)}</td>
                  <td>
                    <div className="admin-actions">
                      <AdminButton
                        to={`/manage/response-drafts/${draft.id}`}
                        size="sm"
                      >
                        詳細
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

export default ResponseDraftListPage;
