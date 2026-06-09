export interface Respondent {
  id?: number;
  name: string;
  email: string;
  honorific: string | null;
  line_display_name?: string;
}

export type IdentifyStatus = 'existing' | 'matched' | 'manual_required' | 'manual_saved';

export interface IdentifyResponse {
  status: IdentifyStatus;
  respondent: Respondent | null;
  error?: string;
  message?: string;
}

/**
 * Represents the raw response from the survey response API.
 * Note: Properties follow the backend snake_case naming convention.
 */
export interface SurveyResponse {
  id: number;
  survey_id: number;
  respondent_id: number;
  edit_token: string;
  answer_json: Record<string, any>;
  survey_snapshot_json: Record<string, any>;
  submitted_at: string;
  email_sent_at: string | null;
  email_error: string | null;
  created_at: string;
  updated_at: string;
}

export interface SaveResponseResult {
  data?: SurveyResponse;
  error?: string;
  code?: string;
}

export interface SurveyData {
  can_answer: boolean;
  reason: 'not_published' | 'not_started' | 'closed' | null;
  survey: {
    title: string;
    description: string;
    questions_json: Record<string, any>;
    allow_multiple: boolean;
    allow_edit: boolean;
    starts_at: string | null;
    ends_at: string | null;
  } | null;
}

export interface ResponseHistoryItem {
  response_public_id: string; // Not actually used yet as we use edit_token for editing, but the API returns it
  submitted_at: string;
  updated_at: string;
  survey_public_id: string | null;
  survey_title: string | null;
}
