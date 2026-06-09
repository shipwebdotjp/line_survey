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
