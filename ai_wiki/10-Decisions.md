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
