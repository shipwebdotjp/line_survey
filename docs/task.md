# 実装タスクリスト

`spec.md` を前提に、上から順に実装するためのタスクリスト。  
各項目は LLM コーディングエージェントが 1 つずつ着手しやすい粒度に分割している。

## 0. 基盤整備

- [ ] 0-1. バックエンドの起動基盤を作る
  - Slim のエントリポイント、ルーティング、JSON レスポンス、例外ハンドリングを整える
  - `.env` 読み込みと設定クラスを用意する
- [ ] 0-2. フロントエンドの起動基盤を作る
  - Vite + React + TypeScript のビルド/起動導線を整える
  - 公開側と管理側を分ける前提でルーティングの土台を作る
- [ ] 0-3. DB 接続と共通ユーティリティを作る
  - MariaDB/MySQL 接続、トランザクション、タイムゾーンを共通化する
  - `public_id` と `edit_token` の生成関数を実装する
  - 日時のフォーマット/変換ヘルパーを用意する

## 1. DB と永続化の土台

- [ ] 1-1. `surveys` / `respondent_masters` / `respondents` / `responses` のテーブルを作る
  - `spec.md` のカラム、制約、インデックスをそのまま反映する
  - `respondents.line_user_id` と `respondent_masters.line_display_name` の一意制約を必ず入れる
- [ ] 1-2. 初期データと管理者認証設定を用意する
  - Basic 認証用のユーザー名/パスワードを環境変数で持てるようにする
  - 開発用の最小データ投入手段を作る
- [ ] 1-3. リポジトリ層の雛形を作る
  - surveys / respondents / respondent_masters / responses の CRUD を共通パターンで扱えるようにする
  - 取得条件、保存、更新、一覧を分けて実装できる形にする

## 2. 回答者識別と名寄せ

- [ ] 2-1. LINE ID Token 検証クライアントを作る
  - `liff.getIDToken()` をバックエンドで検証できるようにする
  - `line_user_id` と `display_name` を取り出せるようにする
- [ ] 2-2. `POST /api/liff/identify` を実装する
  - ID Token を受け取り、検証結果を返す
  - ここで `respondents` の候補情報を決められるようにする
- [ ] 2-3. 既存 respondent の更新ロジックを実装する
  - `line_user_id` が存在する場合は既存 respondent を正とする
  - `line_display_name` は毎回最新値へ更新する
- [ ] 2-4. 初回名寄せロジックを実装する
  - `respondent_masters.line_display_name` の完全一致で検索する
  - 一致時は `respondent_master_id` を紐付けて `respondents` を作成する
- [ ] 2-5. 名寄せ失敗時の手入力保存を実装する
  - 氏名、メールアドレス、敬称を受け取り `respondents` に保存する
  - `respondent_masters` には自動追加しない
- [ ] 2-6. 手入力済み respondent の再訪挙動を実装する
  - 同じ `line_user_id` なら再入力不要にする
  - `respondents` の値をそのまま使う

## 3. 公開アンケート取得

- [ ] 3-1. `GET /api/surveys/public/{public_id}` を実装する
  - `public_id` から survey を取得し、公開可否と期限を判定する
  - `draft` / `published` / `closed` / `archived` の扱いを反映する
- [ ] 3-2. 回答者向けに返す survey ペイロードを整える
  - `questions_json`、タイトル、説明、公開状態に必要な値を返す
  - フロントでそのまま SurveyJS に渡せる形にする
- [ ] 3-3. `questions_json` 編集ロック前提の扱いを確認する
  - 回答数 1 件以上なら設問変更不可の制約を API 側でも守る

## 4. 回答保存

- [ ] 4-1. `POST /api/surveys/public/{public_id}/responses` を実装する
  - `line_user_id`、`respondent_id`、`answer_json` を検証する
  - 回答を新規保存し、`survey_snapshot_json` も保存する
- [ ] 4-2. `allow_multiple` / `allow_edit` の分岐を実装する
  - 1 人 1 回のときは既存回答の表示に切り替える
  - 複数回答可のときは新規 response を追加できるようにする
- [ ] 4-3. `submitted_at` と `updated_at` の扱いを実装する
  - `submitted_at` は初回回答時刻として固定する
  - 編集時は `updated_at` のみ更新する
- [ ] 4-4. `GET /api/surveys/public/{public_id}/responses/current` を実装する
  - 現在の回答者に紐づく最新 response を返す
  - 複数回答時は最新の 1 件を返す
- [ ] 4-5. 回答済み画面での表示分岐をバックエンド側で支える
  - 編集不可のときは回答内容のみ返す
  - 編集可のときは編集導線に必要な情報を返す

## 5. 回答編集

- [ ] 5-1. `GET /api/surveys/public/{public_id}/responses/{edit_token}` を実装する
  - `edit_token` と `respondent_id` と `line_user_id` の一致を検証する
  - 編集画面に戻すための既存 `answer_json` を返す
- [ ] 5-2. `PUT /api/surveys/public/{public_id}/responses/{edit_token}` を実装する
  - 編集期限、`allow_edit`、本人一致を検証する
  - 既存 response を上書き更新する
- [ ] 5-3. 編集時のメール再送を実装する
  - 編集回答時にも回答控えメールを送る
  - 編集 URL を含めるかどうかを `allow_edit` で分岐する
- [ ] 5-4. 編集時の制約を整理する
  - `ends_at` を過ぎたら新規回答と編集の両方を止める
  - URL を知っているだけでは編集できないようにする

## 6. メール送信

- [ ] 6-1. メール送信サービスの共通化を行う
  - Resend API を扱えるようにする
  - 宛先、件名、本文生成をサービス層へ分離する
- [ ] 6-2. 回答控えメールの本文生成を実装する
  - 宛名、アンケート名、回答日時、回答内容を含める
  - `allow_edit=true` のときだけ編集 URL を載せる
- [ ] 6-3. メール送信結果の記録を実装する
  - `email_sent_at` と `email_error` を更新する
  - 送信失敗でも回答保存は成功扱いにする

## 7. 管理画面認証

- [ ] 7-1. Basic 認証ミドルウェアを作る
  - `/admin` と `/api/admin` に適用する
  - 認証情報は環境変数で切り替えられるようにする
- [ ] 7-2. 管理側と回答者側の API 境界を分ける
  - 管理 API に誤って回答者用データを露出しないようにする
  - エラーレスポンスの形式を統一する

## 8. アンケート管理 API

- [ ] 8-1. `GET /api/admin/surveys` を実装する
  - 一覧、公開状態、回答数、編集可否が見えるようにする
- [ ] 8-2. `POST /api/admin/surveys` を実装する
  - 新規作成時に必要なデフォルト値を補う
  - `public_id` を生成する
- [ ] 8-3. `GET /api/admin/surveys/{id}` を実装する
  - 編集画面で必要な survey 詳細を返す
- [ ] 8-4. `PUT /api/admin/surveys/{id}` を実装する
  - タイトル、説明、status、期間、回答設定、メール設定を更新する
  - 回答が 1 件以上ある場合は `questions_json` を更新できないようにする
- [ ] 8-5. `DELETE /api/admin/surveys/{id}` を実装する
  - 回答 0 件の survey のみ削除可能にする
  - 回答がある場合は削除せず、アーカイブ運用前提にする
- [ ] 8-6. `POST /api/admin/surveys/{id}/duplicate` を実装する
  - title / description / questions_json / allow_multiple / allow_edit / mail 設定をコピーする
  - responses / public_id / created_at / updated_at はコピーしない

## 9. 回答管理 API

- [ ] 9-1. `GET /api/admin/surveys/{id}/responses` を実装する
  - 一覧表示用に response を返す
  - 並び順と検索条件を決める
- [ ] 9-2. `GET /api/admin/surveys/{id}/responses.csv` を実装する
  - CSV の列順、UTF-8 BOM、CRLF、Asia/Tokyo を反映する
  - `questions_json` から設問列を生成する
- [ ] 9-3. 回答詳細の表示に必要な API を整える
  - 管理画面で回答内容とメタ情報を確認できるようにする

## 10. respondent_masters 管理 API

- [ ] 10-1. `GET /api/admin/respondent-masters` を実装する
  - 一覧表示できるようにする
  - 検索や並び順の最低限を決める
- [ ] 10-2. `POST /api/admin/respondent-masters/import` を実装する
  - CSV を読み込み、`master_code` 単位で新規追加/更新する
  - 一部エラーがあっても成功行は保存する
- [ ] 10-3. CSV インポートのエラー一覧を返す
  - 必須項目不足、`line_display_name` 重複、形式不正を分けて返す
  - エラー行がどの行か分かるようにする
- [ ] 10-4. `line_display_name` 重複チェックを実装する
  - 名寄せ誤判定を防ぐため、重複時はインポートを失敗扱いにする

## 11. 公開側フロントエンド

- [ ] 11-1. `/s/:public_id` の公開ルーティングを作る
  - LIFF 初期化、ID Token 取得、本人識別までの導線を作る
- [ ] 11-2. SurveyJS で回答フォームを表示する
  - `questions_json` を読み込み、フォームとして描画する
  - 条件分岐や基本入力型を扱えるようにする
- [ ] 11-3. 名寄せ成功時の表示を作る
  - 本名、メール、敬称を事前表示する
  - ユーザーが修正できない前提を画面に反映する
- [ ] 11-4. 名寄せ失敗時の手入力フォームを作る
  - 氏名、メール、敬称の入力導線を用意する
  - メールアドレス必須のバリデーションを入れる
- [ ] 11-5. 回答完了画面を作る
  - 回答控えメール送信済みの案内を出す
  - `allow_edit=true` のときだけ編集 URL を表示する
- [ ] 11-6. `responses/current` を使った再訪時の表示分岐を作る
  - 1 人 1 回かつ編集不可のときは前回回答表示にする
  - 複数回答可のときは最新回答を参照できるようにする

## 12. 編集側フロントエンド

- [ ] 12-1. `/s/:public_id/r/:edit_token/edit` の編集ルーティングを作る
  - LIFF 認証後に編集 API を呼び出せるようにする
- [ ] 12-2. 既存回答をフォームへ復元する
  - `answer_json` を SurveyJS に戻せるようにする
  - 編集時に初回回答内容を見失わないようにする
- [ ] 12-3. 編集送信の完了画面を作る
  - 再送された回答控えメールの案内を出す
  - 編集 URL の表示条件を `allow_edit` に合わせる

## 13. 管理画面フロントエンド

- [ ] 13-1. 管理画面の共通レイアウトを作る
  - サイドバーまたはナビゲーションを用意する
  - `/admin` 配下の画面遷移を整理する
- [ ] 13-2. アンケート一覧画面を作る
  - 一覧、作成、編集、複製、削除、URL コピーを置く
- [ ] 13-3. アンケート編集画面を作る
  - title、description、status、期間、回答設定、メール設定を編集できるようにする
  - `questions_json` は回答 0 件のときだけ編集可能にする
- [ ] 13-4. SurveyJS JSON 編集 UI を作る
  - まずは textarea + JSON バリデーションで実装する
  - 保存前に構文エラーを表示する
- [ ] 13-5. 回答一覧/詳細画面を作る
  - 回答内容とメタ情報を確認できるようにする
  - CSV ダウンロードへの導線を置く
- [ ] 13-6. respondent_masters の一覧と CSV インポート画面を作る
  - インポート結果の成功行/エラー行を見せる
  - 重複や形式不正をその場で確認しやすくする

## 14. CSV 出力の仕上げ

- [ ] 14-1. CSV 列を spec に合わせて固定する
  - 回答 ID、初回回答日時、最終更新日時、LINE 表示名、氏名、敬称、メール、設問列を揃える
- [ ] 14-2. 複数選択の出力形式を固定する
  - `;` 区切りで出力する
  - 空値と未回答の扱いを揃える
- [ ] 14-3. 編集済み回答の出力ルールを固定する
  - 最新の回答内容のみを出す
  - 編集履歴は MVP では持たない

## 15. 検証と仕上げ

- [ ] 15-1. 主要フローの動作確認を行う
  - 初回アクセス、名寄せ成功、名寄せ失敗、回答送信、編集、CSV 出力を通す
- [ ] 15-2. 代表的な異常系を確認する
  - ID Token 不正、編集期限超過、`questions_json` ロック違反、CSV インポートエラーを確認する
- [ ] 15-3. デプロイ前チェックをまとめる
  - Coreserver 配置前提のパス、環境変数、ビルド成果物を確認する

