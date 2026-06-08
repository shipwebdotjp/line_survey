import React from 'react';

interface LiffErrorProps {
  error: Error;
}

const LiffError: React.FC<LiffErrorProps> = ({ error }) => {
  const isDev = import.meta.env.DEV;

  return (
    <div style={{ padding: '2rem', textAlign: 'center' }}>
      <h1 style={{ color: '#c53030' }}>LIFF初期化エラー</h1>
      <p>申し訳ありませんが、エラーが発生しました。</p>
      {isDev && (
        <div style={{ marginTop: '2rem', padding: '1rem', border: '1px solid #ccc', backgroundColor: '#f9f9f9', textAlign: 'left', color: 'black' }}>
          <h3>開発者向けエラー詳細:</h3>
          <p>{error.message}</p>
        </div>
      )}
    </div>
  );
};

export default LiffError;
