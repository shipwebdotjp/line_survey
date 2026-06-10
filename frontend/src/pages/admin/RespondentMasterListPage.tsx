import React, { useEffect, useState, useRef } from 'react';
import { adminRespondentMasterApi } from '../../features/admin/respondent-masters/adminRespondentMasterApi';
import type { RespondentMaster, ImportResult } from '../../features/admin/respondent-masters/types';
import AdminButton from '../../components/admin/AdminButton';

const RespondentMasterListPage: React.FC = () => {
  const [masters, setMasters] = useState<RespondentMaster[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [importing, setImporting] = useState(false);
  const [importResult, setImportResult] = useState<ImportResult | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const fetchMasters = async () => {
    try {
      setLoading(true);
      const data = await adminRespondentMasterApi.list();
      setMasters(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'データの取得に失敗しました');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMasters();
  }, []);

  const handleImport = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    try {
      setImporting(true);
      setError(null);
      setImportResult(null);
      const result = await adminRespondentMasterApi.import(file);
      setImportResult(result);
      if (result.imported > 0) {
        await fetchMasters();
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'インポートに失敗しました');
    } finally {
      setImporting(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  return (
    <div>
      <div className="admin-page-header">
        <h1>回答者マスター管理</h1>
        <div className="admin-actions">
          <AdminButton
            onClick={() => fileInputRef.current?.click()}
            disabled={importing}
          >
            {importing ? 'インポート中...' : 'CSVインポート'}
          </AdminButton>
          <input
            type="file"
            accept=".csv"
            onChange={handleImport}
            style={{
              position: 'absolute',
              width: '1px',
              height: '1px',
              padding: '0',
              margin: '-1px',
              overflow: 'hidden',
              clip: 'rect(0,0,0,0)',
              border: '0',
            }}
            disabled={importing}
            ref={fileInputRef}
            aria-hidden="true"
            tabIndex={-1}
          />
        </div>
      </div>

      {error && <div className="admin-error-message">{error}</div>}

      {importResult && (
        <div className={`admin-import-result ${importResult.errors.length > 0 ? 'warning' : 'success'}`}>
          <p>{importResult.imported} 件のデータをインポートしました。</p>
          {importResult.errors.length > 0 && (
            <div className="admin-import-errors">
              <p>以下の行でエラーが発生しました：</p>
              <ul>
                {importResult.errors.map((err, idx) => (
                  <li key={idx}>
                    {err.row}行目: {err.reason}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}

      <div className="admin-card">
        <div className="admin-card-body">
          {loading ? (
            <p>読み込み中...</p>
          ) : masters.length === 0 ? (
            <p>データがありません。CSVインポートから登録してください。</p>
          ) : (
            <div className="admin-table-container">
              <table className="admin-table">
                <thead>
                  <tr>
                    <th>マスターコード</th>
                    <th>LINE表示名</th>
                    <th>氏名</th>
                    <th>メール</th>
                    <th>敬称</th>
                    <th>備考</th>
                    <th>更新日時</th>
                  </tr>
                </thead>
                <tbody>
                  {masters.map((master) => (
                    <tr key={master.id}>
                      <td>{master.master_code}</td>
                      <td>{master.line_display_name}</td>
                      <td>{master.name}</td>
                      <td>{master.email}</td>
                      <td>{master.honorific}</td>
                      <td style={{ maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={master.note || ''}>
                        {master.note}
                      </td>
                      <td>{master.updated_at}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default RespondentMasterListPage;
