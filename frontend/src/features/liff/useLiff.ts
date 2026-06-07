import { useState, useEffect } from 'react';
import liff from '@line/liff';

export interface UseLiffReturn {
  liff: typeof liff;
  isInitialized: boolean;
  isLoggedIn: boolean;
  idToken: string | null;
  error: Error | null;
}

export const useLiff = (): UseLiffReturn => {
  const [isInitialized, setIsInitialized] = useState(false);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [idToken, setIdToken] = useState<string | null>(null);
  const [error, setError] = useState<Error | null>(null);

  useEffect(() => {
    const init = async () => {
      const liffId = import.meta.env.VITE_LIFF_ID;

      if (!liffId) {
        setError(new Error('LIFF ID is not configured. Please set VITE_LIFF_ID environment variable.'));
        setIsInitialized(true);
        return;
      }

      try {
        await liff.init({ liffId });
        if (!liff.isInClient()) {
          setError(new Error('Outside LIFF'));
          setIsInitialized(true);
          return;
        }

        if (!liff.isLoggedIn()) {
          liff.login();
          return; // Redirecting...
        }

        setIsLoggedIn(true);
        setIdToken(liff.getIDToken());
        setIsInitialized(true);
      } catch (err) {
        setError(err instanceof Error ? err : new Error(String(err)));
        setIsInitialized(true);
      }
    };

    init();
  }, []);

  return {
    liff,
    isInitialized,
    isLoggedIn,
    idToken,
    error,
  };
};
