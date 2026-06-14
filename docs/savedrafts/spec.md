# 途中経過保存仕様

## 1. 文書の目的

本書は、送信前のアンケート途中経過をサーバー側に保存・復元するための仕様を定義する。  
復元対象は `answer_json` のみとし、ページ遷移位置や進捗ステップの復元は今回のスコープ外とする。

## 2. 基本方針

- 途中経過は送信済み回答 `responses` とは分離して保存する
- 下書きは 1 人 1 アンケートにつき 1 件のみ保持する
- 下書きは LIFF セッションで識別された `respondent_id` に紐づける
- 保存対象は SurveyJS の回答 JSON である `answer_json` とする
- 送信完了後は対応する下書きを削除する
- 保持期間は最終保存時刻から 30 日とする

## 3. データ設計

### 3.1 `response_drafts`

送信前の回答途中経過を保持する。

#### カラム

- `id`
- `survey_id`
- `respondent_id`
- `answer_json`
- `created_at`
- `updated_at`

#### カラム定義

```sql
CREATE TABLE response_drafts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  survey_id INT NOT NULL,
  respondent_id INT NOT NULL,
  answer_json JSON NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  UNIQUE KEY unique_survey_respondent (survey_id, respondent_id),
  INDEX idx_survey_id (survey_id),
  INDEX idx_respondent_id (respondent_id),

  CONSTRAINT fk_response_drafts_survey
    FOREIGN KEY (survey_id) REFERENCES surveys (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT fk_response_drafts_respondent
    FOREIGN KEY (respondent_id) REFERENCES respondents (id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);
```

#### 設計意図

- `survey_id + respondent_id` を一意にして、同一ユーザーの最新下書きだけを保持する
- `answer_json` は SurveyJS が返す回答オブジェクトをそのまま保存する
- `survey_id` と `respondent_id` の両方に検索用インデックスを置く
- `survey` または `respondent` の削除時は関連下書きも削除する

#### 保存しないもの

- `survey_snapshot_json`
- `edit_token`
- `submitted_at`
- 進捗ステップ情報
- ページ番号
- 現在の設問位置

## 4. ライフサイクル

### 4.1 作成・更新

- 初回保存時は `response_drafts` に新規作成する
- 2 回目以降は同じ `(survey_id, respondent_id)` 行を上書きする
- `updated_at` は保存のたびに更新する
- `created_at` は初回作成時刻を保持する

### 4.2 復元

- LIFF セッションが有効で、かつ `respondent_id` が解決できる場合のみ復元対象とする
- フロントが復元に使う値は `answer_json` のみとする
- 下書きが存在しない場合は空状態として扱う

### 4.3 送信完了時の扱い

- 回答送信に成功したら、対応する `response_drafts` を削除する
- 回答送信が失敗した場合は下書きを残す
- 編集済み回答の保存は下書きとは別系統の `responses` を使う

### 4.4 保持期限

- 下書きの有効期限は `updated_at + 30日` とする
- 保存が行われるたびに期限は延長される
- 期限超過した下書きは削除対象とする

## 5. API 設計

### 5.1 共通前提

- 利用には LIFF セッションが必要
- `respondent_id` はセッションから解決する
- `public_id` から対象アンケートを特定する
- アンケートが回答不可状態の場合は下書き API も利用不可とする

### 5.2 `GET /api/surveys/public/{public_id}/response-draft`

現在の回答者に紐づく下書きを取得する。

#### 挙動

- セッション未確立なら `401`
- `public_id` が存在しなければ `404`
- アンケートが公開・期間条件を満たさない場合は `403`
- 該当下書きがなければ `200` で空状態を返す

#### レスポンス例

```json
{
  "draft": null
}
```

```json
{
  "draft": {
    "survey_public_id": "sv_p7Kf92LpQaXz3MnT8bQYv2A",
    "respondent_id": 12,
    "answer_json": {
      "q1": "A",
      "q2": ["x", "y"]
    },
    "created_at": "2026-06-14T10:00:00+09:00",
    "updated_at": "2026-06-14T10:05:00+09:00"
  }
}
```

### 5.3 `PUT /api/surveys/public/{public_id}/response-draft`

現在の回答者の下書きを保存または更新する。

#### リクエストボディ

```json
{
  "answer_json": {}
}
```

#### 挙動

- セッション未確立なら `401`
- `public_id` が存在しなければ `404`
- アンケートが公開・期間条件を満たさない場合は `403`
- `answer_json` が JSON オブジェクトでなければ `422`
- `(survey_id, respondent_id)` が既存なら更新、なければ作成する
- 保存後は最新の `answer_json` と `updated_at` を返す

#### レスポンス例

```json
{
  "draft": {
    "survey_public_id": "sv_p7Kf92LpQaXz3MnT8bQYv2A",
    "respondent_id": 12,
    "answer_json": {
      "q1": "A"
    },
    "created_at": "2026-06-14T10:00:00+09:00",
    "updated_at": "2026-06-14T10:05:00+09:00"
  }
}
```

### 5.4 `DELETE /api/surveys/public/{public_id}/response-draft`

現在の回答者の下書きを削除する。

#### 挙動

- セッション未確立なら `401`
- `public_id` が存在しなければ `404`
- アンケートが公開・期間条件を満たさない場合は `403`
- 該当下書きがなければ成功扱いで `204`
- 削除後は空状態として扱う

## 6. エラー方針

- 認証失敗は `401`
- 対象アンケート不在は `404`
- 対象アンケートが回答不可なら `403`
- `answer_json` の形式不正は `422`
- 仕様上の空状態はエラーにしない

## 7. 既存機能との関係

- `POST /api/surveys/public/{public_id}/responses` は送信完了後に下書きを削除する
- `GET /api/surveys/public/{public_id}/responses/current` は送信済み回答の参照に留める
- `responses` は正本、`response_drafts` は一時保存とする

## 8. 実装上の補足

- 自動保存前提のため、フロントは入力変更を debounce して `PUT` する
- 下書きは送信済み回答の代替ではなく、送信前の再開用データとして扱う
- 進捗復元を追加する場合は、`response_drafts` に別カラムを増やすか別テーブルに分離する
