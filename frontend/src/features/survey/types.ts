export interface Respondent {
  id?: number;
  name: string;
  email: string;
  honorific: string | null;
}

export type IdentifyStatus = 'existing' | 'matched' | 'manual_required' | 'manual_saved';

export interface IdentifyResponse {
  status: IdentifyStatus;
  respondent: Respondent | null;
  error?: string;
  message?: string;
}
