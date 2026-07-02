# 設計書

## 1. 文書の目的

本書は、`spec.md` を前提に、LINE 連携アンケートフォームの実装方針を整理した設計書である。  
要件との差分が出ないよう、データ構造、処理フロー、API、画面、権限制御を実装可能な粒度で定義する。

## 2. 全体方針

- 回答者向け入口は LIFF とする
- アンケート定義は SurveyJS JSON で管理する
- バックエンドは PHP 8.3 + Slim Framework で実装する
- アンケートは `public_id` で外部公開する
- 回答は `answer_json` と `survey_snapshot_json` を中心に保存する
- 設問テーブルや分岐ルールテーブルは作らない
- 回答者の識別は `respondents` を基準に行う
- 名寄せの参照元マスターは `respondent_masters` とする

## 3. システム構成

### 3.1 コンポーネント

- フロントエンド
  - React
  - Vite
  - TypeScript
  - SurveyJS
  - LIFF SDK
- バックエンド
  - PHP 8.3
  - Slim Framework
  - Composer
  - Guzzle
  - phpdotenv
- データベース
  - MariaDB / MySQL
- メール送信
  - Resend API
  - SendGrid API
- デプロイ先
  - Coreserver Core-X

### 3.2 役割分担

```text
LIFF + SurveyJS
- フォーム表示
- LINEログイン
- LINE表示名取得
- 回答入力

PHPバックエンド
- 名寄せ
- アンケート取得
- 回答保存
- 回答編集
- CSV出力
- メール送信

DB
- respondent_masters
- respondents
- surveys
- responses
```

## 4. データ設計

### 4.1 `respondent_masters`

名寄せ用の事前登録マスターである。  
CSV インポートの更新キーは `master_code` とする。

主なカラム:

- `id`
- `master_code`
- `line_display_name`
- `name`
- `email`
- `honorific`
- `note`
- `created_at`
- `updated_at`

制約:

- `master_code` は一意
- `line_display_name` は一意

補足:

- `line_display_name` の重複は自動名寄せの誤判定につながるため許容しない
- ここには `line_user_id` は持たない

### 4.2 `respondents`

LIFF ログインした回答者の実体を保持する。  
初回の名寄せ成功時や手入力時に作成・更新する。

主なカラム:

- `id`
- `line_user_id`
- `line_display_name`
- `respondent_master_id`
- `name`
- `email`
- `honorific`
- `is_manually_entered`
- `created_at`
- `updated_at`

制約:

- `line_user_id` は一意
- `respondent_master_id` は任意

補足:

- `line_user_id` が既に存在する場合は、それを正とする
- `is_manually_entered = 1` の場合は、マスター未紐付けでも保存可能

### 4.3 `surveys`

SurveyJS の定義と公開状態を保持する。

主なカラム:

- `id`
- `public_id`
- `title`
- `description`
- `questions_json`
- `status`
- `allow_multiple`
- `allow_edit`
- `starts_at`
- `ends_at`
- `send_confirmation_email`
- `include_answers_in_email`
- `created_at`
- `updated_at`

制約:

- `public_id` は一意

補足:

- `status` は `draft` / `published` / `closed` / `archived`
- `questions_json` は回答が 1 件以上あっても編集可能とする
- `public_id` は `sv_` + URL-safe Base64 のランダム 16 bytes で生成する

### 4.4 `responses`

各回答を保持する。

主なカラム:

- `id`
- `survey_id`
- `respondent_id`
- `edit_token`
- `answer_json`
- `survey_snapshot_json`
- `submitted_at`
- `email_sent_at`
- `email_error`
- `created_at`
- `updated_at`

制約:

- `edit_token` は一意
- `survey_id` と `respondent_id` には検索用インデックスを置く
- `edit_token` は URL-safe Base64 のランダム 32 bytes で生成する

## 5. 処理フロー

### 5.1 初回アクセス

1. 回答者が LIFF URL を開く
2. `public_id` から対象アンケートを取得する
3. LIFF が `line_user_id` と `display_name` を取得する
4. フロントが `POST /api/liff/identify` に `id_token` を送信する
5. バックエンドでLINE ID Tokenを検証する
6. `respondents` に `line_user_id` が既に存在するか確認する
7. 存在する場合は `respondents` の `name`, `email`, `honorific` を使用する
8. 存在しない場合は `respondent_masters.line_display_name` を完全一致で検索する
9. 一致したら `respondents` を作成し、`respondent_master_id` を設定する
10. 一致しない場合は `name`, `email`, `honorific` を手入力させ（`POST /api/liff/identify/manual`）、`respondents` に保存する
11. 名寄せ成功後（または手入力完了後）にPHPセッションを確立する
12. 以降のAPI呼び出しはセッションCookieで認証する
13. SurveyJS の `questions_json` を返してフォームを表示する

### 5.2 回答送信

1. 回答者が SurveyJS で回答する
2. フロントで必須条件や表示条件を制御する
3. バックエンドで `public_id`、`answer_json` を検証し、セッションの `respondent_id` から回答者を特定する
4. 各 `response` に `survey_snapshot_json` を保存する
5. `responses` に新規保存する
6. `send_confirmation_email` が有効なら回答控えメールを送信する
7. 完了画面を返す

### 5.3 回答編集

1. 回答者が `edit_token` 付き URL を開く
2. バックエンドがセッションの `respondent_id` を解決し、`edit_token` と一致することを確認する
3. 既存の `answer_json` を復元する
4. `ends_at` を過ぎていれば編集不可とする
5. `allow_edit = true` の場合のみ更新を受け付ける
6. 更新後は `responses` を上書きし、必要に応じてメールを再送する

### 5.4 CSV 出力

1. 管理者が対象アンケートを選択する
2. バックエンドが `responses` を取得する
3. `responses.survey_snapshot_json` を元に列を展開する
4. CSV を生成する
5. ダウンロードレスポンスとして返す

## 6. アンケート編集ルール

- `questions_json` は回答が 1 件以上あっても編集可能にする
- 保存済み回答との整合性は `responses.survey_snapshot_json` で保持する
- 編集可能なのは `title`, `description`, `status`, `starts_at`, `ends_at`, `allow_multiple`, `allow_edit`, `send_confirmation_email`, `include_answers_in_email`
- 設問を変えたい場合は複製して新規アンケートとして作成する
- 複製時は `title`, `description`, `questions_json`, `allow_multiple`, `allow_edit`, メール設定をコピーする
- 複製時は `responses`, `public_id`, `created_at`, `updated_at` をコピーしない

## 7. 公開状態と回答可否

- `status` は自動変更しない
- 有効な状態は `draft`, `published`, `closed`, `archived`
- 原則として回答可能なのは `status = published`
- `starts_at` より前は回答不可
- `ends_at` より後は回答不可
- 期限到来時に `status` は自動で更新しない

## 8. 回答回数と編集仕様

アンケートごとに `allow_multiple` と `allow_edit` を持つ。

| allow_multiple | allow_edit | 挙動 |
|---|---|---|
| false | false | 1人1回。回答後は前回回答を表示。編集不可 |
| false | true | 1人1回。既存回答を編集可 |
| true | false | 複数回答可。毎回新規回答。編集不可 |
| true | true | 複数回答可。回答ごとに編集用URLを発行 |

補足:

- `allow_multiple = false` かつ `allow_edit = false` の場合は、回答済み画面に前回回答を表示し、編集ボタンは出さない
- `allow_multiple = false` かつ `allow_edit = true` の場合は、回答済み画面から既存回答の編集に進める
- `allow_multiple = true` の場合は、同じ `respondent` が同じ `survey` に複数 `responses` を持てる
- `allow_multiple = true` かつ `allow_edit = true` の場合は、回答ごとに編集用 URL を発行する

## 9. 編集用 URL

- `responses` ごとに `edit_token` を発行する
- 編集 URL は `https://example.com/s/{public_id}/r/{edit_token}/edit` とする
- 編集には `edit_token` の一致と `respondent_id` の一致が必要
- URL を知っているだけでは編集不可
- `allow_edit = true` の場合のみ、完了画面と回答控えメールに編集 URL を出す
- `allow_edit = false` の場合は編集 URL を表示・送信しない
- `ends_at` を過ぎたら新規回答も編集も不可とする

## 10. 名寄せ設計

### 10.1 優先順位

1. `respondents` に `line_user_id` が存在する場合
   - その `respondents` の `name`, `email`, `honorific` を使用する
   - LINE 表示名が変わっていても `line_user_id` を正とする
2. 初回アクセスの場合
   - `line_user_id` が未登録なら `LIFF displayName` で `respondent_masters.line_display_name` を検索する
   - 一致すれば `respondents` を作成し、`name`, `email`, `honorific`, `respondent_master_id` を保存する
3. 名寄せ失敗時
   - 回答不可にはしない
   - `name`, `email`, `honorific` を手入力してもらう
   - 手入力情報は `respondents` に保存する
   - `respondent_masters` には自動追加しない

### 10.2 手入力後の扱い

- 次回以降、同じ `line_user_id` が `respondents` にあれば再入力不要とする
- `name`, `email`, `honorific` の本人修正は MVP では行わない

## 11. CSV インポート設計

- CSV は `master_code,line_display_name,name,email,honorific,note` の形式とする
- 必須項目は `master_code`, `line_display_name`, `name`, `email`
- 任意項目は `honorific`, `note`
- 更新キーは `master_code`
- `master_code` が存在すれば更新、存在しなければ新規追加
- `line_display_name` が重複していたらエラーにする

## 12. 敬称の扱い

- `honorific` はメール宛名、管理画面表示、CSV 出力に使う
- メール宛名は `{name}{honorific}` とする
- `honorific` が空なら `{name}さん` をフォールバックにする

## 13. メール設計

- 新規回答時と編集回答時の両方で回答控えメールを送る
- 件名は `【回答控え】{アンケート名}` または `【回答修正控え】{アンケート名}` とする
- 本文には宛名、アンケート名、回答日時、回答内容を含める
- `allow_edit = true` の場合のみ編集 URL を本文に含める
- `send_confirmation_email = false` の場合は送信しない
- `include_answers_in_email = false` の場合は回答本文を省略する
- アンケートの owner にメールアドレスが設定されている場合は、回答者と同じ内容を owner にも別送する
- 名寄せ成功時はマスター由来のメールアドレスを使う
- 名寄せ失敗時は本人入力のメールアドレスを使う
- メール送信失敗時は回答保存を優先し、失敗内容を `email_error` に保存する
- `email_sent_at` で送信成功時刻を管理する

## 14. CSV 出力設計

- 1 response = 1 行とする
- 複数回答可の場合は同じ回答者が複数行になる
- 編集済み回答は最新内容のみ出力する
- 編集履歴は MVP では持たない
- 出力列は回答 ID、初回回答日時、最終更新日時、LINE 表示名、氏名、敬称、メール、各設問の回答とする
- 文字コードは UTF-8 BOM 付き、改行コードは CRLF とする
- 複数選択は `;` 区切りとする
- 日時は Asia/Tokyo とする
- 設問列は `responses.survey_snapshot_json` を基準に生成し、回答時点の定義で出力する

## 15. 認証・セキュリティ

- 回答者認証は LIFF ログインを使う
- `POST /api/liff/identify` / `POST /api/liff/identify/manual` でLINE ID Tokenを検証し、成功時にPHP標準セッションを確立する
- セッションには `respondent_id`、`authenticated_at` のみ保存する
- Cookie名は `__Host-survey_session`、属性は `HttpOnly` / `Secure` / `SameSite=Lax`、有効期限は14日
- 保存先は `backend/storage/sessions/`（Web公開ディレクトリ外）、DBテーブルは作らない
- 以降の回答者側APIはセッションCookieで認証する（`Authorization: Bearer` は使わない）
- `AuthSessionMiddleware` で `$_SESSION['respondent_id']` の有無を確認し、なければ401を返す
- CSRF対策は `SameSite=Lax` + `Origin`/`Referer` チェック + `Content-Type: application/json` 強制で行う（CSRFトークンは使わない）
- 管理画面は Basic 認証とする
- 対象は `/manage` と `/api/manage`
- 編集時は `edit_token` と `respondent_id` の一致を確認する

## 16. API 設計

### 16.1 回答者側

- `POST /api/liff/identify` — `id_token` を受け取り、セッションを確立する
- `POST /api/liff/identify/manual` — `id_token` と手入力情報を受け取り、セッションを確立する
- `GET /api/surveys/public/{public_id}` — セッション不要
- `POST /api/surveys/public/{public_id}/responses` — セッション必須
- `GET /api/surveys/public/{public_id}/responses/current` — セッション必須
- `GET /api/surveys/public/{public_id}/responses/{edit_token}` — セッション必須
- `PUT /api/surveys/public/{public_id}/responses/{edit_token}` — セッション必須

### 16.2 管理側

- `GET /api/manage/surveys`
- `POST /api/manage/surveys`
- `GET /api/manage/surveys/{id}`
- `PUT /api/manage/surveys/{id}`
- `DELETE /api/manage/surveys/{id}`
- `POST /api/manage/surveys/{id}/duplicate`
- `GET /api/manage/surveys/{id}/responses`
- `GET /api/manage/surveys/{id}/responses.csv`
- `POST /api/manage/respondent-masters/import`
- `GET /api/manage/respondent-masters`

## 17. 画面設計

### 17.1 回答者画面

- LIFF ログイン
- 自動名寄せ結果の表示
- SurveyJS アンケート入力
- 回答完了画面
- 編集画面

### 17.2 管理画面

- アンケート一覧
- アンケート作成
- アンケート編集
- SurveyJS JSON 編集
- 回答一覧（response 単位）
- 回答詳細（選択した response の内容）
- CSV ダウンロード
- respondent_masters CSV インポート

## 18. 実装上の注意

- `questions_json` は回答が 1 件以上あっても編集できる
- `survey_snapshot_json` は回答時点の定義を保持する
- 回答管理は `responses` 単位で行い、同一回答者の重複 response は一覧にそのまま表示する
- 回答詳細では同一回答者の response 切り替え UI を持たない
- `respondent_masters` と `respondents` の役割を混同しない
- `respondents` の `line_user_id` を識別の主軸にする
- `public_id` と `edit_token` は用途を分ける
- `status` の自動変更はしない
