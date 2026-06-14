# 途中経過保存仕様書の追加

  - [docs/savedrafts/spec.md](/Users/ship/project/php/line_survey/docs/savedrafts/spec.md) を新規作成し、送信前の途中経過保存に限定した仕様を切り出す。
  - 対象は response_drafts テーブルと、その取得・保存・削除 API に限定する。
  - 進捗位置やページ再開情報は今回は扱わず、後続仕様に分離する。

  ## Key Changes

  - データモデルは response_drafts を新設し、survey_id、respondent_id、answer_json、created_at、updated_at を持たせる。
  - 制約は UNIQUE(survey_id, respondent_id) を基本にし、surveys と respondents への外部キーを張る。
  - 下書きは 1 人 1 アンケートにつき 1 件のみとし、最新の途中経過で上書きする。
  - 保持期間は 30 日を既定とし、期限超過分は削除対象とする。
  - edit_token、survey_snapshot_json、submitted_at は下書きには持たせない。
  - API は GET /api/surveys/public/{public_id}/response-draft、PUT /api/surveys/public/{public_id}/response-draft、DELETE /api/surveys/public/{public_id}/response-draft を定義する。
  - 認可は既存の LIFF セッション前提とし、respondent_id が一致する場合のみ操作可能とする。
  - PUT は answer_json のみ受け付ける idempotent な upsert とし、フロントは debounce 自動保存を前提にする。
  - 本番送信時は既存の responses が正本で、成功後に対応する draft を削除する。
  - allow_multiple は送信済み回答の挙動にだけ影響させ、下書きは常に単一の編集中状態として扱う。

  ## Test Plan

  - 下書き未作成時の GET は 404 になること。
  - PUT 初回で draft が作成され、以後は同一行が更新されること。
  - GET が直近の answer_json を返すこと。
  - 他ユーザーのセッションでは draft を読めないこと。
  - 送信成功後に draft が削除されること。
  - 30 日超過の draft が削除対象になること。

  ## Assumptions

  - 下書きは 1 人 1 アンケートにつき 1 件のみ。
  - 途中経過は answer_json だけを保存する。
  - UI は自動保存を前提にする。
  - 保持期間は 30 日を既定値にする。
