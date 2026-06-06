# 実装タスクリスト（修正版）

`spec.md` を前提に、上から順に実装するためのタスクリスト。
依存関係の順序・抜け漏れ・粒度を修正済み。

---

## 0. 基盤整備

- [x] 0-1. バックエンドの起動基盤を作る
  - Slim のエントリポイント、ルーティング、JSON レスポンス、例外ハンドリングを整える
  - `.env` 読み込みと設定クラスを用意する（phpdotenv）
  - エラーレスポンスの形式を `{ "error": "...", "code": "..." }` に統一する

- [ ] 0-2. フロントエンドの起動基盤を作る
  - Vite + React + TypeScript のビルド/起動導線を整える
  - 公開側（`/s/*`）と管理側（`/admin/*`）でルーティングを分ける
  - **SurveyJS のバージョンを固定し、`questions_json` の最小サンプルを1件用意する**
  - LIFF SDK をインストールし、初期化関数の雛形を作る
  - **LIFF ブラウザ外アクセス時（開発環境など）のフォールバック方針を決める**

- [ ] 0-3. DB 接続と共通ユーティリティを作る
  - MariaDB/MySQL 接続（illuminate/database）、タイムゾーン（Asia/Tokyo）を共通化する
  - `public_id` と `edit_token` の生成関数を実装する
  - 日時のフォーマット/変換ヘルパーを用意する（Carbon または手動）

---

## 1. DB と永続化の土台

- [ ] 1-1. 全テーブルを作成するマイグレーションを作る
  - `surveys` / `respondent_masters` / `respondents` / `responses` を作成する
  - **`responses` の外部キー（`survey_id` → `surveys.id`、`respondent_id` → `respondents.id`）を明示する**
  - `respondents.line_user_id` と `respondent_masters.line_display_name` の UNIQUE 制約を必ず入れる
  - `responses.edit_token` の UNIQUE 制約を入れる
  - phinx または生 SQL でマイグレーションを管理する

- [ ] 1-2. 初期データと管理者認証設定を用意する
  - Basic 認証用のユーザー名/パスワードを環境変数（`ADMIN_USER` / `ADMIN_PASS`）で管理する
  - 開発用シードデータ（survey 1件、respondent_master 数件）を投入できるようにする

- [ ] 1-3. リポジトリ層の雛形を作る
  - `SurveyRepository` / `RespondentRepository` / `RespondentMasterRepository` / `ResponseRepository` を作る
  - 各リポジトリに `findById` / `findBy` / `save` / `update` の共通パターンを実装する
  - **以降のタスクはすべてこのリポジトリ層を経由することを前提とする**

---

## 2. 回答者識別と名寄せ

> **前提: タスク 0-3、1-1、1-3 完了後に着手する**

- [ ] 2-1. LINE ID Token 検証クライアントを作る
  - LINE の公開鍵エンドポイントを使って ID Token を検証する
  - `line_user_id` と `display_name` を取り出せるようにする
  - 検証失敗時は 401 を返す

- [ ] 2-2. 名寄せサービス層を実装する（`IdentifyService`）
  - 以下の3ケースをすべてカバーする
    1. `line_user_id` が `respondents` に既存 → `line_display_name` を最新値に更新して既存情報を返す
    2. `line_user_id` が未登録 → `respondent_masters.line_display_name` と完全一致で検索して `respondents` を作成する
    3. 名寄せ失敗 → 氏名・メール・敬称が必要な「手入力要求」状態を返す
  - 返り値に識別ステータス（`existing` / `matched` / `manual_required`）を含める

- [ ] 2-3. `POST /api/liff/identify` を実装する
  - ID Token を受け取り、`IdentifyService` を呼ぶ
  - **レスポンス仕様を明示する:**
    ```json
    {
      "status": "existing|matched|manual_required",
      "respondent": { "id": 1, "name": "...", "email": "...", "honorific": "..." } | null
    }
    ```
  - `manual_required` の場合は `respondent` を null で返す

- [ ] 2-4. `POST /api/liff/identify/manual` を追加する
  - 手入力（氏名・メール・敬称）を受け取り `respondents` に保存する
  - `respondent_masters` には追加しない
  - バリデーション: メールアドレス必須・形式チェック

---

## 3. 公開アンケート取得

> **前提: タスク 1-3、2-3 完了後に着手する**

- [ ] 3-1. `GET /api/surveys/public/{public_id}` を実装する
  - `public_id` から survey を取得する
  - 回答可否を判定して返す:
    - `status != published` → 回答不可
    - `starts_at` 前 → 回答不可
    - `ends_at` 後 → 回答不可
  - 回答不可の理由をレスポンスに含める（`"reason": "not_started|closed|not_published"`）

- [ ] 3-2. 回答者向け survey ペイロードを整える
  - `questions_json`、タイトル、説明、`allow_multiple`、`allow_edit`、`ends_at` を返す
  - SurveyJS にそのまま渡せる形にする（`questions_json` をパースして `survey.fromJSON()` できること確認）

---

## 4. 回答保存とメール送信

> **前提: タスク 2-3、3-1、6-1 完了後に着手する**
> ※ メールサービスを先に作ってから回答保存に組み込む（後回しにすると実装が中途半端になる）

- [ ] 4-1. メール送信サービスを先に作る（`MailService`）
  - Resend API クライアントをラップする
  - `sendConfirmation(respondent, survey, response, editUrl|null)` のインターフェースを定める
  - 送信失敗時に例外を throw せず、失敗情報（メッセージ）を返す設計にする

- [ ] 4-2. `POST /api/surveys/public/{public_id}/responses` を実装する
  - ID Token を検証して `respondent_id` を確定する
  - `allow_multiple=false` かつ既存回答あり → 既存回答を返す（保存しない）
  - 新規保存時: `answer_json`、`survey_snapshot_json`、`edit_token`、`submitted_at` を保存する
  - 保存後に `MailService` を呼ぶ。失敗時は `email_error` に記録し、回答は成功扱い

- [ ] 4-3. `GET /api/surveys/public/{public_id}/responses/current` を実装する
  - `respondent_id` に紐づく最新 response を返す
  - `allow_edit` の値も含めてフロントの表示分岐に使える形にする

---

## 5. 回答編集

> **前提: タスク 4-2 完了後に着手する**

- [ ] 5-1. `GET /api/surveys/public/{public_id}/responses/{edit_token}` を実装する
  - `edit_token` の一致 + `respondent_id` の一致を検証する（URLだけでは編集不可）
  - `ends_at` 超過時は 403 を返す
  - `allow_edit=false` の survey では 403 を返す

- [ ] 5-2. `PUT /api/surveys/public/{public_id}/responses/{edit_token}` を実装する
  - 上記と同じ検証を行う
  - `answer_json` を上書き、`updated_at` を更新、`submitted_at` は変更しない
  - `survey_snapshot_json` は編集時も上書きする（最新版を保持する）
  - 保存後に `MailService` で編集控えメールを送る

---

## 6. メール本文生成

> **前提: タスク 4-1 完了後に着手する**

- [ ] 6-1. 回答控えメールの本文テンプレートを実装する
  - 宛名（`{name}{honorific}` / honorific なしなら `{name}さん`）
  - アンケート名、回答日時、回答内容
  - `allow_edit=true` のときだけ編集 URL を含める
  - 件名: 新規「【回答控え】{title}」/ 編集「【回答修正控え】{title}」

- [ ] 6-2. メール送信結果の記録を実装する
  - 成功時: `email_sent_at` に現在時刻を保存する
  - 失敗時: `email_error` にエラーメッセージを保存する

---

## 7. 管理画面認証

> **前提: タスク 0-1 完了後に着手する**

- [ ] 7-1. Basic 認証ミドルウェアを作る
  - `/api/admin/*` に適用する
  - 認証情報は `ADMIN_USER` / `ADMIN_PASS` 環境変数から読む
  - 401 時は `WWW-Authenticate: Basic realm="Admin"` を返す

- [ ] 7-2. フロントエンドの `/admin/*` ルートと Basic 認証の整合を確認する
  - Coreserver に静的ビルドを配置する場合、`.htaccess` でBasic認証を掛ける方針を決める
  - Vite 開発サーバーでは認証をスキップする方針を明示する

---

## 8. アンケート管理 API

> **前提: タスク 1-3、7-1 完了後に着手する**

- [ ] 8-1. `GET /api/admin/surveys` を実装する
  - 一覧（id, title, status, 回答数, allow_multiple, allow_edit, starts_at, ends_at）を返す

- [ ] 8-2. `POST /api/admin/surveys` を実装する
  - `public_id` を生成して保存する
  - `questions_json` の JSON 構文バリデーションを行う

- [ ] 8-3. `GET /api/admin/surveys/{id}` を実装する
  - 編集画面用に全カラムを返す
  - 回答数も含めて返す（`questions_json` 編集ロックの判定用）

- [ ] 8-4. `PUT /api/admin/surveys/{id}` を実装する
  - 回答数 ≥ 1 のとき `questions_json` の更新を拒否する（400）
  - それ以外のフィールドは更新可能

- [ ] 8-5. `DELETE /api/admin/surveys/{id}` を実装する
  - 回答数 ≥ 1 のとき削除を拒否する（409）
  - 回答 0 件のときのみ削除する

- [ ] 8-6. `POST /api/admin/surveys/{id}/duplicate` を実装する
  - コピー対象: title / description / questions_json / allow_multiple / allow_edit / メール設定
  - コピーしない: responses / public_id / created_at / updated_at
  - 新しい `public_id` を生成する

---

## 9. 回答管理 API

> **前提: タスク 4-2、8-1 完了後に着手する**

- [ ] 9-1. `GET /api/admin/surveys/{id}/responses` を実装する
  - respondent の氏名・メール・LINE表示名を JOIN して返す
  - `submitted_at` 降順で返す

- [ ] 9-2. `GET /api/admin/surveys/{id}/responses.csv` を実装する
  - 列順: 回答ID、初回回答日時、最終更新日時、LINE表示名、氏名、敬称、メール、設問列
  - 設問列は `surveys.questions_json` から生成する（回答後はロックされているため列ズレなし）
  - 複数選択は `;` 区切り、空値は空文字
  - 文字コード: UTF-8 BOM付き、改行: CRLF、日時: Asia/Tokyo

---

## 10. respondent_masters 管理 API

> **前提: タスク 1-3、7-1 完了後に着手する**

- [ ] 10-1. `GET /api/admin/respondent-masters` を実装する
  - 全件一覧を返す（master_code / line_display_name / name / email / honorific / note）

- [ ] 10-2. `POST /api/admin/respondent-masters/import` を実装する
  - CSV を読み込み、`master_code` 単位で upsert する
  - 一部エラーがあっても成功行は保存する
  - レスポンスに `{ "imported": N, "errors": [ { "row": N, "reason": "..." } ] }` を返す

- [ ] 10-3. CSV インポートのバリデーションを実装する
  - 必須項目不足（master_code / line_display_name / name / email）
  - `line_display_name` の重複（CSV内重複 + DB既存との重複）
  - メールアドレス形式不正

---

## 11. 公開側フロントエンド

> **前提: タスク 2-3、3-1、4-2 完了後に着手する**

- [ ] 11-1. LIFF 初期化と ID Token 取得の共通フックを作る
  - `useLiff()` フックで初期化・ログイン・IDトークン取得をカプセル化する
  - **LIFF ブラウザ外（開発環境）からのアクセス時にエラーメッセージを表示する**

- [ ] 11-2. `/s/:public_id` のルーティングと Survey 取得を作る
  - アンケート情報を取得し、回答不可なら理由を表示する

- [ ] 11-3. 名寄せ結果による表示分岐を実装する
  - `existing` / `matched`: 氏名・メール・敬称を表示（編集不可）
  - `manual_required`: 手入力フォームを表示（メール必須バリデーション）

- [ ] 11-4. SurveyJS フォームを表示する
  - `questions_json` を `survey.fromJSON()` に渡して描画する
  - 送信ハンドラで `POST /api/surveys/public/{public_id}/responses` を呼ぶ

- [ ] 11-5. 回答完了画面を作る
  - 回答控えメール送信済みの案内を出す
  - `allow_edit=true` のときだけ編集 URL を表示する

- [ ] 11-6. 再訪時の表示分岐を作る（`responses/current` 使用）
  - `allow_multiple=false, allow_edit=false`: 前回回答内容を表示。編集ボタンなし
  - `allow_multiple=false, allow_edit=true`: 既存回答を編集フォームに復元
  - `allow_multiple=true`: 最新回答を参照しつつ新規回答フォームを出す

---

## 12. 編集側フロントエンド

> **前提: タスク 5-1、5-2、11-4 完了後に着手する**

- [ ] 12-1. `/s/:public_id/r/:edit_token/edit` の編集ルーティングを作る
  - LIFF 認証後に `GET responses/{edit_token}` を呼ぶ
  - 本人不一致・期限超過は適切なエラー表示にする

- [ ] 12-2. 既存回答をフォームへ復元する
  - `answer_json` を `survey.data` にセットして SurveyJS に戻す

- [ ] 12-3. 編集送信と完了画面を作る
  - `PUT responses/{edit_token}` を呼ぶ
  - 完了画面で再送メールの案内と編集 URL を表示する

---

## 13. 管理画面フロントエンド

> **前提: タスク 7-2、8-x、9-x、10-x 完了後に着手する**

- [ ] 13-1. 管理画面の共通レイアウトを作る
  - サイドバー（アンケート管理 / マスター管理）
  - Basic 認証はブラウザ or `.htaccess` に委ねる（フロント側は認証 UI を持たない）

- [ ] 13-2. アンケート一覧画面を作る
  - 一覧表示、新規作成ボタン、URL コピー、複製、削除（0件のみ）

- [ ] 13-3. アンケート編集画面を作る
  - title / description / status / starts_at / ends_at / allow_multiple / allow_edit / メール設定
  - 回答 ≥ 1 件のとき `questions_json` 編集欄を disabled にする

- [ ] 13-4. SurveyJS JSON 編集 UI を作る
  - textarea + JSON.parse によるバリデーション
  - 保存前に構文エラーをインラインで表示する

- [ ] 13-5. 回答一覧・詳細画面を作る
  - 一覧: 氏名・LINE表示名・回答日時
  - 詳細: 回答内容（SurveyJS の `survey.data` ベースで表示）
  - **`allow_multiple=true, allow_edit=true` の場合、同一回答者の複数 response を選択できる UI を用意する**
  - CSV ダウンロードボタン

- [ ] 13-6. respondent_masters 一覧と CSV インポート画面を作る
  - インポート結果: 成功 N 件 + エラー行一覧（行番号・理由）を表示する

---

## 14. 検証と仕上げ

- [ ] 14-1. 主要フローの動作確認を行う
  - 初回アクセス（名寄せ成功）→ 回答 → メール受信
  - 初回アクセス（名寄せ失敗）→ 手入力 → 回答 → 次回再訪で再入力なし
  - 編集 URL から再編集 → 編集控えメール受信
  - CSV ダウンロードの列・文字コード確認

- [ ] 14-2. 代表的な異常系を確認する
  - ID Token 不正 → 401
  - 編集期限超過 → 403
  - `questions_json` ロック違反 → 400
  - CSV インポートの一部エラー行 → 成功行は保存・エラー行一覧返却
  - LIFF 外アクセス → エラー表示（フリーズしない）

- [ ] 14-3. Coreserver デプロイ前チェック
  - フロントのビルド成果物配置先（`public_html` 以下）を確認する
  - `.htaccess` での Basic 認証・リライトルールを確認する
  - 環境変数（`.env`）のパスと権限を確認する
  - PHP 8.3 と必要な拡張モジュール（`pdo_mysql`, `mbstring`, `openssl`）を確認する