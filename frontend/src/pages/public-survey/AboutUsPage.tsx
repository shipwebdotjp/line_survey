import React, { useEffect } from 'react';

const AboutUsPage: React.FC = () => {
  useEffect(() => {
    document.title = 'About Us | アンケートフォーム';
  }, []);

  return (
    <div className="public-container">
      <div className="public-card">
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1.5rem' }}>About Us</h1>
        <p style={{ lineHeight: '1.6', color: '#4b5563' }}>
          LINEと連携してアンケートに回答する際の煩雑なお名前やメールアドレスの入力の手間を省いていただけるアンケートフォームです。          
        </p>
        <p style={{ marginTop: '1rem', lineHeight: '1.6', color: '#4b5563' }}>
          LINE表示名をLINE連携時に取得し、あらかじめ管理者が把握しているお名前とメールアドレスのリストと突き合わせることで、ご本人かどうかを特定しています。
        </p>
        <p style={{ marginTop: '1rem', lineHeight: '1.6', color: '#4b5563' }}>
          ご不明な点は管理者までお問い合わせください。
        </p>
      </div>
    </div>
  );
};

export default AboutUsPage;
