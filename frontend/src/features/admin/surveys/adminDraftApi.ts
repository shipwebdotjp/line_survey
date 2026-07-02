import { fetchAdmin } from '../lib/adminFetch';
import type { ResponseDraft } from '../../survey/types';

export const adminDraftApi = {
  async list(): Promise<ResponseDraft[]> {
    const data = await fetchAdmin<{ drafts: ResponseDraft[] }>(
      '/api/manage/response-drafts',
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
    const data = await fetchAdmin<{ draft: ResponseDraft | null }>(
      `/api/manage/response-drafts/${id}`,
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
    return fetchAdmin<{ deleted_count: number; message: string }>(
      '/api/manage/response-drafts/cleanup',
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
