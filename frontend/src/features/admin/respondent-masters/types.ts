export interface RespondentMaster {
  id: number;
  master_code: string;
  line_display_name: string;
  name: string;
  honorific: string | null;
  email: string;
  note: string | null;
  created_at: string;
  updated_at: string;
}

export interface ImportResult {
  imported: number;
  errors: Array<{
    row: number;
    reason: string;
  }>;
}

export interface CreateRespondentMasterRequest {
  master_code: string;
  line_display_name: string;
  name: string;
  email: string;
  honorific?: string | null;
  note?: string | null;
}

export interface UpdateRespondentMasterRequest {
  master_code: string;
  line_display_name: string;
  name: string;
  email: string;
  honorific?: string | null;
  note?: string | null;
}
