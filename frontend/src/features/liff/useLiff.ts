import { useState, useEffect, useRef } from 'react';
import liff from '@line/liff';

export interface UseLiffReturn {
  liff: typeof liff;
  isInitialized: boolean;
  isLoggedIn: boolean;
  idToken: string | null;
  error: Error | null;
  reauthenticate: () => void;
}

export interface UseLiffOptions {
  enabled?: boolean;
}

export const useLiff = (options: UseLiffOptions = {}): UseLiffReturn => {
  const { enabled = true } = options;
  const [isInitialized, setIsInitialized] = useState(false);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [idToken, setIdToken] = useState<string | null>(null);
  const [error, setError] = useState<Error | null>(null);

  // Use a ref to track the current run ID to prevent race conditions
  const runIdRef = useRef(0);

  useEffect(() => {
    const currentRunId = ++runIdRef.current;

    // Reset state when enabled changes or on re-run
    setIsInitialized(false);
    setIsLoggedIn(false);
    setIdToken(null);
    setError(null);

    if (!enabled) {
      setIsInitialized(true);
      return;
    }

    const init = async () => {
      const liffId = import.meta.env.VITE_LIFF_ID;

      if (!liffId) {
        if (runIdRef.current === currentRunId) {
          setError(new Error('LIFF ID is not configured. Please set VITE_LIFF_ID environment variable.'));
          setIsInitialized(true);
        }
        return;
      }

      try {
        await liff.init({
          liffId,
          withLoginOnExternalBrowser: true
        });

        if (runIdRef.current !== currentRunId) return;

        // 外部ブラウザで、ログインしていない場合はログインさせる
        if (!liff.isLoggedIn() && !liff.isInClient()) {
          liff.login({ redirectUri: window.location.href });
          return;
        }
        const loggedIn = liff.isLoggedIn();
        setIsLoggedIn(loggedIn);
        if (loggedIn) {
          setIdToken(liff.getIDToken());
        }
        setIsInitialized(true);
      } catch (err) {
        if (runIdRef.current !== currentRunId) return;
        setError(err instanceof Error ? err : new Error(String(err)));
        setIsInitialized(true);
      }
    };

    init();

    return () => {
      // Invalidate current run on cleanup
      runIdRef.current++;
    };
  }, [enabled]);

  const reauthenticate = () => {
    liff.login({ redirectUri: window.location.href });
  };

  return {
    liff,
    isInitialized,
    isLoggedIn,
    idToken,
    error,
    reauthenticate,
  };
};
