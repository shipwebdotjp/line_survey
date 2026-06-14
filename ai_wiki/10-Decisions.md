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
