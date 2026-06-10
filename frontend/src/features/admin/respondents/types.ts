export interface Respondent {
  id: number;
  line_user_id: string;
  line_display_name: string;
  respondent_master_id: number | null;
  name: string;
  email: string;
  honorific: string | null;
  is_manually_entered: boolean;
  created_at: string;
  updated_at: string;
}

export interface RespondentSummary extends Respondent {
  response_count: number;
  latest_submitted_at: string | null;
}

export interface RespondentResponseHistory {
  response_id: number;
  survey_id: number;
  survey_public_id: string;
  survey_title: string;
  submitted_at: string;
  updated_at: string;
}

export interface RespondentDetail extends Respondent {
  responses: RespondentResponseHistory[];
}

export interface UpdateRespondentRequest {
  name: string;
  email: string;
  honorific: string | null;
}
