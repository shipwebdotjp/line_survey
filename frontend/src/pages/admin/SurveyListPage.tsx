import React, { useEffect, useState } from 'react';
import { adminSurveyApi } from '../../features/admin/surveys/adminSurveyApi';
import type { Survey } from '../../features/admin/surveys/types';
import { formatDisplayDate } from '../../features/admin/surveys/dateUtils';
import { createLiffUrl } from '../../lib/liffUrl';
import AdminButton from '../../components/admin/AdminButton';
import { useConfirm } from '../../features/ui/ConfirmContext';
import { useToast } from '../../features/ui/ToastContext';

const SurveyListPage: React.FC = () => {
  const [surveys, setSurveys] = useState<Survey[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const confirm = useConfirm();
  const { showToast } = useToast();

  const fetchSurveys = async () => {
    try {
      setLoading(true);
      const data = await adminSurveyApi.list();
      setSurveys(data);
      setError(null);
    } catch (err) {
      setError('アンケート一覧の取得に失敗しました。');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSurveys();
  }, []);

  const handleCopyUrl = async (publicId: string) => {
    const url = createLiffUrl(`/s/${publicId}`);
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(url);
        showToast('URLをコピーしました。');
      } else {
        throw new Error('Clipboard API unavailable');
      }
    } catch (err) {
      console.error('Failed to copy URL:', err);
      // Fallback strategy
      const textArea = document.createElement('textarea');
      textArea.value = url;
      document.body.appendChild(textArea);
      textArea.select();
      try {
        document.execCommand('copy');
        showToast('URLをコピーしました。');
      } catch (fallbackErr) {
        console.error('Fallback copy failed:', fallbackErr);
        showToast(`URLのコピーに失敗しました。直接コピーしてください: ${url}`, 'error');
      }
      document.body.removeChild(textArea);
    }
  };

  const handleDelete = async (id: number, title: string) => {
    if (!(await confirm({ message: `アンケート「${title}」を削除しますか？`, danger: true }))) {
      return;
    }

    try {
      await adminSurveyApi.delete(id);
      showToast('アンケートを削除しました');
      await fetchSurveys();
    } catch (err: any) {
      showToast(err.message || '削除に失敗しました。', 'error');
    }
  };

  const handleDuplicate = async (id: number) => {
    try {
      await adminSurveyApi.duplicate(id);
      showToast('アンケートを複製しました');
      await fetchSurveys();
    } catch (err: any) {
      showToast(err.message || '複製に失敗しました。', 'error');
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
        <h1>アンケート管理</h1>
        <div className="admin-actions">
          <AdminButton to="/manage/surveys/new" variant="primary">
            新規作成
          </AdminButton>
        </div>
      </div>

      {error && <div className="error-banner">{error}</div>}

      <div className="admin-table-container">
        {surveys.length === 0 ? (
          <div className="empty-state">
            <p>アンケートがありません。</p>
          </div>
        ) : (
          <table className="admin-table">
            <thead>
              <tr>
                <th>タイトル</th>
                <th>ステータス</th>
                <th>回答数</th>
                <th>複数回答</th>
                <th>回答編集</th>
                <th>開始日時</th>
                <th>終了日時</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              {surveys.map((survey) => (
                <tr key={survey.id}>
                  <td>
                    <strong>{survey.title}</strong>
                  </td>
                  <td>
                    <span className={`badge badge-${survey.status}`}>
                      {survey.status}
                    </span>
                  </td>
                  <td>{survey.response_count || 0}</td>
                  <td>{survey.allow_multiple ? '可' : '不可'}</td>
                  <td>{survey.allow_edit ? '可' : '不可'}</td>
                  <td>{formatDisplayDate(survey.starts_at)}</td>
                  <td>{formatDisplayDate(survey.ends_at)}</td>
                  <td>
                    <div className="admin-actions">
                      <AdminButton
                        to={`/manage/surveys/${survey.id}/edit`}
                        size="sm"
                      >
                        編集
                      </AdminButton>
                      <AdminButton
                        onClick={() => handleCopyUrl(survey.public_id)}
                        size="sm"
                      >
                        URLコピー
                      </AdminButton>
                      <AdminButton
                        onClick={() => handleDuplicate(survey.id)}
                        size="sm"
                      >
                        複製
                      </AdminButton>
                      <AdminButton
                        variant="danger"
                        size="sm"
                        onClick={() => handleDelete(survey.id, survey.title)}
                        disabled={(survey.response_count || 0) > 0}
                        title={
                          (survey.response_count || 0) > 0
                            ? '回答があるため削除できません'
                            : ''
                        }
                      >
                        削除
                      </AdminButton>
                      <AdminButton
                        to={`/manage/surveys/${survey.id}/responses`}
                        size="sm"
                      >
                        回答一覧
                      </AdminButton>
                      <AdminButton
                        to={`/manage/surveys/${survey.id}/summary`}
                        size="sm"
                      >
                        要約
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

export default SurveyListPage;
