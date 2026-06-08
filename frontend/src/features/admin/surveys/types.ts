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
