import type { ResponseDraft } from '../../survey/types';

async function fetchDraftJson<T>(
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

  const result = await response.json();
  return result.data as T;
}

export const adminDraftApi = {
  async list(): Promise<ResponseDraft[]> {
    const data = await fetchDraftJson<{ drafts: ResponseDraft[] }>(
      '/api/admin/response-drafts',
      {
        headers: {
          'Accept': 'application/json',
        },
      },
      '下書き一覧の取得に失敗しました'
    );

    return data.drafts ?? [];
  },

  async get(id: number): Promise<ResponseDraft> {
    const data = await fetchDraftJson<{ draft: ResponseDraft | null }>(
      `/api/admin/response-drafts/${id}`,
      {
        headers: {
          'Accept': 'application/json',
        },
      },
      '下書きの取得に失敗しました'
    );

    if (!data.draft) {
      throw new Error('下書きが見つかりませんでした。');
    }

    return data.draft;
  },

  async cleanup(): Promise<{ deleted_count: number; message: string }> {
    return fetchDraftJson<{ deleted_count: number; message: string }>(
      '/api/admin/response-drafts/cleanup',
      {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
        },
      },
      'クリーンアップに失敗しました'
    );
  },
};
