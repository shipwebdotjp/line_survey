# Frontend

React / Vite / TypeScript の画面実装を置くディレクトリです。

## Environment Variables

Vite の環境変数は `frontend/` 配下の `.env` 系ファイルから読み込まれます。

- ローカル専用の値は `frontend/.env.local` に置く
- ブラウザから参照する変数名は `VITE_` で始める
- LIFF ID は `VITE_LIFF_ID` として定義する
- 敬称候補は `VITE_RESPONDENT_HONORIFICS` にカンマ区切りで定義する

例:

```env
VITE_LIFF_ID=your-liff-id
VITE_RESPONDENT_HONORIFICS=さん,様,先生
```

## Scripts

`frontend/package.json` の主なコマンド:

- `npm run dev`
- `npm run build`
- `npm run lint`
- `npm run preview`
