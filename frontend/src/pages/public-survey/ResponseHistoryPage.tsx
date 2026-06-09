import React, { useEffect, useState } from 'react';
import { useLiffContext } from '../../features/liff/LiffContext';
import { fetchWithSession } from '../../lib/publicApi';
import type { ResponseHistoryItem } from '../../features/survey/types';
import { createLiffUrl } from '../../lib/liffUrl';

const ResponseHistoryPage: React.FC = () => {
  const { isLoggedIn, idToken, identify } = useLiffContext();
  const [history, setHistory] = useState<ResponseHistoryItem[] | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    document.title = '回答履歴';

    if (!isLoggedIn || !idToken) {
      setError('LINEログインが必要です。');
      setIsLoading(false);
      return;
    }

    const fetchHistory = async () => {
      try {
        setIsLoading(true);

        const fetchOptions = {
          onSessionRequired: identify,
        };

        // Identification (ensure session exists)
        const identifyResponse = await fetchWithSession('/api/liff/identify', {
          method: 'POST',
          body: JSON.stringify({ id_token: idToken }),
        }, fetchOptions);

        if (!identifyResponse.ok) {
          setError('本人確認に失敗しました。');
          return;
        }

        // Fetch history
        const response = await fetchWithSession('/api/surveys/responses/history', {}, fetchOptions);
        const result = await response.json();

        if (!response.ok) {
          setError(result.error || '履歴の取得に失敗しました。');
          return;
        }

        setHistory(result.data);
      } catch (err) {
        setError('通信エラーが発生しました。');
      } finally {
        setIsLoading(false);
      }
    };

    fetchHistory();
  }, [isLoggedIn, idToken]);

  if (isLoading) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <p>読み込み中...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>エラー</h1>
        <p>{error}</p>
      </div>
    );
  }

  return (
    <div style={{ padding: '1rem', maxWidth: '800px', margin: '0 auto' }}>
      <h1 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', textAlign: 'center' }}>回答履歴</h1>

      {!history || history.length === 0 ? (
        <div style={{ padding: '2rem', textAlign: 'center', color: '#666', background: '#f8f9fa', borderRadius: '8px' }}>
          <p>まだ回答履歴がありません。</p>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          {history.map((item, index) => (
            <div
              key={index}
              style={{
                border: '1px solid #ddd',
                borderRadius: '8px',
                padding: '1rem',
                backgroundColor: 'white',
                boxShadow: '0 2px 4px rgba(0,0,0,0.05)'
              }}
            >
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '1rem' }}>
                <div style={{ flex: 1 }}>
                  <h2 style={{ fontSize: '1.1rem', marginBottom: '0.5rem', fontWeight: 'bold' }}>
                    {item.survey_title || <span style={{ color: '#999' }}>削除済みアンケート</span>}
                  </h2>
                  <div style={{ fontSize: '0.85rem', color: '#666' }}>
                    <p>回答日時: {item.submitted_at}</p>
                    {item.updated_at !== item.submitted_at && (
                      <p>更新日時: {item.updated_at}</p>
                    )}
                  </div>
                </div>
                {item.survey_public_id && (
                  <a
                    href={createLiffUrl(`/s/${item.survey_public_id}`)}
                    style={{
                      display: 'inline-block',
                      padding: '0.5rem 1rem',
                      backgroundColor: '#007bff',
                      color: 'white',
                      textDecoration: 'none',
                      borderRadius: '4px',
                      fontSize: '0.9rem',
                      fontWeight: 'bold',
                      whiteSpace: 'nowrap'
                    }}
                  >
                    詳細
                  </a>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default ResponseHistoryPage;
