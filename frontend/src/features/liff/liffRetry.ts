/**
 * Helper to handle 401 Expired token responses from LIFF-authenticated endpoints.
 * Returns true if a re-authentication was triggered, false otherwise.
 */
export const handleExpiredToken = async (
  response: Response,
  reauthenticate: () => void
): Promise<boolean> => {
  if (response.status !== 401) {
    return false;
  }

  try {
    // Clone response to avoid consuming the body if the caller needs it
    const data = await response.clone().json();

    if (data.message && data.message.includes('Expired token')) {
      const retryKey = `liff_retry:${window.location.pathname}${window.location.search}`;
      const hasRetried = sessionStorage.getItem(retryKey);

      if (!hasRetried) {
        sessionStorage.setItem(retryKey, 'true');
        reauthenticate();
        return true;
      }
    }
  } catch (err) {
    // If not JSON or other error, don't retry
  }

  return false;
};
