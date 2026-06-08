# LINE連携アンケートフォーム MVP仕様

## 1. 目的

LINEグループ内の身内向けに、LIFF経由で回答者を識別し、事前登録マスターとLINE表示名で自動名寄せするアンケートフォームを作る。

主な目的:

- 回答者の入力負担を減らす
- LINE表示名から氏名・メール・敬称を自動補完する
- SurveyJSでアンケートフォームを柔軟に定義する
- 回答内容をメールで自動送信する
- 管理画面から回答一覧表示とCSV出力を行う

## 2. 技術構成

### フロントエンド

- React
- Vite
- TypeScript
- SurveyJS
- LIFF SDK

用途:

- 回答者用アンケート画面
- LIFFログイン
- SurveyJSフォーム表示
- 管理画面

Reactは静的ビルドしてCoreserverに配置する。

### バックエンド

- PHP 8.3
- Slim Framework
- Composer
- Guzzle
- phpdotenv

用途:

- APIルーティング
- LIFF ID Token検証
- 自動名寄せ
- 回答保存
- CSV出力
- メール送信API連携

### DB

- MariaDB / MySQL

### メール送信

第一候補:

- Resend API

第二候補:

- SendGrid API

### デプロイ先

- Coreserver Core-X

## 3. アンケート仕様

### アンケートURL

アンケートごとに、連番IDではなく推測困難な `public_id` を発行する。

回答者用URL:

`https://example.com/s/{public_id}`

例:

`https://example.com/s/sv_p7Kf92LpQaXz3MnT8bQYv2A`

内部管理用には連番の `id` を使う。

### public_id生成

`sv_ + URL-safe Base64 random 16 bytes`

```php
function generatePublicId(): string
{
    return 'sv_' . rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
}
```

### アンケート定義

SurveyJS JSONをそのままDBに保存する。

- `surveys.questions_json`

回答データもSurveyJSの回答JSONをそのまま保存する。

- `responses.answer_json`

## 4. アンケート編集ルール

- `questions_json` は回答が 1 件以上あっても編集可能
- 保存済み回答との整合性は `responses.survey_snapshot_json` で保持する
- 設問構造を更新しても、過去回答の表示やCSV出力は回答時点の定義を基準にする

回答後も編集可能な項目:

- タイトル
- 説明
- ステータス
- 開始日時
- 終了日時
- allow_multiple
- allow_edit
- メール送信設定

設問を変更したい場合:

既存アンケートを複製して、新しいアンケートとして作成する。

複製時にコピーするもの:

- title
- description
- questions_json
- allow_multiple
- allow_edit
- メール設定

複製時にコピーしないもの:

- responses
- public_id
- created_at
- updated_at

## 5. アンケート公開状態

`status` は自動変更しない。

- draft: 下書き
- published: 公開中
- closed: 回答終了
- archived: アーカイブ

回答可能なのは原則:

- `status = published`

回答期限:

- `starts_at`
- `ends_at`

ただし、期限が来ても `status` は自動変更しない。
回答可否判定は都度行う。

- `starts_at` 前 → 回答不可
- `ends_at` 後 → 回答不可

## 6. 回答回数・編集仕様

アンケートごとに以下を設定する。

- `allow_multiple` bool
- `allow_edit` bool

組み合わせごとの挙動:

| ALLOW_MULTIPLE | ALLOW_EDIT | 挙動 |
|---|---|---|
| false | false | 1人1回。回答後は前回回答を表示。編集不可 |
| false | true | 1人1回。既存回答を編集可 |
| true | false | 複数回答可。毎回新規回答。編集不可 |
| true | true | 複数回答可。回答ごとに編集用URLを発行 |

1人1回で編集不可の場合:

回答済み画面には、

- 前回の回答内容を表示
- 編集ボタンは出さない

複数回答時:

- 1回答 = 1 response
- 同じ respondent が同じ survey に複数 responses を持てる

## 7. 編集用URL仕様

`edit_token`

各回答 response に推測困難な `edit_token` を発行する。

編集URL:

`https://example.com/s/{public_id}/r/{edit_token}/edit`

### edit_token生成

```php
function generateEditToken(): string
{
    return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}
```

### 編集用URLのアクセス制御

編集には以下の両方を必要とする。

1. `edit_token` が一致する
2. LIFFログイン中の `respondent_id` が `response.respondent_id` と一致する

つまり、URLを知っているだけでは編集不可。
本人のLINEログインも必要。

### 編集URLの表示・送信

`allow_edit = true` の場合のみ:

- 回答完了画面に編集URLを表示
- 回答控えメールにも編集URLを記載

`allow_edit = false` の場合は編集URLを表示・送信しない。

### 編集期限

`ends_at` を過ぎたら、

- 新規回答不可
- 編集不可

とする。

## 8. 回答者名寄せ仕様

### 基本方針

LIFFで取得したLINE表示名を使って、事前登録マスターと完全一致で名寄せする。

`LIFF displayName`
↓ 完全一致
`respondent_masters.line_display_name`

### 識別の優先順位

1. 既存 respondent がいる場合

   `line_user_id` が `respondents` に既に存在する場合:

   - `respondents` の氏名・メール・敬称を使用
   - LINE表示名が変わっていても、 `line_user_id` を正とする
   - `line_display_name` は LIFF で取得した最新値に更新する

2. 初回アクセスの場合

   `line_user_id` が未登録なら、

   `LIFF displayName` で `respondent_masters.line_display_name` を検索する。
   一致すれば、

   - name
   - email
   - honorific
   - respondent_master_id

   を `respondents` に保存する。

3. 名寄せ失敗時

   回答不可にはしない。
   本人に以下を手入力してもらう。

   - 氏名
   - メールアドレス
   - 敬称

   メールアドレスは必須。
   手入力情報は `respondents` に保存する。
   `respondent_masters` には自動追加しない。

### 手入力された respondent の次回扱い

次回以降、同じLINEユーザーがアクセスした場合は、

- `line_user_id` が既に `respondents` にあれば、
- `respondents` の氏名・メール・敬称を使う
- 再入力は不要

### 回答者情報の更新

- `line_user_id` に紐づく `respondents` が存在する場合、`line_display_name` は毎回最新値に更新する
- 氏名・メールアドレス・敬称は本人による修正対象にしない

### 名寄せ成功後の修正

MVPでは、名寄せ成功後に本人が氏名・メール・敬称を修正する機能は持たない。

### 手入力情報の修正

MVPでは、手入力で登録された情報も本人による修正は不可。

必要になったら後で追加する。

## 9. respondent_masters CSVインポート

### CSV形式

```csv
master_code,line_display_name,name,email,honorific,note
001,慎平,山田太郎,shinpei@example.com,さん,備考
```

### 必須項目

- master_code
- line_display_name
- name
- email

### 任意項目

- honorific
- note

### CSVインポート時の更新キー

- master_code

`master_code` が既に存在する場合は更新。
`master_code` が存在しない場合は新規追加。

### CSVインポートのエラー処理

- 一部の行にエラーがあっても、正しく取り込める行は保存する
- エラーになった行は一覧で返す
- エラー行の例:
  - 必須項目不足
  - `line_display_name` の重複
  - 形式不正

### LINE表示名の重複

CSVインポート時に、

- `line_display_name` が重複していたらエラー

とする。

理由:

- LINE表示名で自動名寄せするため、重複があると誤判定になる

## 10. 敬称の扱い

`honorific` を持つ。

用途:

- メール宛名
- 管理画面表示
- CSV出力

メール宛名:

`{name}{honorific}`

例:

`山田太郎さん`

`honorific` が空の場合は、

`{name}さん`
をフォールバックにする。

## 11. メール仕様

### メール送信タイミング

- 新規回答時
- 編集回答時

どちらも回答控えメールを送る。

### メール送信内容

回答者に回答内容の控えを送る。

件名例:

- 【回答控え】{アンケート名}
- 【回答修正控え】{アンケート名}

本文に含めるもの:

- 宛名
- アンケート名
- 回答日時
- 回答内容
- 編集URL `allow_edit=true` の場合のみ

### メールアドレス

- 必須
- 名寄せ成功時はマスター由来のメールアドレスを使う
- 名寄せ失敗時は本人に入力させる

### メール送信失敗時

- 回答保存を優先
- メール送信失敗はログ・DBに記録
- 回答自体は成功扱いにする

## 12. CSV出力仕様

### 基本方針

- 1 response = 1行
- 複数回答可の場合、同じ回答者が複数行に出る

### 編集済み回答

- 最新の回答内容のみ出力
- 編集履歴はMVPでは持たない

### 回答日時の扱い

- `submitted_at` は初回回答時刻として保持する
- 編集時は `submitted_at` を上書きしない
- 編集時の最終更新は `updated_at` で表す

### CSV列

- 回答ID
- 初回回答日時
- 最終更新日時
- LINE表示名
- 氏名
- 敬称
- メール
- 各設問の回答

### CSV形式

- 文字コード: UTF-8 BOM付き
- 改行コード: CRLF
- 複数選択: `;` 区切り
- 日時: Asia/Tokyo

### CSV列生成

CSVの設問列は、`responses.survey_snapshot_json` を基準に生成する。
`questions_json` は回答後も編集できるため、列ズレは回答時点の定義で防ぐ。

## 13. 回答時点のアンケート定義保存

各 response には回答時点のSurveyJS JSONを保存する。

- `responses.survey_snapshot_json`

### 目的

- 設問定義の証跡
- 将来的なバージョン管理への備え
- 万一の編集事故への保険

## 14. 管理画面仕様

### アンケート管理

機能:

- 一覧
- 新規作成
- 編集
- 削除
- 複製
- 回答者用URLコピー

削除の扱い:

- アンケートは回答が0件のときのみ削除可能とする
- 回答が1件以上あるアンケートは削除せず、必要に応じてアーカイブ運用とする

編集項目:

- タイトル
- 説明
- status
- starts_at
- ends_at
- allow_multiple
- allow_edit
- send_confirmation_email
- include_answers_in_email
- questions_json

ただし、回答が1件以上ある場合も、

- `questions_json` 編集可能

### SurveyJS JSON編集

MVPでは簡易JSON編集。

- textarea
- JSONバリデーション
- 保存

将来的に必要なら、

- Monaco Editor
- CodeMirror
- JSONEditor

を導入する。

### 回答管理

アンケートごとに `responses` 単位で以下を表示する。

- 回答一覧
  - 1 response = 1 行
  - 同一回答者の複数 response は重複して表示する
  - 氏名・LINE表示名・回答日時・詳細リンクを表示する
- 回答詳細
  - 選択した response の回答内容を表示する
  - 同一回答者の response 切り替え UI は持たない
- 回答詳細API
  - `GET /api/admin/surveys/{id}/responses/{response_id}`
  - `respondent` はネストしたオブジェクトで返す
  - `respondent` には `name`, `email`, `line_display_name`, `honorific`, `is_manually_entered`, `respondent_master_id` を含める
  - `survey_snapshot_json` はDBに保存されている内容をそのまま返す
  - `survey` が存在しない場合、または対象 `response` が存在しない場合は 404 を返す
  - path ID が不正な場合は `VALIDATION_ERROR` の 400 を返す
- CSVダウンロード

### respondent_masters 管理

MVPではCSVインポート対応。

機能:

- CSVインポート
- インポート時の重複チェック
- 一覧表示

## 15. 認証・セキュリティ

### 回答者認証

LIFFログインを使用する。

フロントで取得:

- `liff.getIDToken()`

バックエンドでLINE APIに検証する。

`ID Token検証`
↓
`line_user_id / display_name取得`
↓
`respondent識別`

### 管理画面認証

MVPではBasic認証。

対象:

- `/admin`
- `/api/admin`

### API認証

回答者側APIは、LIFF ID Tokenを用いて本人確認する。

編集時はさらに、

- `edit_token`
- `respondent_id`

の一致を確認する。

## 16. API設計

### 回答者側

- `POST /api/liff/identify`
- `GET /api/surveys/public/{public_id}`
- `POST /api/surveys/public/{public_id}/responses`
- `GET /api/surveys/public/{public_id}/responses/current`
- `GET /api/surveys/public/{public_id}/responses/{edit_token}`
- `PUT /api/surveys/public/{public_id}/responses/{edit_token}`

補足:

- `responses/current` は、現在の回答者に紐づく最新の回答を指す

### 管理側

- `GET /api/admin/surveys`
- `POST /api/admin/surveys`
- `GET /api/admin/surveys/{id}`
- `PUT /api/admin/surveys/{id}`
- `DELETE /api/admin/surveys/{id}`
- `POST /api/admin/surveys/{id}/duplicate`
- `GET /api/admin/surveys/{id}/responses`
- `GET /api/admin/surveys/{id}/responses/{response_id}`
- `GET /api/admin/surveys/{id}/responses.csv`
- `POST /api/admin/respondent-masters/import`
- `GET /api/admin/respondent-masters`

## 17. DB設計案

### surveys

```sql
CREATE TABLE surveys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  public_id VARCHAR(64) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  questions_json JSON NOT NULL,

  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  allow_multiple TINYINT(1) NOT NULL DEFAULT 0,
  allow_edit TINYINT(1) NOT NULL DEFAULT 0,

  starts_at DATETIME NULL,
  ends_at DATETIME NULL,

  send_confirmation_email TINYINT(1) NOT NULL DEFAULT 1,
  include_answers_in_email TINYINT(1) NOT NULL DEFAULT 1,

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  UNIQUE KEY unique_public_id (public_id)
);
```

### respondent_masters

```sql
CREATE TABLE respondent_masters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  master_code VARCHAR(100) NOT NULL,
  line_display_name VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  honorific VARCHAR(50) NULL,
  note TEXT NULL,

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  UNIQUE KEY unique_master_code (master_code),
  UNIQUE KEY unique_line_display_name (line_display_name)
);
```

### respondents

```sql
CREATE TABLE respondents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  line_user_id VARCHAR(255) NOT NULL,
  line_display_name VARCHAR(255) NOT NULL,
  respondent_master_id INT NULL,

  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  honorific VARCHAR(50) NULL,

  is_manually_entered TINYINT(1) NOT NULL DEFAULT 0,

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  UNIQUE KEY unique_line_user_id (line_user_id),
  INDEX idx_respondent_master_id (respondent_master_id)
);
```

### responses

```sql
CREATE TABLE responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  survey_id INT NOT NULL,
  respondent_id INT NOT NULL,

  edit_token VARCHAR(128) NOT NULL,

  answer_json JSON NOT NULL,
  survey_snapshot_json JSON NULL,

  submitted_at DATETIME NOT NULL,

  email_sent_at DATETIME NULL,
  email_error TEXT NULL,

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  UNIQUE KEY unique_edit_token (edit_token),
  INDEX idx_survey_id (survey_id),
  INDEX idx_respondent_id (respondent_id),
  INDEX idx_survey_respondent (survey_id,
```

## 18. LIFF仕様

### 初期化タイミング

LIFFの初期化（`liff.init()`）は、以下の条件のいずれかを満たす場合にのみ実行する。

- URLに `liff.state=` パラメータが含まれている場合（LIFF内でのリダイレクト復元時）
- パスが `/s/` で始まる場合（アンケート回答・編集画面）

`/` (ランディングページ) や `/admin` (管理画面) への通常アクセス時は、LIFFの初期化を行わない。

### 外部ブラウザ対応

外部ブラウザからのアンケート閲覧・回答を許可する。

- `liff.init()` の際、`withLoginOnExternalBrowser: true` を指定する。
- コード内で明示的に `liff.login()` を呼び出すことは避ける（ログインループ防止のため、LIFF SDKの自動ログイン機能に委ねる）。

### 実装構造

- `LiffContext` / `LiffProvider` をApp直下に配置し、LIFFの状態を一元管理する。
- `LiffGate` コンポーネントにより、初期化中のローディング表示やエラー表示を制御する。
