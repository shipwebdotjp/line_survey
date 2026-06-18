import React, { useEffect, useState } from 'react';
import { useLiffContext } from '../../features/liff/LiffContext';
import { fetchWithSession } from '../../lib/publicApi';
import type { ResponseHistoryItem } from '../../features/survey/types';
import ResponseHistoryList from '../../features/survey/ResponseHistoryList';

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
      <div className="public-container">
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <p>読み込み中...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="public-container">
        <div className="public-card" style={{ textAlign: 'center' }}>
          <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>エラー</h1>
          <p>{error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="public-container">
      <div className="public-card">
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', textAlign: 'center' }}>回答履歴</h1>
        <ResponseHistoryList history={history} />
      </div>
    </div>
  );
};

export default ResponseHistoryPage;
