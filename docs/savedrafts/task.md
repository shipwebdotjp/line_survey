# 途中経過保存 実装タスクリスト

`docs/savedrafts/spec.md` を前提に、`answer_json` のみを保存・復元する途中経過保存機能を実装するためのタスクリスト。
進捗位置やページ番号の復元は対象外とする。

---

## 0. 実装前提の固定

- [ ] 0-1. 下書き保存の責務を `response_drafts` に限定する
  - 送信済み回答 `responses` と途中経過 `response_drafts` を分離する
  - 下書きは 1 人 1 アンケートにつき 1 件のみとする
  - 保存対象は `answer_json` のみとする

- [ ] 0-2. API の公開範囲を固定する
  - 下書き API は既存の LIFF セッション必須ルート配下に追加する
  - `GET` は閲覧、`PUT` / `DELETE` は状態変更として扱う
  - `PUT` / `DELETE` は `RequestSafetyMiddleware` の対象にする

- [ ] 0-3. 保持期限の扱いを固定する
  - `updated_at` 基準で 30 日を保持期限とする
  - 送信成功時は対応する下書きを削除する
  - 期限超過データは削除対象とする

---

## 1. DB と永続化の土台

> **前提: 既存の `surveys` / `respondents` / セッション基盤が利用可能であること**

- [ ] 1-1. `response_drafts` のマイグレーションを作成する
  - `survey_id` / `respondent_id` / `answer_json` / `created_at` / `updated_at` を持つ
  - `UNIQUE (survey_id, respondent_id)` を設定する
  - `survey_id` と `respondent_id` に検索用インデックスを置く
  - `surveys.id` と `respondents.id` への外部キーを設定する
  - 参照先削除時に下書きも消える `ON DELETE CASCADE` を付ける

- [ ] 1-2. `ResponseDraftRepository` を追加する
  - `findBySurveyAndRespondent`
  - `save`
  - `update`
  - `deleteBySurveyAndRespondent`
  - `deleteExpiredBefore`
  - `findBySurveyAndRespondent` は 0 件なら空配列か null で扱いを統一する

- [ ] 1-3. DB スキーマ資料を同期する
  - `docs/db/survey.sql` に `response_drafts` を反映する
  - 既存の `responses` との役割差が分かるように追記する

---

## 2. 下書き API

> **前提: タスク 0-2、1-1、1-2 完了後に着手する**

- [ ] 2-1. 下書き用の共通ユースケースを作る
  - `SurveyResolutionTrait` で `public_id` から survey を解決できるようにする
  - `published` / `starts_at` / `ends_at` の回答可否判定を再利用する
  - 既存の回答保存・編集と同じ条件で下書き API も制御する

- [ ] 2-2. `GET /api/surveys/public/{public_id}/response-draft` を実装する
  - 現在の `respondent_id` に紐づく下書きを返す
  - 見つからない場合は `{"draft": null}` を返す
  - `survey_public_id` / `respondent_id` / `answer_json` / `created_at` / `updated_at` を返す
  - セッション未確立、対象 survey 不在、回答不可状態を適切にエラー化する

- [ ] 2-3. `PUT /api/surveys/public/{public_id}/response-draft` を実装する
  - リクエストは `answer_json` のみ受け取る
  - `answer_json` が JSON オブジェクトでない場合は `422` にする
  - 既存行があれば更新、なければ新規作成する
  - 保存後は最新の下書きデータを返す

- [ ] 2-4. `DELETE /api/surveys/public/{public_id}/response-draft` を実装する
  - 現在の `respondent_id` の下書きを削除する
  - 対象がなければ成功扱いで返す
  - 削除後は空状態として扱う

- [ ] 2-5. ルーティングを追加する
  - `backend/routes/api.php` の session ルート配下へ追加する
  - `GET` は `AuthSessionMiddleware` のみでよい
  - `PUT` / `DELETE` は `RequestSafetyMiddleware` を追加する

- [ ] 2-6. プレゼンテーション層のアクションを追加する
  - `GetResponseDraftAction`
  - `SaveResponseDraftAction`
  - `DeleteResponseDraftAction`
  - 既存の `JsonResponse` フォーマットに合わせる

---

## 3. 回答送信フローへの接続

> **前提: タスク 1-2、2-3 完了後に着手する**

- [ ] 3-1. `POST /api/surveys/public/{public_id}/responses` に削除処理を組み込む
  - 回答保存成功後に対応する `response_drafts` を削除する
  - `MailService` の成功・失敗に関わらず、送信済み回答が作成できたら削除する
  - `allow_multiple=false` で既存回答を返す場合の扱いを整理する

- [ ] 3-2. 編集保存フローとは分離する
  - `PUT /api/surveys/public/{public_id}/responses/{edit_token}` では下書きを触らない
  - `responses` の編集は送信済み回答として独立して扱う

- [ ] 3-3. 下書き API と既存回答表示の優先順位を整理する
  - `responses/current` がある場合は送信済み回答表示を優先する
  - 下書きは送信前の再開用としてのみ使う

---

## 4. フロントエンド連携

> **前提: タスク 2-2、2-3、3-1 完了後に着手する**

- [ ] 4-1. 下書き API の型を追加する
  - `frontend/src/features/survey/types.ts` に draft 用型を追加する
  - `draft: null` と `draft: {...}` の両方を扱えるようにする
  - `answer_json` は SurveyJS の `sender.data` と同じ構造で扱う

- [ ] 4-2. 公開 API ヘルパーを追加する
  - `frontend/src/lib/publicApi.ts` か同等の API 層に draft 用関数を追加する
  - `getResponseDraft(public_id)`
  - `saveResponseDraft(public_id, answer_json)`
  - `deleteResponseDraft(public_id)`
  - 既存の `fetchWithSession` を流用する

- [ ] 4-3. `PublicSurveyPage.tsx` で下書きを復元する
  - survey 取得後、回答入力モードに入る前に draft を取得する
  - draft が存在する場合は SurveyJS の初期値として復元する
  - draft がない場合は空フォームで開始する
  - 既に送信済み回答を表示する分岐では draft 復元を行わない

- [ ] 4-4. 回答入力時の自動保存を実装する
  - SurveyJS の値変更イベントを使って `PUT` を呼ぶ
  - 連続入力に対して debounce して送信回数を抑える
  - 初回復元直後に不要な自動保存が走らないようにする
  - 保存失敗時はユーザー入力を破壊せず、軽いエラー表示に留める

- [ ] 4-5. 送信完了時に下書きを削除する
  - `POST /responses` 成功後に `DELETE /response-draft` を呼ぶ
  - 削除失敗は回答完了の致命的エラーにしない
  - 再訪時に古い下書きが残らないようにする

- [ ] 4-6. 手入力本人確認との整合を確認する
  - `identify` / `identify/manual` 後に draft 復元が可能なことを確認する
  - セッション確立前に draft API を呼ばない

---

## 5. 保持期限とクリーンアップ

> **前提: タスク 1-2、2-3 完了後に着手する**

- [ ] 5-1. 期限切れ下書きの削除処理を用意する
  - `updated_at + 30日` を超えた行を削除する
  - `deleteExpiredBefore` を再利用する
  - 実行タイミングを 1 箇所に寄せる

- [ ] 5-2. クリーニング実行方法を定義する
  - 定期実行用の CLI コマンドまたはバッチ処理を追加する
  - デプロイ手順で cron 相当の実行方法を明示する
  - 手動実行時の安全なログ出力を用意する

- [ ] 5-3. 保存時の延命動作を確認する
  - `PUT` のたびに `updated_at` が更新される
  - その結果、保持期限が延長される

---

## 6. テストと確認

> **前提: タスク 1-1 以降が順次実装されること**

- [ ] 6-1. `response_drafts` リポジトリのテストを追加する
  - 新規保存で 1 件作成されること
  - 同一 `(survey_id, respondent_id)` で上書きされること
  - 期限切れ削除が動くこと

- [ ] 6-2. 下書き API の統合テストを追加する
  - `GET` で空状態と保存済み状態を返せること
  - `PUT` で作成・更新できること
  - `DELETE` で削除できること
  - 他 respondent の下書きを読めないこと

- [ ] 6-3. 回答送信との結合テストを追加する
  - `POST /responses` 成功後に draft が消えること
  - 送信失敗時は draft が残ること
  - `allow_multiple` の挙動と衝突しないこと

- [ ] 6-4. フロントの手動確認項目を用意する
  - 入力途中でリロードしても `answer_json` が復元されること
  - 送信後に draft が消えていること
  - セッション切れ時に下書き API が 401 になること

