const LIFF_BASE_URL = 'https://liff.line.me';

const normalizePath = (path: string): string => path.replace(/^\/+/, '');

export const getLiffBaseUrl = (): string | null => {
  const liffId = import.meta.env.VITE_LIFF_ID;

  if (!liffId) {
    return null;
  }

  return `${LIFF_BASE_URL}/${liffId}`;
};

export const createLiffUrl = (path = ''): string => {
  const baseUrl = getLiffBaseUrl();
  const normalizedPath = normalizePath(path);

  if (!baseUrl) {
    return normalizedPath ? `/${normalizedPath}` : '/';
  }

  if (!normalizedPath) {
    return baseUrl;
  }

  return new URL(normalizedPath, `${baseUrl}/`).toString();
};
