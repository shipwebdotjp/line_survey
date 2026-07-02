import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';

const features = [
  {
    title: 'LINE表示名で名寄せ',
    description: '初回アクセス時に表示名を照合し、氏名やメールアドレスの入力負担を減らします。',
  },
  {
    title: '回答控えを自動送信',
    description: '送信後は回答内容の控えと修正用URLを記載して修正も可能。',
  },
  {
    title: 'CSV出力まで見据えた設計',
    description: '管理画面も充実。アンケートの回答､要約が見られるのはもちろん、CSVエクスポートも可能。',
  },
];

const steps = [
  {
    title: '1. URLをLINEで共有',
    description: 'LINEからアクセスすることで、LINEアカウントで自動的にログイン',
  },
  {
    title: '2. 本人情報を確認',
    description: '表示と名簿を突き合わせ、必要なときだけ名前とメールを手入力。',
  },
  {
    title: '3. アンケートに回答',
    description: 'アンケートフォームに入力して送信。完了後は控えがメールで送信されます。',
  },
];

const highlights = [
  '身内向けに、手軽に回答してもらえるアンケート',
  '面倒なログインをせずすぐに回答に入れる設計',
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
              LINEログインした表示名から個人を特定し､回答者が名前やメールアドレスを入力する手間を減らすことができる回答しやすさを
              第一に考えたアンケートサービス。回答控えも送れるので、自分が送ったか､何を送ったかを後から確認できて安心です。
            </p>

            <div className="public-home-actions">
              <Link className="public-btn public-btn-primary" to="/admin/surveys/new">
                アンケート作成
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
                <span className="public-home-panel-kicker">アンケートの流れ</span>
              </div>

              <div className="public-home-stacked-cards">
                <div className="public-home-mini-card">
                  <span className="public-home-mini-label">01</span>
                  <strong>LINEログイン</strong>
                  <p>LINE内ならそのまま、外部ブラウザでもLINEログイン経由でログイン。</p>
                </div>
                <div className="public-home-mini-card">
                  <span className="public-home-mini-label">02</span>
                  <strong>自動名寄せ</strong>
                  <p>LINE表示で突き合わせて、氏名・メールを補完します。</p>
                </div>
                <div className="public-home-mini-card public-home-mini-card-accent">
                  <span className="public-home-mini-label">03</span>
                  <strong>回答完了メール</strong>
                  <p>送信後は控えがメールで送信されます。</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="public-home-section">
          <div className="public-home-section-heading">
            <h2>サービスの特徴</h2>
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
            <h2>プライバシーへの配慮</h2>
            <p>
              このサービスは、LINEグループ内の限定利用を前提にしています。
              収集・保存する情報は、プライバシーポリシーページをご覧ください。
            </p>
          </div>
          <Link className="public-btn public-btn-primary" to="/privacy-policy">
            プライバシーポリシーを見る
          </Link>
        </section>
      </div>
    </div>
  );
};

export default PublicHomePage;
