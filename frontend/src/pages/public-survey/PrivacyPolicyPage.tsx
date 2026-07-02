import React, { useEffect } from 'react';

const PrivacyPolicyPage: React.FC = () => {
  useEffect(() => {
    document.title = 'プライバシーポリシー | アンケートフォーム';
  }, []);

  return (
    <div className="public-container">
      <div className="public-card">
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1.5rem' }}>プライバシーポリシー</h1>

        <section style={{ lineHeight: '1.7', color: '#4b5563' }}>
          <p>
            本サービスでは、LINE連携アンケートの提供と本人確認、回答内容の保存および送信のために、必要最小限の情報を取り扱います。
          </p>
        </section>

        <section style={{ marginTop: '1.25rem', lineHeight: '1.7', color: '#4b5563' }}>
          <h2 style={{ fontSize: '1.1rem', marginBottom: '0.5rem', color: '#111827' }}>取得する情報</h2>
          <ul style={{ paddingLeft: '1.25rem', margin: 0 }}>
            <li>LINEプロフィール情報（内部識別子※、名前、アイコン用画像）</li>
            <li>氏名、メールアドレス、敬称</li>
            <li>アンケートの回答内容、回答日時</li>
          </ul>
        </section>

        <section style={{ marginTop: '1.25rem', lineHeight: '1.7', color: '#4b5563' }}>
          <h2 style={{ fontSize: '1.1rem', marginBottom: '0.5rem', color: '#111827' }}>利用目的</h2>
          <ul style={{ paddingLeft: '1.25rem', margin: 0 }}>
            <li>LINE表示名と名簿情報を照合して回答者を特定するため</li>
            <li>アンケートの回答を保存し、必要に応じて編集できるようにするため</li>
            <li>回答完了メールや回答控えを送信するため</li>
            <li>管理画面で回答の集計・確認・CSV出力を行うため</li>
          </ul>
        </section>

        <section style={{ marginTop: '1.25rem', lineHeight: '1.7', color: '#4b5563' }}>
          <h2 style={{ fontSize: '1.1rem', marginBottom: '0.5rem', color: '#111827' }}>情報の管理</h2>
          <p>
            取得した情報は、アンケート運営に必要な範囲で管理者が取り扱います。法令に基づく場合を除き、本人の同意なく第三者へ提供しません。
          </p>
        </section>

        <section style={{ marginTop: '1.25rem', lineHeight: '1.7', color: '#4b5563' }}>
          <h2 style={{ fontSize: '1.1rem', marginBottom: '0.5rem', color: '#111827' }}>お問い合わせ</h2>
          <p>ご不明な点は、サービス管理者までお問い合わせください。</p>
        </section>
      </div>
    </div>
  );
};

export default PrivacyPolicyPage;
