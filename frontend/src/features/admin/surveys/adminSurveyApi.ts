import type {
  ResponseDetail,
  ResponseSummary,
  Survey,
  SurveyCreateParams,
  SurveySummary,
  SurveyUpdateParams,
} from './types';

const API_BASE = '/api/admin/surveys';

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

export const adminSurveyApi = {
  async list(): Promise<Survey[]> {
    return fetchJson<Survey[]>(API_BASE, {}, 'アンケート一覧の取得に失敗しました');
  },

  async get(id: number): Promise<Survey> {
    return fetchJson<Survey>(`${API_BASE}/${id}`, {}, `アンケート(ID:${id})の取得に失敗しました`);
  },

  async create(params: SurveyCreateParams): Promise<{ id: number }> {
    return fetchJson<{ id: number }>(
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
    await fetchJson<void>(
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
    await fetchJson<void>(
      `${API_BASE}/${id}`,
      {
        method: 'DELETE',
      },
      'アンケートの削除に失敗しました'
    );
  },

  async duplicate(id: number): Promise<{ id: number }> {
    return fetchJson<{ id: number }>(
      `${API_BASE}/${id}/duplicate`,
      {
        method: 'POST',
      },
      'アンケートの複製に失敗しました'
    );
  },

  async listResponses(surveyId: number): Promise<ResponseSummary[]> {
    return fetchJson<ResponseSummary[]>(
      `${API_BASE}/${surveyId}/responses`,
      {},
      '回答一覧の取得に失敗しました'
    );
  },

  async getResponse(surveyId: number, responseId: number): Promise<ResponseDetail> {
    return fetchJson<ResponseDetail>(
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
    await fetchJson<void>(
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
    await fetchJson<void>(
      `${API_BASE}/${surveyId}/responses/${responseId}`,
      {
        method: 'DELETE',
      },
      '回答の削除に失敗しました'
    );
  },

  async getSummary(surveyId: number): Promise<SurveySummary> {
    return fetchJson<SurveySummary>(
      `${API_BASE}/${surveyId}/summary`,
      {},
      '集計結果の取得に失敗しました'
    );
  },

  getCsvUrl(surveyId: number): string {
    return `${API_BASE}/${surveyId}/responses.csv`;
  },
};
