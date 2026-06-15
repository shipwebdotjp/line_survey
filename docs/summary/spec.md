# アンケート集計要約ページ 仕様書

## 1. 目的

回答データ `answer_json` と、回答時点のアンケート定義 `survey_snapshot_json` をもとに、Googleフォームの「要約」タブのような集計ページを提供する。

- 自由記述系は回答一覧として表示する
- 選択式は選択肢ごとの件数・割合をグラフ表示する
- アンケート項目が途中で変更されても、回答時点の snapshot を考慮して集計する

---

## 2. 前提データ

### 回答レコード

各回答レコードは以下を持つ。

```txt
answer_json
survey_snapshot_json
```

### answer_json 例

```json
{"name":"田中太郎","dinner":"希望する","share":"参加する"}
```

```json
{"share":"参加する","name":"山田花子"}
```

```json
{"share":"参加する","note":"お弁当の種類は選べますか？","dinner":"希望する"}
```

### survey_snapshot_json 例

```json
{
  "pages": [
    {
      "name": "page1",
      "elements": [
        {
          "type": "text",
          "name": "name",
          "title": "参加者全員のお名前"
        },
        {
          "type": "boolean",
          "name": "dinner",
          "title": "夕食の会食への参加を希望する",
          "valueTrue": "希望する",
          "valueFalse": "希望しない",
          "useTitleAsLabel": true,
          "renderAs": "checkbox"
        },
        {
          "type": "boolean",
          "name": "share",
          "title": "費用の分担に参加する",
          "valueTrue": "参加する",
          "valueFalse": "参加しない",
          "useTitleAsLabel": true,
          "renderAs": "checkbox"
        },
        {
          "type": "text",
          "name": "note",
          "title": "その他ご質問やお伝えしたいこと"
        }
      ]
    }
  ]
}
```

---

## 3. 設問の同一視ルール

設問は `element.name` をキーとして同一視する。

```txt
設問ID = element.name
```

以下が変わっても、同じ `name` であれば同じ設問として集計する。

- title
- type
- choices
- valueTrue
- valueFalse
- その他設定

例：

```txt
name = dinner
```

であれば、途中で title が変更されても同じ設問として扱う。

---

## 4. 集計対象の設問

集計ページには、回答に関連する全設問を表示する。

具体的には以下を対象にする。

1. `surveys.questions_json` に存在する設問
2. 各回答レコードの `survey_snapshot_json` に存在する設問
3. `answer_json` に存在する設問

つまり、現在のフォームから削除された過去の設問も、回答に存在する場合は表示対象に含める。

---

## 5. 表示順

表示順は以下のルールとする。

1. `surveys.questions_json` に存在する設問は、その定義順で表示する
2. `surveys.questions_json` に存在しない設問は、回答・snapshot から検出したものを後ろに追加する
3. 追加分の順序は特に保証しない

---

## 6. 表示用の設問定義の優先順位

`title`, `type`, `choices`, `valueTrue`, `valueFalse` などの表示・集計用メタ情報は以下の優先順位で取得する。

1. `surveys.questions_json`
2. 各回答レコードの `survey_snapshot_json` で最初に見つかった定義
3. `answer_json` の値から推定

---

## 7. 設問が回答時点に存在したかの判定

未回答判定や母数計算では、各回答レコードの `survey_snapshot_json` を見る。

ある回答レコードにおいて、設問 `name` が `survey_snapshot_json` に存在する場合、その回答者はその設問の対象者とする。

```txt
targetCount = survey_snapshot_json にその設問が存在した回答数
```

逆に、`survey_snapshot_json` にその設問が存在しない場合、その回答者はその設問の集計母数に含めない。

---

## 8. 対応する設問タイプ

初期対応する設問タイプは以下。

| type | 表示方法 |
|---|---|
| text | 回答一覧カード |
| comment | 回答一覧カード |
| boolean | 円グラフ |
| checkbox | 横棒グラフ |
| radiogroup | 横棒グラフ |
| dropdown | 横棒グラフ |

未対応タイプは、初期実装では非表示または「未対応タイプ」として扱う。

---

## 9. text / comment の集計仕様

### 表示内容

`text` / `comment` は回答一覧として表示する。

### 集計ルール

- 値が存在し、空文字でないものを回答として扱う
- 空文字、null、未定義は回答一覧に表示しない
- 件数として以下を返す

```txt
targetCount
answeredCount
emptyCount
```

### 定義

```txt
targetCount = その設問が snapshot に存在した回答数
answeredCount = 実際に値がある回答数
emptyCount = targetCount - answeredCount
```

### 表示例

```txt
その他ご質問やお伝えしたいこと

1件の回答

- お弁当の種類は選べますか？
```

---

## 10. boolean の集計仕様

### 表示方法

円グラフで表示する。

### 集計キー

boolean は、実データ値をそのまま集計キーとする。

例：

```json
{"dinner": "希望する"}
```

の場合、`"希望する"` をキーとしてカウントする。

### 未入力時の扱い

`survey_snapshot_json` に設問が存在するが、`answer_json` にその項目が存在しない場合は、false 相当として扱う。

```txt
answer_json に項目がない
かつ
snapshot に boolean 設問として存在する
```

この場合：

- `valueFalse` がある場合：`valueFalse` の値として集計する
- `valueFalse` がない場合：`0` として集計する

### answeredCount

boolean は未入力も false 相当として扱うため、以下とする。

```txt
answeredCount = targetCount
emptyCount = 0
```

### 例

設問定義：

```json
{
  "type": "boolean",
  "name": "dinner",
  "valueTrue": "希望する",
  "valueFalse": "希望しない"
}
```

回答：

```json
{"dinner": "希望する"}
```

```json
{}
```

集計：

```txt
希望する: 1
希望しない: 1
```

---

## 11. checkbox の集計仕様

### 表示方法

横棒グラフで表示する。

### 値形式

checkbox の回答値は配列を想定する。

```json
{
  "foods": ["和食", "洋食"]
}
```

### 集計ルール

- 配列内の各値をそれぞれ1カウントする
- 複数選択のため、合計件数は `targetCount` を超えてよい
- 割合の母数は `targetCount`

### 例

```txt
対象者: 10人

和食: 6件 60%
洋食: 4件 40%
中華: 3件 30%
```

---

## 12. radiogroup / dropdown の集計仕様

### 表示方法

横棒グラフで表示する。

### 集計ルール

- `answer_json[name]` の値を集計キーにする
- 値が存在しない場合は未回答として扱う
- 割合の母数は回答済み人数ベースとする

### 件数定義

```txt
targetCount = その設問が snapshot に存在した回答数
answeredCount = answer_json に有効値が存在する回答数
emptyCount = targetCount - answeredCount
```

---

## 13. 選択肢ラベルの取得ルール

### choices が文字列配列の場合

```json
"choices": ["A", "B", "C"]
```

以下のように扱う。

```txt
value = "A"
label = "A"
```

### choices がオブジェクト配列の場合

```json
"choices": [
  { "value": "a", "text": "Aプラン" },
  { "value": "b", "text": "Bプラン" }
]
```

以下のように扱う。

```txt
value = "a"
label = "Aプラン"
```

### boolean の場合

boolean は実データ値を集計キーにする。

表示ラベルも原則として実データ値を使う。

ただし、未入力を false 相当に補完する場合は以下を使う。

```txt
valueFalse があれば valueFalse
なければ 0
```

---

## 14. 割合計算

### boolean

```txt
rate = count / targetCount * 100
```

boolean は未入力も false 相当として扱うため、母数は `targetCount`。

### checkbox

```txt
rate = count / targetCount * 100
```

複数選択なので、合計割合は100%を超えてよい。

### radiogroup / dropdown

```txt
rate = count / answeredCount * 100
```

未回答はグラフの割合母数には含めない。

---

## 15. APIレスポンス形式

Backend は集計済みデータを返す。

### エンドポイント例

```http
GET /api/surveys/{surveyId}/summary
```

### レスポンス例

```json
{
  "totalResponses": 3,
  "questions": [
    {
      "name": "name",
      "title": "参加者全員のお名前",
      "type": "text",
      "targetCount": 3,
      "answeredCount": 2,
      "emptyCount": 1,
      "answers": [
        "田中太郎",
        "山田花子"
      ]
    },
    {
      "name": "dinner",
      "title": "夕食の会食への参加を希望する",
      "type": "boolean",
      "targetCount": 3,
      "answeredCount": 3,
      "emptyCount": 0,
      "choices": [
        {
          "value": "希望する",
          "label": "希望する",
          "count": 2,
          "rate": 66.7
        },
        {
          "value": "希望しない",
          "label": "希望しない",
          "count": 1,
          "rate": 33.3
        }
      ]
    },
    {
      "name": "share",
      "title": "費用の分担に参加する",
      "type": "boolean",
      "targetCount": 3,
      "answeredCount": 3,
      "emptyCount": 0,
      "choices": [
        {
          "value": "参加する",
          "label": "参加する",
          "count": 3,
          "rate": 100
        },
        {
          "value": "参加しない",
          "label": "参加しない",
          "count": 0,
          "rate": 0
        }
      ]
    },
    {
      "name": "note",
      "title": "その他ご質問やお伝えしたいこと",
      "type": "text",
      "targetCount": 3,
      "answeredCount": 1,
      "emptyCount": 2,
      "answers": [
        "お弁当の種類は選べますか？"
      ]
    }
  ]
}
```

---

## 16. フロントエンド表示仕様

React 側では `type` に応じて表示コンポーネントを切り替える。

### text / comment

回答一覧カード。

表示内容：

- 設問タイトル
- 回答件数
- 回答一覧

### boolean

円グラフ。

表示内容：

- 設問タイトル
- 対象者数
- 各選択肢の件数
- 各選択肢の割合
- 円グラフ

### checkbox / radiogroup / dropdown

横棒グラフ。

表示内容：

- 設問タイトル
- 対象者数
- 回答済み数
- 未回答数
- 各選択肢の件数
- 各選択肢の割合
- 横棒グラフ

---

## 17. バックエンド集計処理の流れ

PHP Slim Framework 側で以下の流れで集計する。

```txt
1. surveyId に紐づく回答レコードを取得
2. surveys.questions_json を取得
3. questions_json から設問一覧を作る
4. 各回答の survey_snapshot_json から設問定義を補完する
5. answer_json にのみ存在する設問も補完する
6. 表示順を決定する
7. 各回答ごとに、snapshot にその設問が存在するか判定する
8. type ごとのルールで集計する
9. APIレスポンス形式に整形する
```

---

## 18. 補足仕様

### answer_json にあるが snapshot にない項目

基本的には例外的なデータとして扱う。

ただし表示対象から完全には除外せず、設問定義がない項目として末尾に追加する。

この場合：

```txt
type = unknown または text 相当
title = name
```

として扱う。

### 未対応タイプ

初期実装では以下のように扱う。

```txt
type = unsupported
```

またはフロント側で非表示にする。

