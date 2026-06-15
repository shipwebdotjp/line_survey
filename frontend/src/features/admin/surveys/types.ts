export type SurveyStatus = 'draft' | 'published' | 'closed' | 'archived';

export interface QuestionElement {
  type: string;
  name: string;
  title?: string;
  [key: string]: any;
}

export interface SurveyPage {
  name: string;
  elements: QuestionElement[];
  [key: string]: any;
}

export interface QuestionsJSON {
  pages: SurveyPage[];
  [key: string]: any;
}

export interface Survey {
  id: number;
  public_id: string;
  title: string;
  description?: string;
  questions_json: QuestionsJSON;
  status: SurveyStatus;
  allow_multiple: boolean;
  allow_edit: boolean;
  starts_at?: string;
  ends_at?: string;
  send_confirmation_email: boolean;
  include_answers_in_email: boolean;
  created_at: string;
  updated_at: string;
  response_count?: number;
}

export interface SurveyCreateParams {
  title: string;
  description?: string;
  status: SurveyStatus;
  questions_json: QuestionsJSON;
  allow_multiple: boolean;
  allow_edit: boolean;
  starts_at?: string;
  ends_at?: string;
  send_confirmation_email: boolean;
  include_answers_in_email: boolean;
}

export interface SurveyUpdateParams extends Partial<SurveyCreateParams> {}

export interface ResponseSummary {
  id: number;
  respondent_name: string;
  respondent_email: string;
  respondent_line_display_name: string;
  respondent_honorific: string;
  submitted_at: string;
  updated_at: string;
  email_sent_at: string | null;
  email_error: string | null;
}

export interface ResponseDetail {
  id: number;
  answer_json: Record<string, any>;
  survey_snapshot_json: QuestionsJSON;
  submitted_at: string;
  updated_at: string;
  email_sent_at: string | null;
  email_error: string | null;
  respondent: {
    name: string;
    email: string;
    line_display_name: string;
    honorific: string;
    is_manually_entered: boolean;
    respondent_master_id: number | null;
  };
}

export interface SurveySummaryChoice {
  value: any;
  label: string;
  count: number;
  rate: number;
}

export interface QuestionSummary {
  name: string;
  title: string;
  type: string;
  targetCount: number;
  answeredCount: number;
  emptyCount: number;
  answers?: string[];
  choices?: SurveySummaryChoice[];
}

export interface SurveySummary {
  totalResponses: number;
  questions: QuestionSummary[];
}
