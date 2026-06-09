# PHP標準セッションへの切り替え タスクリスト

`docs/session/plan.md` を実装に落とすためのタスクリスト。
大分類は `backend` と `frontend` の 2 つに分ける。

---

## Backend

### 1. セッション基盤

- [ ] `SessionMiddleware` を追加して `session_start()` を 1 箇所に集約する
- [ ] `session_name('__Host-survey_session')` と cookie 属性を設定する
- [ ] `SESSION_SAVE_PATH` を `backend/storage/sessions` 相当の非公開ディレクトリに向ける
- [ ] `session.gc_maxlifetime` と cookie lifetime を 14日に揃える
- [ ] セッション保存先ディレクトリが存在しない場合に分かりやすく失敗するようにする

### 2. LIFF 本人確認

- [ ] `POST /api/liff/identify` をセッション発行対応にする
- [ ] リクエストボディは `id_token` のまま受け取る
- [ ] LINE idToken 検証後に `session_regenerate_id(true)` を実行する
- [ ] `$_SESSION['respondent_id']` と `$_SESSION['authenticated_at']` を保存する
- [ ] `POST /api/liff/identify/manual` も同じセッション発行フローにする
- [ ] `IdentifyAction` と `IdentifyManualAction` のレスポンス形式を現行 frontend に合わせて維持する

### 3. 公開回答 API の認証差し替え

- [ ] `SaveResponseAction` から `Authorization: Bearer` 依存を外す
- [ ] `GetCurrentResponseAction` から `Authorization: Bearer` 依存を外す
- [ ] `GetEditResponseAction` から `Authorization: Bearer` 依存を外す
- [ ] `UpdateResponseAction` から `Authorization: Bearer` 依存を外す
- [ ] `ActionHelperTrait` の Bearer 抜き出し処理を削除または置き換える
- [ ] `SurveyResolutionTrait` を `$_SESSION['respondent_id']` ベースに切り替える
- [ ] 上記 4 つの UseCase から `IdTokenVerifier` 依存を外す
- [ ] セッション未初期化時は 401 を返す

### 4. 認証ミドルウェア

- [ ] `AuthSessionMiddleware` を追加する
- [ ] `$_SESSION['respondent_id']` の有無を確認する
- [ ] 存在する場合は respondent を DB から取得して request attribute に載せる
- [ ] 401 レスポンスの code を `SESSION_REQUIRED` にする

### 5. リクエスト安全性

- [ ] `RequestSafetyMiddleware` を追加する
- [ ] unsafe method に対して `Origin` チェックを行う
- [ ] `Origin` が無い場合の `Referer` フォールバックを実装する
- [ ] unsafe method に対して `Content-Type: application/json` を要求する
- [ ] `POST /api/liff/identify` と `POST /api/liff/identify/manual` も JSON API として扱う
- [ ] `APP_ORIGIN_URL` を基準に許可オリジンを判定する

### 6. セッション終了

- [ ] ログアウト処理を定義する
- [ ] `$_SESSION = []`、cookie 失効、`session_destroy()` の流れを実装する
- [ ] 将来の `auth_sessions` テーブルが不要なことを前提に、MVP は DB 変更なしで完結させる

### 7. 既存仕様の維持確認

- [ ] `allow_edit` の既存制御が壊れていないことを確認する
- [ ] `edit_token` の既存制御が壊れていないことを確認する
- [ ] 回答作成・取得・更新が respondent のセッションに正しく紐づくことを確認する
- [ ] 他 respondent の回答を操作できないことを確認する

---

## Frontend

### 1. LIFF 認証フロー

- [ ] `useLiff.ts` の `idToken` 利用方針を維持しつつ、初回本人確認専用に整理する
- [ ] ログイン後の継続認証に `idToken` を使わない前提へ寄せる
- [ ] `frontend/src/features/liff/LiffContext.tsx` 周辺の利用箇所をセッション前提に見直す

### 2. 公開回答画面

- [ ] `PublicSurveyPage.tsx` から `Authorization: Bearer` を外す
- [ ] `PublicSurveyPage.tsx` の `fetch()` に `credentials: 'include'` を付ける
- [ ] `POST /api/liff/identify` 送信時は `id_token` を送る
- [ ] `POST /api/liff/identify/manual` 送信時も `id_token` を送る
- [ ] `POST /api/surveys/public/{public_id}/responses` 送信時は `credentials: 'include'` と `Content-Type: application/json` を付ける
- [ ] `GET /api/surveys/public/{public_id}/responses/current` 取得時も cookie 付きで呼ぶ

### 3. 編集回答画面

- [ ] `EditResponsePage.tsx` から `Authorization: Bearer` を外す
- [ ] `EditResponsePage.tsx` の `fetch()` に `credentials: 'include'` を付ける
- [ ] `GET /api/surveys/public/{public_id}/responses/{edit_token}` を cookie 前提で呼ぶ
- [ ] `PUT /api/surveys/public/{public_id}/responses/{edit_token}` を cookie 前提で呼ぶ

### 4. 共通 API 呼び出し

- [ ] 今後追加する survey 系 API 呼び出しは最初から cookie 付き fetch を使う
- [ ] 状態変更 API は `Content-Type: application/json` を明示する
- [ ] 画面遷移後も session cookie が残る前提で再訪時の挙動を確認する

### 5. 挙動確認

- [ ] LIFF 外部ブラウザからの初回アクセスでもログイン後に画面遷移できることを確認する
- [ ] session cookie があれば idToken なしで回答保存・取得・編集更新ができることを確認する
- [ ] 手入力登録後も次回アクセスで再入力が不要であることを確認する
