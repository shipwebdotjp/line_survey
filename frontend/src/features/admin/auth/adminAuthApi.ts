import { fetchAdmin } from '../lib/adminFetch';

export interface AdminUser {
  id: number;
  line_user_id: string;
  line_display_name: string | null;
  line_picture_url: string | null;
  email: string | null;
  role: string;
  created_at: string;
  updated_at: string;
}

const API_BASE = '/api/manage';

async function fetchJson<T>(
  url: string,
  options?: RequestInit,
  errorMessage = 'Failed to fetch'
): Promise<T> {
  const response = await fetch(url, {
    ...options,
    credentials: 'include',
  });

  if (!response.ok) {
    let errorDetail = '';
    try {
      const errorData = await response.json();
      errorDetail = errorData.error || errorData.message || '';
    } catch {
      // Ignore
    }
    throw new Error(errorDetail ? `${errorMessage}: ${errorDetail}` : errorMessage);
  }

  const result = await response.json();
  return result.data;
}

export const adminAuthApi = {
  async login(idToken: string): Promise<{ user: AdminUser }> {
    return fetchJson<{ user: AdminUser }>(
      `${API_BASE}/login`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_token: idToken }),
      },
      'ログインに失敗しました'
    );
  },

  async logout(): Promise<void> {
    await fetchJson<void>(
      `${API_BASE}/logout`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      },
      'ログアウトに失敗しました'
    );
  },

  async getCurrentUser(): Promise<AdminUser | null> {
    try {
      const data = await fetchAdmin<{ user: AdminUser }>(`${API_BASE}/me`);
      return data.user;
    } catch {
      return null;
    }
  },

  async updateMe(email: string): Promise<AdminUser> {
    const data = await fetchAdmin<{ user: AdminUser }>(`${API_BASE}/me`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email }),
    });
    return data.user;
  },
};
