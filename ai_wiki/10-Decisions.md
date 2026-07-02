# Decisions

## 2026-06-09: LIFF の期限切れ IDToken クリア

- `frontend/src/features/liff/useLiff.ts` では、`liff.init()` の前に期限切れの IDToken を localStorage から削除する処理を入れた
- 参考にしたのは以下の記事
  - https://zenn.dev/arahabica/articles/274bb147a91d8a
- 目的は、期限切れの `decodedIDToken` が LIFF 側の状態を不整合にし、再ログインや再初期化のたびに有効期限切れ扱いが残る問題を避けること
- この処理を追加した後、有効期限切れになる現象は解消した

## Notes

- この判断は `useLiff` の初期化安定化のための実装修正として扱う
- 将来同様の不整合が再発した場合は、まず localStorage 上の LIFF ストア状態を確認する

## 2026-07-02: 管理画面の自動復帰ログイン

- `/admin` 配下の保護ルートは `AdminShell` で共通ガードし、未認証時は `/admin/login?from=...` にリダイレクトする
- `/admin/login` は外部ブラウザで LINE 認証を行った後、`POST /api/admin/login` を自動実行して管理者セッションを作成する
- 認証後は `from` に保持した元の admin ルートへ自動復帰する
- この変更で、管理画面への直アクセス時にユーザーが手動で二度ログイン操作を行う必要はなくなる

## 2026-06-14: 下書き保存仕様

- 送信前の途中経過は `response_drafts` テーブルで管理する
- 下書きの保存対象は `answer_json` のみに限定する
- 下書きは 1 人 1 アンケートにつき 1 件とし、最新保存で上書きする
- 保持期間は `updated_at` 基準で 30 日とする
- 送信成功時は対応する下書きを削除する

## 2026-06-10: CoreServer デプロイ方針

- `deploy.sh` は作業ツリーをそのまま同期せず、一時ステージングに `backend/` と `public_html/` を組み立ててから CoreServer へ送る
- フロントエンドはステージング先の `public_html/` にビルドし、`public_html/api/index.php` はデプロイ時に生成する
- `public_html/.htaccess` は repo 管理の実体をそのまま staging にコピーし、`/admin` 用の Basic Auth 設定を保持する
- リモートでは `backend/.env`、`backend/storage/`、`.htpasswd` 系を残し、それ以外の不要な古い成果物は `rsync --delete` で整理する
- `DRY_RUN=1` では転送内容の確認だけ行い、実送信はしない

## 2026-06-15: window.confirm の廃止と共通確認モーダルの導入

- **背景**: iPhone Safari において、`history.pushState` や `replaceState` による画面遷移（React Router の遷移など）を行った後に `window.confirm()` を呼び出すと、ダイアログが表示されないことがある既知の不具合があるため。
- **対策**: `window.confirm()` の使用を全面的に禁止し、`ConfirmProvider` + `useConfirm()` による React ベースの共通確認モーダルを導入した。
- **利用方法**:
  - `useConfirm` フックから `confirm` 関数を取得して使用する。
  - `const result = await confirm({ message: '...', title: '...', danger: true })`
  - 結果は `Promise<boolean>` で返される。
- **メリット**: Safari のバグ回避に加え、UI の一貫性確保（特に破壊的操作の danger スタイル統一）と、React Router の遷移中にダイアログが消えない安定した挙動を実現できる。
