import React, { useEffect } from 'react';

const AboutUsPage: React.FC = () => {
  useEffect(() => {
    document.title = 'About Us | Survey System';
  }, []);

  return (
    <div className="public-container">
      <div className="public-card">
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1.5rem' }}>About Us</h1>
        <p style={{ lineHeight: '1.6', color: '#4b5563' }}>
          Survey System は、LINE を活用した手軽なアンケート回答プラットフォームです。
          ユーザーの皆様からの貴重な意見を収集し、より良いサービスの向上に役立てています。
        </p>
        <p style={{ marginTop: '1rem', lineHeight: '1.6', color: '#4b5563' }}>
          お問い合わせ等がある場合は、公式 LINE アカウントまでご連絡ください。
        </p>
      </div>
    </div>
  );
};

export default AboutUsPage;
