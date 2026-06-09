# PHP標準セッションへの切り替え

このプランは、現状の実装が使っている `id_token` / `Authorization: Bearer` / `JsonResponse` の流れを前提に、認証だけを PHP 標準セッションへ寄せる案として整理する。
大筋は維持しつつ、変数名と API の呼び方をこのプロジェクトの実態に合わせる。

## Summary

- `idToken` は「LINE本人確認」の入口に限定し、以後の継続認証は PHP 標準セッションで行う。
- 初回本人確認に使うリクエストボディは、現行実装どおり `id_token` を使う。
- `POST /api/liff/identify` と `POST /api/liff/identify/manual` は、現行どおり生 JSON を返す。
- `GET /api/surveys/public/{public_id}` 以降の survey 系 API は、現行どおり `JsonResponse::success()` の `data` 包みを維持する。
- MVP では新規のセッション用 DB テーブルは作らない。
- セッション情報は PHP の `$_SESSION` に置き、保存先は PHP 標準セッションハンドラを使う。
- セッションファイルの保存先は、Web 公開ディレクトリ外の非公開ディレクトリに明示指定する。
- CSRF token は使わない。
- CSRF 対策は以下の組み合わせで行う。
  - `SameSite=Lax`
  - unsafe method の `Origin` / 必要に応じて `Referer` チェック
  - `Content-Type: application/json` の強制
  - CORS の最小化
- セッション有効期限は 14日 とする。

---

## Implementation Changes

### 1. SessionMiddleware を追加する

PHP 標準セッションを Slim Middleware で開始する。

- `session_start()` は各 Action で直接呼ばず、Middleware に集約する。
- Cookie 属性を明示する。
- セッション保存先を Web 公開ディレクトリ外に指定する。
- セッション有効期限は 14日 とする。

設定方針:

```php
session_name('__Host-survey_session');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');

ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 14));
ini_set('session.save_path', '/path/to/project/backend/storage/sessions');

session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 14,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
```

注意:

- `__Host-` prefix を使う場合、Cookie に `Domain` は付けない。
- `Secure` 必須のため、HTTPS 前提。
- `session.save_path` のディレクトリは Web 公開ディレクトリ外に作成し、PHP 実行ユーザーのみ読み書き可能にする。

---

### 2. セッションに保存する値を最小限にする

セッションに保存するのは以下のみ。

```txt
respondent_id
authenticated_at
```

保存しないもの:

```txt
line_user_id
line_display_name
last_seen_at
csrf_token
idToken
accessToken
email
name
```

LINE関連情報や respondent の詳細情報は、必要に応じて DB から取得する。

---

### 3. POST /api/liff/identify を「LINE本人確認 + セッション発行」に変更する

現在の `id_token` 受け取りと idToken 検証は維持する。

処理フロー:

```txt
1. frontend から `id_token` を受け取る
2. backend で LINE idToken を検証する
3. line_user_id を取得する
4. respondents テーブルから respondent を特定または作成する
5. ログイン成功時に session_regenerate_id(true) を実行する
6. $_SESSION['respondent_id'] = respondent.id
7. $_SESSION['authenticated_at'] = time()
8. respondent 情報を JSON で返す
```

重要:

- `session_regenerate_id(true)` はログイン成功時のみ実行する。
- 通常の API リクエストごとには実行しない。
- `idToken` は保存しない。

---

### 4. POST /api/liff/identify/manual も同様にセッションを確立する

`identify/manual` も、本人確認と respondent 紐付けが完了した時点で同じようにセッションを発行する。

処理フロー:

```txt
1. `id_token` を検証する
2. 手入力情報と LINE user を respondent に紐付ける
3. session_regenerate_id(true) を実行する
4. $_SESSION['respondent_id'] = respondent.id
5. $_SESSION['authenticated_at'] = time()
6. respondent 情報を返す
```

---

### 5. frontend は idToken を毎回送らない

ログイン後の API 呼び出しでは `Authorization: Bearer ...` を廃止する。

対象は主に以下:

- `frontend/src/pages/public-survey/PublicSurveyPage.tsx`
- `frontend/src/pages/public-survey/EditResponsePage.tsx`

代わりに Cookie 付き fetch にする。

```ts
fetch('/api/xxx', {
  credentials: 'include',
});
```

状態変更 API では JSON を明示する。

```ts
fetch('/api/xxx', {
  method: 'POST',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify(payload),
});
```

---

### 6. 既存の Authorization: Bearer 前提を廃止する

以下の Action は、`idToken` ではなくセッションから現在の respondent を解決する。

対象:

```txt
SaveResponseAction
GetCurrentResponseAction
GetEditResponseAction
UpdateResponseAction
```

変更前:

```txt
Authorization: Bearer {idToken}
↓
backend で idToken 検証
↓
line_user_id から respondent を解決
```

変更後:

```txt
Cookie: __Host-survey_session=...
↓
$_SESSION['respondent_id'] を参照
↓
respondent_id から respondent を解決
```

補足:

- `backend/src/Presentation/Http/Survey/ActionHelperTrait.php` の Bearer 抜き出しは不要になる。
- `backend/src/Application/Survey/SurveyResolutionTrait.php` も、`idToken` 前提の解決からセッション前提に切り替える。
- これに合わせて `SaveResponseUseCase` / `GetCurrentResponseUseCase` / `GetEditResponseUseCase` / `UpdateResponseUseCase` から `IdTokenVerifier` 依存を外す。

---

### 7. AuthSessionMiddleware を追加する

認証が必要な API に共通適用する。

役割:

- `$_SESSION['respondent_id']` が存在するか確認する
- 存在しない場合は 401 を返す
- 存在する場合は respondent を DB から取得する
- request attribute に respondent をセットする

例:

```php
$request = $request->withAttribute('respondent', $respondent);
```

401 レスポンス例:

```json
{
  "error": "Unauthorized",
  "code": "SESSION_REQUIRED"
}
```

---

### 8. CsrfProtectionMiddleware ではなく RequestSafetyMiddleware を追加する

CSRF token は使わないため、名前は `CsrfProtectionMiddleware` よりも `RequestSafetyMiddleware` の方が実態に合う。

対象メソッド:

```txt
POST
PUT
PATCH
DELETE
```

チェック内容:

#### 1. Origin チェック

`Origin` が期待ドメインと一致するか確認する。

```txt
Origin: https://example.com
```

期待値は `APP_ORIGIN_URL` を基準に管理する。

```txt
APP_ORIGIN_URL=https://example.com
```

不一致なら 403。

```json
{
  "error": "Forbidden",
  "code": "INVALID_ORIGIN"
}
```

#### 2. Referer フォールバック

一部環境で `Origin` が空になる可能性を考慮する場合、`Referer` をフォールバックとして使う。

方針:

```txt
Origin がある:
  Origin を検証

Origin がない:
  Referer が期待ドメイン配下なら許可
  それ以外は拒否
```

より厳密にするなら、unsafe method では `Origin` 必須でもよい。

#### 3. Content-Type チェック

unsafe method では原則 `application/json` のみ許可する。

許可:

```txt
Content-Type: application/json
Content-Type: application/json; charset=utf-8
```

拒否:

```txt
application/x-www-form-urlencoded
multipart/form-data
text/plain
```

不正なら 415。

```json
{
  "error": "Unsupported Media Type",
  "code": "UNSUPPORTED_CONTENT_TYPE"
}
```

注意:

- `POST /api/liff/identify`
- `POST /api/liff/identify/manual`

も JSON API なので `Content-Type: application/json` を要求する。

---

### 9. Cookie 設定

セッション Cookie は以下にする。

```txt
Name: __Host-survey_session
HttpOnly: true
Secure: true
SameSite: Lax
Path: /
Domain: なし
Max-Age / lifetime: 14日
```

`SameSite=Lax` のため、通常の外部ブラウザ遷移後にも比較的扱いやすい。

MVP では単一オリジン運用を前提にするため、`SameSite=None` は使わない。

---

### 10. セッション有効期限

有効期限は 14日。

設定対象:

```txt
session.cookie_lifetime
session.gc_maxlifetime
```

または `session_set_cookie_params()` と `ini_set('session.gc_maxlifetime', ...)` で設定する。

注意:

- Cookie の lifetime だけ伸ばしても、サーバー側のセッションファイルが GC で消えると無効になる。
- 逆に `gc_maxlifetime` だけ伸ばしても、Cookie が消えればブラウザからは使えない。
- 両方 14日に揃える。

---

### 11. ログアウト

MVP ではシンプルに実装する。

処理:

```txt
1. $_SESSION = []
2. セッション Cookie を期限切れにする
3. session_destroy()
```

ログアウト API は将来拡張でよい。

---

## API Behavior

### POST /api/liff/identify

- `id_token` 必須
- LINE idToken を検証する
- respondent を特定または作成する
- セッションを発行する
- 以後の API 認証は idToken ではなく session cookie を使う

### POST /api/liff/identify/manual

- `id_token` 必須
- LINE idToken を検証する
- 手入力情報を respondent に紐付ける
- セッションを発行する

### GET /api/surveys/public/{public_id}/responses/current

- idToken 不要
- session 必須
- `$_SESSION['respondent_id']` から現在の respondent を解決する

### POST /api/surveys/public/{public_id}/responses

- idToken 不要
- session 必須
- Origin チェック必須
- Content-Type チェック必須

### GET /api/surveys/public/{public_id}/responses/{edit_token}

- idToken 不要
- session 必須
- 既存の `edit_token` / `allow_edit` 制御は維持する

### PUT /api/surveys/public/{public_id}/responses/{edit_token}

- idToken 不要
- session 必須
- Origin チェック必須
- Content-Type チェック必須
- 既存の `edit_token` / `allow_edit` 制御は維持する

---

## Table Migration

- セッション専用テーブルは作らない。
- MVP の DB migration は不要。
- 既存の `respondents` / `responses` / `surveys` のスキーマも、セッション化のためには変更しない。
- 将来、以下が必要になった場合のみ `auth_sessions` テーブルを検討する。
  - セッション一覧表示
  - 管理者による強制ログアウト
  - 端末別ログイン管理
  - 監査ログ
  - 不審セッション検知

---

## Test Plan

### セッション発行

- `POST /api/liff/identify` 成功時に session cookie が発行されること。
- `POST /api/liff/identify` 成功時に `$_SESSION['respondent_id']` が保存されること。
- `$_SESSION['authenticated_at']` が保存されること。
- セッションに `line_user_id`, `line_display_name`, `csrf_token`, `last_seen_at` が保存されないこと。
- ログイン成功時に `session_regenerate_id(true)` が実行されること。

### セッション認証

- Cookie なしの場合、認証必須 API が 401 になること。
- 未初期化セッションの場合、認証必須 API が 401 になること。
- 期限切れセッションの場合、認証必須 API が 401 になること。
- 有効な session cookie があれば、idToken なしで回答保存・取得・編集更新ができること。

### Origin / Referer チェック

- unsafe method で期待外 Origin の場合、403 になること。
- unsafe method で Origin が空、Referer も不正な場合、403 になること。
- unsafe method で期待 Origin の場合、処理が通ること。

### Content-Type チェック

- unsafe method で `Content-Type: application/json` の場合、処理が通ること。
- unsafe method で `application/json; charset=utf-8` の場合、処理が通ること。
- unsafe method で `application/x-www-form-urlencoded` の場合、415 になること。
- unsafe method で `multipart/form-data` の場合、415 になること。
- unsafe method で `text/plain` の場合、415 になること。

### 既存仕様維持

- `allow_edit` の既存制御が壊れていないこと。
- `edit_token` の既存制御が壊れていないこと。
- 回答作成・取得・更新が respondent のセッションに正しく紐づくこと。
- 他 respondent の回答を操作できないこと。
- identify 系のレスポンス形式が、現行の frontend 実装に合わせて崩れていないこと。

### セッション保存先

- セッションファイルが Web 公開ディレクトリ外に作成されること。
- セッション保存先ディレクトリが存在しない場合に起動時または初回アクセス時に分かりやすく失敗すること。
- PHP 実行ユーザーが保存先に読み書きできること。

---

## Assumptions

- MVP は単一オリジン運用を前提にする。
- 本番では frontend と backend を同一オリジンで配信する。
- 開発時に Vite を別サーバーで動かす場合は、API へ届くように proxy か同等の接続経路を用意する。
- クロスサイト共有ログインは考えない。
- `SameSite=None` は使わない。
- CSRF token は使わない。
- unsafe method は `Origin` / `Referer` と `Content-Type` で防御する。
- CORS は原則不要、または必要最小限に限定する。
- `idToken` は初回本人確認、またはセッションが切れた後の再本人確認時にのみ使う。
- セッション有効期限は 14日。
- セッションの DB 永続化、監査、強制失効機能は後回しにする。
