import type {
  ImportResult,
  RespondentMaster,
  CreateRespondentMasterRequest,
  UpdateRespondentMasterRequest,
} from './types';

const API_BASE = '/api/admin/respondent-masters';

export class FetchError extends Error {
  status: number;
  data: any;

  constructor(message: string, status: number, data: any) {
    super(message);
    this.name = 'FetchError';
    this.status = status;
    this.data = data;
  }
}

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

    throw new FetchError(
      errorDetail ? `${errorMessage}: ${errorDetail}` : errorMessage,
      response.status,
      errorData
    );
  }

  // For 204 No Content or similar, just return as cast T
  if (response.status === 204) {
    return {} as T;
  }

  const result = await response.json();
  return result.data;
}

export const adminRespondentMasterApi = {
  async list(): Promise<RespondentMaster[]> {
    return fetchJson<RespondentMaster[]>(API_BASE, {}, 'マスター一覧の取得に失敗しました');
  },

  async get(id: number): Promise<RespondentMaster> {
    return fetchJson<RespondentMaster>(`${API_BASE}/${id}`, {}, `マスター(ID:${id})の取得に失敗しました`);
  },

  async create(params: CreateRespondentMasterRequest): Promise<{ id: number }> {
    return fetchJson<{ id: number }>(
      API_BASE,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(params),
      },
      'マスターの作成に失敗しました'
    );
  },

  async update(id: number, params: UpdateRespondentMasterRequest): Promise<void> {
    await fetchJson<void>(
      `${API_BASE}/${id}`,
      {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(params),
      },
      'マスターの更新に失敗しました'
    );
  },

  async delete(id: number): Promise<void> {
    await fetchJson<void>(
      `${API_BASE}/${id}`,
      {
        method: 'DELETE',
      },
      'マスターの削除に失敗しました'
    );
  },

  async import(file: File): Promise<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);

    return fetchJson<ImportResult>(
      `${API_BASE}/import`,
      {
        method: 'POST',
        body: formData,
      },
      'インポートに失敗しました'
    );
  },
};
