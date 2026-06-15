# アンケート集計要約ページ 実装タスクリスト

`docs/summary/spec.md` を前提に、backend → frontend の順で進めるためのタスクリスト。
API の集計結果を先に固めてから、管理画面での表示を載せる。

---

## Backend

- [ ] 1. 集計対象データをまとめて扱う土台を作る
  - `surveys.questions_json`、`responses.answer_json`、`responses.survey_snapshot_json` を同じ流れで読めるようにする
  - 設問は `name` 基準で同一視し、表示順は `questions_json` 優先で組み立てる
  - 設問メタ情報は `questions_json` → snapshot → answer の順で補完する

- [ ] 2. `GET /api/surveys/{surveyId}/summary` を実装する
  - `totalResponses` と `questions[]` を返す集計 API を用意する
  - 回答が 0 件、または過去設問だけが残るケースでも破綻しないようにする
  - 管理画面からそのまま使える JSON 形式に整える

- [ ] 3. 設問タイプごとの集計ロジックを実装する
  - `text` / `comment` は回答一覧として扱う
  - `boolean` は選択肢ごとの件数・割合を返す
  - `checkbox` / `radiogroup` / `dropdown` はグラフ表示用に件数・割合を返す
  - 未対応タイプや `answer_json` にだけ存在する項目の扱いをそろえる

- [ ] 4. 集計結果の表示ルールを固める
  - 空文字、未入力、false 相当の扱いを仕様に合わせて統一する
  - 選択肢ラベル、未回答数、割合計算の基準を固定する
  - フロントが迷わないように、必要な補助情報をレスポンスへ含める

## Frontend

- [ ] 1. 集計 API の型とクライアントを追加する
  - summary 用のレスポンス型を定義する
  - `GET /api/surveys/{surveyId}/summary` を呼ぶ API ヘルパーを作る
  - 既存の admin API と同じエラーハンドリングに寄せる

- [ ] 2. 管理画面に集計ページの導線を追加する
  - `admin/surveys/:id/summary` のルートを追加する
  - `AdminShell` のパンくずとサイドメニューに「要約」を入れる
  - 回答一覧からも移動しやすい場所にリンクを置く

- [ ] 3. 設問タイプ別の表示コンポーネントを作る
  - 依存関係にRechartsを入れる
  - `text` / `comment` 用の回答一覧カードを作る
  - `boolean` 用の円グラフ表示を作る（Recharts の PieChart使用）
  - `checkbox` / `radiogroup` / `dropdown` 用の横棒グラフ表示を作る（ Recharts BarChart使用）
  - 共通で使う件数・割合・ラベル表示をまとめる

- [ ] 4. 集計ページ本体を組み立てる
  - 読み込み中・空データ・エラーの状態を用意する
  - 設問を仕様どおりの順序で並べ、各カードを描画する
  - 管理画面らしい見やすさを保ちながら、スマホでも崩れないレイアウトにする

