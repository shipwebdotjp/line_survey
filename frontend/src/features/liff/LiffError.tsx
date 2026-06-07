import React from 'react';

interface LiffErrorProps {
  error: Error;
}

const LiffError: React.FC<LiffErrorProps> = ({ error }) => {
  const isDev = import.meta.env.DEV;

  if (error.message === 'Outside LIFF') {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <h1>LINEでのアクセスが必要です</h1>
        <p>このアンケートはLINEアプリ内からのみ回答いただけます。</p>
        {isDev && (
          <div style={{ marginTop: '2rem', padding: '1rem', border: '1px solid #ccc', backgroundColor: '#f9f9f9', textAlign: 'left' }}>
            <h3>開発者向け情報:</h3>
            <p>LIFF外ブラウザからアクセスされています。実機またはLIFFシミュレーターを使用してください。</p>
          </div>
        )}
      </div>
    );
  }

  return (
    <div style={{ padding: '2rem', textAlign: 'center', color: 'red' }}>
      <h1>LIFF初期化エラー</h1>
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
