import type {
  RespondentSummary,
  RespondentDetail,
  UpdateRespondentRequest,
} from './types';

const API_BASE = '/api/admin/respondents';

async function fetchJson<T>(
  url: string,
  options?: RequestInit,
  errorMessage = 'Failed to fetch'
): Promise<T> {
  const response = await fetch(url, options);

  if (!response.ok) {
    let errorDetail = '';
    let errorData: any = null;
    const contentType = response.headers.get('content-type');

    if (contentType && contentType.includes('application/json')) {
      try {
        errorData = await response.json();
        errorDetail = errorData.error || errorData.message || '';
      } catch {
        // Fallback if parsing fails despite header
      }
    }

    if (!errorDetail) {
      try {
        errorDetail = await response.text();
      } catch {
        // Fallback if text read fails
      }
    }

    const error: any = new Error(errorDetail ? `${errorMessage}: ${errorDetail}` : errorMessage);
    error.status = response.status;
    error.data = errorData;
    throw error;
  }

  if (response.status === 204) {
    return {} as T;
  }

  const result = await response.json();
  return result.data;
}

export interface ApiError {
  error: string;
  code: string;
  details?: Record<string, string>;
}

export const adminRespondentApi = {
  async list(): Promise<RespondentSummary[]> {
    return fetchJson<RespondentSummary[]>(API_BASE, {}, '回答者一覧の取得に失敗しました');
  },

  async get(id: number): Promise<RespondentDetail> {
    return fetchJson<RespondentDetail>(`${API_BASE}/${id}`, {}, `回答者(ID:${id})の取得に失敗しました`);
  },

  async update(id: number, params: UpdateRespondentRequest): Promise<void> {
    await fetchJson<void>(
      `${API_BASE}/${id}`,
      {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(params),
      },
      '回答者の更新に失敗しました'
    );
  },

  async delete(id: number): Promise<void> {
    await fetchJson<void>(
      `${API_BASE}/${id}`,
      {
        method: 'DELETE',
      },
      '回答者の削除に失敗しました'
    );
  },
};
