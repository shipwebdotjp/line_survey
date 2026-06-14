import type { ResponseDraftResponse, Respondent } from '../features/survey/types';

export interface ApiError extends Error {
  code?: string;
}

export const fetchWithSession = async (
  input: RequestInfo | URL,
  init: RequestInit = {},
  options: {
    onSessionRequired?: () => Promise<boolean>;
  } = {}
): Promise<Response> => {
  const { onSessionRequired } = options;

  const headers = new Headers(init.headers || {});

  // Set Content-Type: application/json for unsafe methods if not set
  const method = (init.method || 'GET').toUpperCase();
  if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }

  const response = await fetch(input, {
    ...init,
    headers,
    credentials: 'include',
  });

  if (response.status === 401) {
    const clonedResponse = response.clone();
    try {
      const data = await clonedResponse.json();
      if (data.code === 'SESSION_REQUIRED' && onSessionRequired) {
        const retried = await onSessionRequired();
        if (retried) {
          // Retry the original request
          return fetch(input, {
            ...init,
            headers,
            credentials: 'include',
          });
        }
      }
    } catch (e) {
      // Ignore JSON parse error and return original response
    }
  }

  return response;
};

export const getRespondentProfile = async (onSessionRequired?: () => Promise<boolean>): Promise<Respondent> => {
  const response = await fetchWithSession('/api/respondent', {}, { onSessionRequired });
  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.error || 'Failed to fetch respondent profile');
  }
  return result.data;
};

export const getResponseDraft = async (
  publicId: string,
  onSessionRequired?: () => Promise<boolean>
): Promise<ResponseDraftResponse> => {
  const response = await fetchWithSession(`/api/surveys/public/${publicId}/response-draft`, {}, { onSessionRequired });
  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.error || 'Failed to fetch response draft');
  }
  return result.data;
};

export const saveResponseDraft = async (
  publicId: string,
  answerJson: Record<string, any>,
  onSessionRequired?: () => Promise<boolean>
): Promise<ResponseDraftResponse> => {
  const response = await fetchWithSession(`/api/surveys/public/${publicId}/response-draft`, {
    method: 'PUT',
    body: JSON.stringify({ answer_json: answerJson }),
  }, { onSessionRequired });
  const result = await response.json();
  if (!response.ok) {
    throw new Error(result.error || 'Failed to save response draft');
  }
  return result.data;
};

export const deleteResponseDraft = async (
  publicId: string,
  onSessionRequired?: () => Promise<boolean>
): Promise<void> => {
  const response = await fetchWithSession(`/api/surveys/public/${publicId}/response-draft`, {
    method: 'DELETE',
  }, { onSessionRequired });
  if (!response.ok) {
    const result = await response.json();
    throw new Error(result.error || 'Failed to delete response draft');
  }
};

export const updateRespondentProfile = async (
  data: { name: string; email: string },
  onSessionRequired?: () => Promise<boolean>
): Promise<Respondent> => {
  const response = await fetchWithSession('/api/respondent', {
    method: 'PUT',
    body: JSON.stringify(data),
  }, { onSessionRequired });
  const result = await response.json();
  if (!response.ok) {
    const error = new Error(result.error || 'Failed to update respondent profile') as ApiError;
    error.code = result.code;
    (error as any).details = result.details;
    throw error;
  }
  return result.data;
};
