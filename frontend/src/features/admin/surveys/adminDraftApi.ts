import type { ResponseDraft } from '../../survey/types';

export const adminDraftApi = {
  async list(): Promise<ResponseDraft[]> {
    const response = await fetch('/api/admin/response-drafts', {
      headers: {
        'Accept': 'application/json',
      },
    });
    if (!response.ok) {
      throw new Error('下書き一覧の取得に失敗しました。');
    }
    const result = await response.json();
    return result.drafts;
  },

  async get(id: number): Promise<ResponseDraft> {
    const response = await fetch(`/api/admin/response-drafts/${id}`, {
      headers: {
        'Accept': 'application/json',
      },
    });
    if (!response.ok) {
      throw new Error('下書きの取得に失敗しました。');
    }
    const result = await response.json();
    return result.draft;
  },

  async cleanup(): Promise<{ deleted_count: number; message: string }> {
    const response = await fetch('/api/admin/response-drafts/cleanup', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
      },
    });
    if (!response.ok) {
      throw new Error('クリーンアップに失敗しました。');
    }
    return await response.json();
  },
};
