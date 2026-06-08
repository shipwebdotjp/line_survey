import type { ImportResult, RespondentMaster } from './types';

const API_BASE = '/api/admin/respondent-masters';

async function fetchJson<T>(
  url: string,
  options?: RequestInit,
  errorMessage = 'Failed to fetch'
): Promise<T> {
  const response = await fetch(url, options);

  if (!response.ok) {
    let errorDetail = '';
    const contentType = response.headers.get('content-type');

    if (contentType && contentType.includes('application/json')) {
      try {
        const errorData = await response.json();
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

    throw new Error(errorDetail ? `${errorMessage}: ${errorDetail}` : errorMessage);
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
