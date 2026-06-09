import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useLiffContext } from '../../features/liff/LiffContext';
import { fetchWithSession } from '../../lib/publicApi';
import type { ResponseHistoryItem } from '../../features/survey/types';
import { createLiffUrl } from '../../lib/liffUrl';
import Footer from '../../features/survey/Footer';

const formatHistoryDate = (value: string): string => {
  const directMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);

  if (directMatch) {
    const [, year, month, day, hour, minute] = directMatch;
    return `${year}/${month}/${day} ${hour}:${minute}`;
  }

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return value;
  }

  const pad = (input: number): string => String(input).padStart(2, '0');
  return `${parsedDate.getFullYear()}/${pad(parsedDate.getMonth() + 1)}/${pad(parsedDate.getDate())} ${pad(parsedDate.getHours())}:${pad(parsedDate.getMinutes())}`;
};

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
    <div style={{ padding: '1rem', maxWidth: '720px', margin: '0 auto' }}>
      <h1 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', textAlign: 'center' }}>回答履歴</h1>

      {!history || history.length === 0 ? (
        <div style={{ padding: '1.5rem', textAlign: 'center', color: '#6b7280', background: '#fff', border: '1px solid #e5e7eb', borderRadius: '10px' }}>
          <p>まだ回答履歴がありません。</p>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
          {history.map((item, index) => (
            <div
              key={index}
              style={{
                border: '1px solid #e5e7eb',
                borderRadius: '10px',
                padding: '0.9rem 1rem',
                backgroundColor: '#fff',
                boxShadow: 'none'
              }}
            >
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.35rem' }}>
                {item.survey_public_id && item.edit_token ? (
                  <Link
                    to={`/s/${item.survey_public_id}/r/${item.edit_token}`}
                    style={{
                      color: '#111827',
                      fontSize: '1rem',
                      fontWeight: 600,
                      textDecoration: 'none',
                      lineHeight: 1.4,
                    }}
                  >
                    {item.survey_title || '削除済みアンケート'}
                  </Link>
                ) : (
                  <span style={{ color: '#111827', fontSize: '1rem', fontWeight: 600, lineHeight: 1.4 }}>
                    {item.survey_title || '削除済みアンケート'}
                  </span>
                )}

                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.25rem 1rem', fontSize: '0.8rem', color: '#6b7280' }}>
                  <div>
                    回答日時: <time dateTime={item.submitted_at}>{formatHistoryDate(item.submitted_at)}</time>
                  </div>
                  {item.updated_at !== item.submitted_at && (
                    <div>
                      更新日時: <time dateTime={item.updated_at}>{formatHistoryDate(item.updated_at)}</time>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
      <Footer />
    </div>
  );
};

export default ResponseHistoryPage;
