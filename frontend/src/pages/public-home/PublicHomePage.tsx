import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';

const features = [
  {
    title: 'LINE表示名で名寄せ',
    description: '初回アクセス時に表示名を照合し、氏名やメールアドレスの入力負担を減らします。',
  },
  {
    title: '回答控えを自動送信',
    description: '送信後は回答内容の控えをメールで返し、修正用URLがある場合は案内します。',
  },
  {
    title: 'CSV出力まで見据えた設計',
    description: '管理者側では回答一覧・CSV出力・複製運用を前提にした構成です。',
  },
];

const steps = [
  {
    title: '1. LINEで開く',
    description: 'LIFFからアクセスすると、LINEアカウントに紐づいた回答体験になります。',
  },
  {
    title: '2. 本人情報を確認',
    description: '表示名が一致すれば自動で名寄せし、必要なときだけ手入力します。',
  },
  {
    title: '3. そのまま回答',
    description: 'SurveyJSで定義した設問に回答し、完了後は控えを受け取れます。',
  },
];

const highlights = [
  '身内向けの運用を前提にした、軽量なアンケート体験',
  '公開URLは推測しづらい `public_id` を採用',
  '回答者の識別とメール送信を一連の流れで処理',
];

const PublicHomePage: React.FC = () => {
  useEffect(() => {
    document.title = 'アンケートフォーム | LINE連携アンケート';
  }, []);

  return (
    <div className="public-home">
      <div className="public-home-inner">
        <section className="public-home-hero">
          <div className="public-home-copy">
            <div className="public-home-badge">LINE連携アンケート / 回答者名寄せ / CSV出力</div>
            <h1 className="public-home-title">
              LINEでそのまま答えられる、
              <br />
              身内向けアンケートサービス
            </h1>
            <p className="public-home-lead">
              表示名ベースの名寄せで入力を減らし、SurveyJSで柔軟に設計した設問へそのまま回答できます。
              回答後は控えのメール送信まで、ひとつの流れでまとめています。
            </p>

            <div className="public-home-actions">
              <Link className="public-btn public-btn-primary" to="/s">
                回答履歴を見る
              </Link>
              <Link className="public-btn public-btn-secondary" to="/respondent/edit?return_to=%2F">
                本人情報を編集
              </Link>
              <Link className="public-btn public-btn-secondary" to="/about-us">
                利用目的を見る
              </Link>
            </div>

            <ul className="public-home-highlights">
              {highlights.map((item) => (
                <li key={item}>{item}</li>
              ))}
            </ul>
          </div>

          <div className="public-home-visual" aria-hidden="true">
            <div className="public-home-orb public-home-orb-one" />
            <div className="public-home-orb public-home-orb-two" />

            <div className="public-home-panel">
              <div className="public-home-panel-top">
                <span className="public-home-panel-kicker">Survey flow</span>
                <span className="public-home-panel-status">ready</span>
              </div>

              <div className="public-home-stacked-cards">
                <div className="public-home-mini-card">
                  <span className="public-home-mini-label">01</span>
                  <strong>LINEログイン</strong>
                  <p>外部ブラウザでもそのまま回答へ進めます。</p>
                </div>
                <div className="public-home-mini-card">
                  <span className="public-home-mini-label">02</span>
                  <strong>自動名寄せ</strong>
                  <p>表示名一致なら氏名・メールを補完します。</p>
                </div>
                <div className="public-home-mini-card public-home-mini-card-accent">
                  <span className="public-home-mini-label">03</span>
                  <strong>回答完了メール</strong>
                  <p>送信後は控えを送って、後追い確認しやすくします。</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="public-home-section">
          <div className="public-home-section-heading">
            <span className="public-home-section-kicker">What you get</span>
            <h2>何ができるサービスか</h2>
          </div>

          <div className="public-home-grid">
            {features.map((feature) => (
              <article key={feature.title} className="public-home-card">
                <h3>{feature.title}</h3>
                <p>{feature.description}</p>
              </article>
            ))}
          </div>
        </section>

        <section className="public-home-section">
          <div className="public-home-section-heading">
            <span className="public-home-section-kicker">How it works</span>
            <h2>使い方</h2>
          </div>

          <div className="public-home-steps">
            {steps.map((step) => (
              <article key={step.title} className="public-home-step">
                <h3>{step.title}</h3>
                <p>{step.description}</p>
              </article>
            ))}
          </div>
        </section>

        <section className="public-home-note">
          <div>
            <span className="public-home-section-kicker">Privacy</span>
            <h2>利用目的と保存情報を明示しています</h2>
            <p>
              このサービスは、LINEグループ内の限定利用を前提にしています。
              保存する情報や管理者の考え方は、About Usページにまとめています。
            </p>
          </div>
          <Link className="public-btn public-btn-primary" to="/about-us">
            About Us を見る
          </Link>
        </section>
      </div>
    </div>
  );
};

export default PublicHomePage;
