import type { Respondent } from '../features/survey/types';

export interface ApiError extends Error {
  code?: string;
   details?: Record<string, string>;
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
    error.details = result.details;
    throw error;
  }
  return result.data;
};
