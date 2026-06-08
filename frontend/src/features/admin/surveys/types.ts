export type SurveyStatus = 'draft' | 'published' | 'closed' | 'archived';

export interface Survey {
  id: number;
  public_id: string;
  title: string;
  description: string | null;
  questions_json: any;
  status: SurveyStatus;
  allow_multiple: boolean;
  allow_edit: boolean;
  starts_at: string | null;
  ends_at: string | null;
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
  questions_json: any;
  allow_multiple: boolean;
  allow_edit: boolean;
  starts_at?: string | null;
  ends_at?: string | null;
  send_confirmation_email: boolean;
  include_answers_in_email: boolean;
}

export interface SurveyUpdateParams extends Partial<SurveyCreateParams> {}
