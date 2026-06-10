import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { adminRespondentApi } from '../../features/admin/respondents/adminRespondentApi';
import type { RespondentSummary } from '../../features/admin/respondents/types';

const RespondentListPage: React.FC = () => {
  const [respondents, setRespondents] = useState<RespondentSummary[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadRespondents();
  }, []);

  const loadRespondents = async () => {
    try {
      setIsLoading(true);
      const data = await adminRespondentApi.list();
      setRespondents(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (id: number, name: string, responseCount: number) => {
    if (deletingId !== null) return;

    if (!window.confirm(`回答者「${name}」を削除しますか？\n紐づいている ${responseCount} 件の回答もすべて削除されます。\nこの操作は取り消せません。`)) {
      return;
    }

    try {
      setDeletingId(id);
      await adminRespondentApi.delete(id);
      setRespondents(respondents.filter(r => r.id !== id));
    } catch (err: any) {
      alert(err.message);
    } finally {
      setDeletingId(null);
    }
  };

  if (isLoading) return <div>読み込み中...</div>;
  if (error) return <div className="error-message">エラー: {error}</div>;

  return (
    <div className="admin-respondent-list">
      <div className="admin-page-header">
        <h1>回答者管理</h1>
      </div>

      <div className="admin-card">
        <table className="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>LINE表示名</th>
              <th>氏名</th>
              <th>メール</th>
              <th>敬称</th>
              <th>回答数</th>
              <th>最終回答日時</th>
              <th>更新日時</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            {respondents.length === 0 ? (
              <tr>
                <td colSpan={9} style={{ textAlign: 'center' }}>回答者が登録されていません。</td>
              </tr>
            ) : (
              respondents.map(respondent => (
                <tr key={respondent.id}>
                  <td>{respondent.id}</td>
                  <td>{respondent.line_display_name}</td>
                  <td>{respondent.name}</td>
                  <td>{respondent.email}</td>
                  <td>{respondent.honorific || '-'}</td>
                  <td>{respondent.response_count}</td>
                  <td>{respondent.latest_submitted_at || '-'}</td>
                  <td>{respondent.updated_at}</td>
                  <td className="admin-table-actions">
                    <Link to={`/admin/respondents/${respondent.id}`} className="btn btn-sm">詳細</Link>
                    <Link to={`/admin/respondents/${respondent.id}/edit`} className="btn btn-sm">編集</Link>
                    <button
                      onClick={() => handleDelete(respondent.id, respondent.name || respondent.line_display_name, respondent.response_count)}
                      className="btn btn-sm btn-danger"
                      disabled={deletingId === respondent.id}
                    >
                      {deletingId === respondent.id ? '削除中...' : '削除'}
                    </button>
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

export default RespondentListPage;
