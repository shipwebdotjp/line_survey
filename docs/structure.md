# ディレクトリ構成

本書は、共用サーバーでも運用しやすいように、`public_html/` を唯一の公開ルートとする構成を定める。  
フロントエンドの起点は `public_html/index.html`、API の公開入口は `public_html/api/index.php` とする。  
バックエンド本体は `public_html/` の外に置き、公開ルートから直接参照されない前提で設計する。

## 1. 基本方針

- 公開ルートは `public_html/` のみとする
- フロントエンドの起点は `public_html/index.html` とする
- API の公開入口は `public_html/api/index.php` とする
- `backend/` は公開ルート外に置く
- `backend/public/index.php` はバックエンド内部のフロントコントローラとし、公開URLの本体にしない
- ビジネスロジック、設定、ドキュメント、テストは公開ルート外に置く
- 画面実装は `frontend/`、PHP 実装は `backend/` に分離する
- ディレクトリ名は `kebab-case`
- PHP のクラス名は `PascalCase`
- React のコンポーネント名は `PascalCase`

## 2. ルート構成

```text
/
├── public_html/
├── backend/
├── frontend/
├── docs/
├── tests/
├── php/
├── docker-compose.yml
└── Makefile
```

### 2.1 `public_html/`

公開されるファイルのみを置く。

```text
public_html/
├── index.html
├── assets/
└── api/
    └── index.php
```

- `index.html` は React アプリのエントリポイント
- `assets/` は静的アセット置き場
- `api/index.php` は API の公開入口
- `api/index.php` はバックエンド本体へ処理を委譲するだけにする
- ビジネスロジックや設計書は置かない

### 2.2 `backend/`

Slim ベースの PHP API 実装を置く。

```text
backend/
├── public/
│   └── index.php
├── bootstrap/
├── config/
├── routes/
├── src/
│   ├── Application/
│   ├── Domain/
│   ├── Infrastructure/
│   └── Presentation/
├── storage/
└── composer.json
```

- `public/index.php` は PHP のフロントコントローラ
- `bootstrap/` は DI 初期化、環境変数読み込み、Slim アプリ生成を置く
- `config/` は設定値をまとめる
- `routes/` は API ルート定義を置く
- `src/Application/` はユースケース層を置く
- `src/Domain/` はエンティティ、値オブジェクト、ドメインサービスを置く
- `src/Infrastructure/` は DB、外部 API、メール送信などを置く
- `src/Presentation/` は HTTP 入出力の変換を置く
- `storage/` はログ、CSV 一時ファイル、アップロード一時領域を置く

### 2.3 `frontend/`

React / Vite / TypeScript の画面実装を置く。

```text
frontend/
├── src/
│   ├── app/
│   ├── features/
│   ├── pages/
│   ├── components/
│   ├── hooks/
│   ├── lib/
│   └── styles/
├── public/
├── index.html
├── vite.config.ts
└── package.json
```

- `src/app/` はアプリ全体の初期化とルーティングを置く
- `src/features/` は機能単位の実装を置く
- `src/pages/` は画面単位のコンポーネントを置く
- `src/components/` は共通コンポーネントを置く
- `src/hooks/` は共通フックを置く
- `src/lib/` は API クライアント、LIFF 初期化、ユーティリティを置く
- `src/styles/` は共通スタイルを置く

### 2.4 `docs/`

仕様・設計・実装計画・構成定義を置く。

```text
docs/
├── requirements.md
├── spec.md
├── design.md
├── task.md
└── structure.md
```

- `requirements.md` は要件定義
- `spec.md` は仕様
- `design.md` は設計
- `task.md` は実装タスクリスト
- `structure.md` は本書

### 2.5 `tests/`

テストを集約する。

```text
tests/
├── backend/
├── frontend/
└── fixtures/
```

- `tests/backend/` は PHP のテストを置く
- `tests/frontend/` は React 側のテストを置く
- `tests/fixtures/` は共通のテストデータを置く

## 3. バックエンド詳細構成

`backend/src/` は機能別に分ける。  
回答者向け、管理者向け、共通基盤を同じ階層に混ぜない。

```text
backend/src/
├── Application/
│   ├── Admin/
│   ├── Survey/
│   ├── Response/
│   └── Respondent/
├── Domain/
│   ├── Survey/
│   ├── Response/
│   └── Respondent/
├── Infrastructure/
│   ├── Database/
│   ├── Line/
│   ├── Mail/
│   └── Csv/
└── Presentation/
    ├── Http/
    ├── Middleware/
    └── Serializer/
```

### 3.1 `Application/`

- ユースケースを置く
- 入力検証後の業務処理を担当する
- API 1 本につき 1 ユースケースを原則とする

### 3.2 `Domain/`

- `Survey`、`Response`、`Respondent` のルールを置く
- DB 依存のない純粋な業務ロジックを置く
- 名寄せ、編集可否、公開可否などの判定を置く

### 3.3 `Infrastructure/`

- DB アクセス実装を置く
- LINE ID Token 検証を置く
- メール送信を置く
- CSV 生成や外部 I/O を置く

### 3.4 `Presentation/`

- HTTP Request / Response の変換を置く
- Controller 相当の処理を置く
- エラーレスポンス整形を置く

## 4. フロントエンド詳細構成

画面軸ではなく機能軸で分ける。  
公開回答、編集、管理画面の責務を混ぜない。

```text
frontend/src/
├── app/
│   ├── routes/
│   ├── providers/
│   └── bootstrap/
├── features/
│   ├── liff/
│   ├── survey/
│   ├── response/
│   ├── admin/
│   └── auth/
├── pages/
│   ├── public-survey/
│   ├── response-edit/
│   └── admin/
├── components/
├── hooks/
├── lib/
└── styles/
```

- `features/liff/` は LIFF 初期化と認証を置く
- `features/survey/` は SurveyJS 連携を置く
- `features/response/` は回答送信・編集・再訪表示を置く
- `features/admin/` は管理画面の機能を置く
- `features/auth/` は管理画面認証などを置く

## 5. ファイル命名

- ディレクトリ名は `kebab-case`
- React コンポーネントは `PascalCase.tsx`
- PHP クラスは `PascalCase.php`
- 関数・変数は `camelCase`
- 定数は `UPPER_SNAKE_CASE`
- 設定ファイルは用途が分かる名前にする

例:

```text
frontend/src/pages/public-survey/PublicSurveyPage.tsx
backend/src/Application/Survey/CreateSurvey.php
backend/src/Infrastructure/Mail/ResendMailer.php
```

## 6. 公開入口の考え方

本構成では、公開URLを `public_html/` 配下に限定する。  
そのため、`backend/public/index.php` は本番URLとして直接公開しない。

- フロントの公開入口は `public_html/index.html`
- API の公開入口は `public_html/api/index.php`
- `public_html/api/index.php` は `backend/` の bootstrap や front controller を呼び出す
- 共用サーバーで vhost 設定の自由度が低い場合でも、この構成なら運用しやすい

## 8. 守るべき境界

- `public_html/` に `.php` の業務ロジックを置かない
- `docs/` に実装コードを置かない
- `tests/` に本番コードを置かない
- `frontend/` と `backend/` を横断する共通処理は、片側に寄せず境界を明示する
- 公開ルートから非公開情報が参照できる形にしない
