import { fetchAdmin } from '../lib/adminFetch';
import type {
  ImportResult,
  RespondentMaster,
  CreateRespondentMasterRequest,
  UpdateRespondentMasterRequest,
} from './types';

const API_BASE = '/api/manage/respondent-masters';

export const adminRespondentMasterApi = {
  async list(): Promise<RespondentMaster[]> {
    return fetchAdmin<RespondentMaster[]>(API_BASE, {}, 'マスター一覧の取得に失敗しました');
  },

  async get(id: number): Promise<RespondentMaster> {
    return fetchAdmin<RespondentMaster>(`${API_BASE}/${id}`, {}, `マスター(ID:${id})の取得に失敗しました`);
  },

  async create(params: CreateRespondentMasterRequest): Promise<{ id: number }> {
    return fetchAdmin<{ id: number }>(
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
    await fetchAdmin<void>(
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
    await fetchAdmin<void>(
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

    return fetchAdmin<ImportResult>(
      `${API_BASE}/import`,
      {
        method: 'POST',
        body: formData,
      },
      'インポートに失敗しました'
    );
  },
};
