import React from 'react';
import { Link } from 'react-router-dom';
import type { ResponseHistoryItem } from './types';

export const formatHistoryDate = (value: string): string => {
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

interface Props {
  history: ResponseHistoryItem[] | null;
  emptyMessage?: string;
}

const ResponseHistoryList: React.FC<Props> = ({ history, emptyMessage = 'まだ回答履歴がありません。' }) => {
  if (!history || history.length === 0) {
    return (
      <div style={{ padding: '1.5rem', textAlign: 'center', color: '#6b7280', background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: '10px' }}>
        <p>{emptyMessage}</p>
      </div>
    );
  }

  return (
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
                  color: '#4f46e5',
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
  );
};

export default ResponseHistoryList;
