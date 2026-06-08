import type { Survey, SurveyCreateParams, SurveyUpdateParams } from './types';

const API_BASE = '/api/admin/surveys';

export const adminSurveyApi = {
  async list(): Promise<Survey[]> {
    const response = await fetch(API_BASE);
    if (!response.ok) {
      throw new Error('Failed to fetch surveys');
    }
    const result = await response.json();
    return result.data;
  },

  async get(id: number): Promise<Survey> {
    const response = await fetch(`${API_BASE}/${id}`);
    if (!response.ok) {
      throw new Error(`Failed to fetch survey ${id}`);
    }
    const result = await response.json();
    return result.data;
  },

  async create(params: SurveyCreateParams): Promise<{ id: number }> {
    const response = await fetch(API_BASE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(params),
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Failed to create survey');
    }
    const result = await response.json();
    return result.data;
  },

  async update(id: number, params: SurveyUpdateParams): Promise<void> {
    const response = await fetch(`${API_BASE}/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(params),
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Failed to update survey');
    }
  },

  async delete(id: number): Promise<void> {
    const response = await fetch(`${API_BASE}/${id}`, {
      method: 'DELETE',
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Failed to delete survey');
    }
  },

  async duplicate(id: number): Promise<{ id: number }> {
    const response = await fetch(`${API_BASE}/${id}/duplicate`, {
      method: 'POST',
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Failed to duplicate survey');
    }
    const result = await response.json();
    return result.data;
  },
};
