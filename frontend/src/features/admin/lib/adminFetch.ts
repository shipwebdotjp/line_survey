export class AdminApiError extends Error {
  status: number;
  data: any;

  constructor(message: string, status: number, data: any) {
    super(message);
    this.name = 'AdminApiError';
    this.status = status;
    this.data = data;
  }
}

export async function fetchAdmin<T>(
  url: string,
  options?: RequestInit,
  errorMessage = 'Failed to fetch'
): Promise<T> {
  let response: Response;
  try {
    response = await fetch(url, {
      ...options,
      credentials: 'include',
    });
  } catch (err) {
    throw new AdminApiError(
      `${errorMessage}: 通信エラーが発生しました。`,
      0,
      { error: String(err) }
    );
  }

  if (response.status === 401) {
    // If we're on the admin side, 401 means session expired or not logged in
    if (window.location.pathname.startsWith('/admin') && window.location.pathname !== '/admin/login') {
      const currentPath = window.location.pathname + window.location.search;
      window.location.href = `/admin/login?from=${encodeURIComponent(currentPath)}`;
      // Return a never-resolving promise to stop further execution in the caller
      return new Promise(() => {});
    }
  }

  if (!response.ok) {
    let errorDetail = '';
    let errorData: any = null;
    const contentType = response.headers.get('content-type');

    if (contentType && contentType.includes('application/json')) {
      try {
        errorData = await response.json();
        errorDetail = errorData.error || errorData.message || '';
      } catch {
        // Ignore
      }
    }

    if (!errorDetail) {
      try {
        errorDetail = await response.text();
      } catch {
        // Ignore
      }
    }

    throw new AdminApiError(
      errorDetail ? `${errorMessage}: ${errorDetail}` : errorMessage,
      response.status,
      errorData
    );
  }

  // For 204 No Content or similar, just return as cast T
  if (response.status === 204) {
    return {} as T;
  }

  const result = await response.json();
  return result.data;
}
