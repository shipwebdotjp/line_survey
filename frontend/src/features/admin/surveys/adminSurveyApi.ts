import { fetchAdmin } from '../lib/adminFetch';
import type {
  ResponseDetail,
  ResponseSummary,
  Survey,
  SurveyCreateParams,
  SurveySummary,
  SurveyUpdateParams,
} from './types';

const API_BASE = '/api/manage/surveys';

export const adminSurveyApi = {
  async list(): Promise<Survey[]> {
    return fetchAdmin<Survey[]>(API_BASE, {}, 'アンケート一覧の取得に失敗しました');
  },

  async get(id: number): Promise<Survey> {
    return fetchAdmin<Survey>(`${API_BASE}/${id}`, {}, `アンケート(ID:${id})の取得に失敗しました`);
  },

  async create(params: SurveyCreateParams): Promise<{ id: number }> {
    return fetchAdmin<{ id: number }>(
      API_BASE,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(params),
      },
      'アンケートの作成に失敗しました'
    );
  },

  async update(id: number, params: SurveyUpdateParams): Promise<void> {
    await fetchAdmin<void>(
      `${API_BASE}/${id}`,
      {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(params),
      },
      'アンケートの更新に失敗しました'
    );
  },

  async delete(id: number): Promise<void> {
    await fetchAdmin<void>(
      `${API_BASE}/${id}`,
      {
        method: 'DELETE',
      },
      'アンケートの削除に失敗しました'
    );
  },

  async duplicate(id: number): Promise<{ id: number }> {
    return fetchAdmin<{ id: number }>(
      `${API_BASE}/${id}/duplicate`,
      {
        method: 'POST',
      },
      'アンケートの複製に失敗しました'
    );
  },

  async listResponses(surveyId: number): Promise<ResponseSummary[]> {
    return fetchAdmin<ResponseSummary[]>(
      `${API_BASE}/${surveyId}/responses`,
      {},
      '回答一覧の取得に失敗しました'
    );
  },

  async getResponse(surveyId: number, responseId: number): Promise<ResponseDetail> {
    return fetchAdmin<ResponseDetail>(
      `${API_BASE}/${surveyId}/responses/${responseId}`,
      {},
      '回答詳細の取得に失敗しました'
    );
  },

  async updateResponse(
    surveyId: number,
    responseId: number,
    answerJson: Record<string, any>
  ): Promise<void> {
    await fetchAdmin<void>(
      `${API_BASE}/${surveyId}/responses/${responseId}`,
      {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ answer_json: answerJson }),
      },
      '回答の更新に失敗しました'
    );
  },

  async deleteResponse(surveyId: number, responseId: number): Promise<void> {
    await fetchAdmin<void>(
      `${API_BASE}/${surveyId}/responses/${responseId}`,
      {
        method: 'DELETE',
      },
      '回答の削除に失敗しました'
    );
  },

  async getSummary(surveyId: number): Promise<SurveySummary> {
    return fetchAdmin<SurveySummary>(
      `${API_BASE}/${surveyId}/summary`,
      {},
      '集計結果の取得に失敗しました'
    );
  },

  getCsvUrl(surveyId: number): string {
    return `${API_BASE}/${surveyId}/responses.csv`;
  },
};
