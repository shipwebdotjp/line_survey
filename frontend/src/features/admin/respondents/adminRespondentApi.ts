import { fetchAdmin } from '../lib/adminFetch';
import type {
  RespondentSummary,
  RespondentDetail,
  UpdateRespondentRequest,
} from './types';

const API_BASE = '/api/admin/respondents';

export interface ApiError {
  error: string;
  code: string;
  details?: Record<string, string>;
}

export const adminRespondentApi = {
  async list(): Promise<RespondentSummary[]> {
    return fetchAdmin<RespondentSummary[]>(API_BASE, {}, '回答者一覧の取得に失敗しました');
  },

  async get(id: number): Promise<RespondentDetail> {
    return fetchAdmin<RespondentDetail>(`${API_BASE}/${id}`, {}, `回答者(ID:${id})の取得に失敗しました`);
  },

  async update(id: number, params: UpdateRespondentRequest): Promise<void> {
    await fetchAdmin<void>(
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
    await fetchAdmin<void>(
      `${API_BASE}/${id}`,
      {
        method: 'DELETE',
      },
      '回答者の削除に失敗しました'
    );
  },
};
