import { useState, useEffect, useRef } from 'react';
import liff from '@line/liff';

export interface UseLiffReturn {
  liff: typeof liff;
  isInitialized: boolean;
  isLoggedIn: boolean;
  idToken: string | null;
  error: Error | null;
  identify: (publicId: string) => Promise<boolean>;
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

  // liff関連のlocalStorageのキーのリストを取得
  const getLiffLocalStorageKeys = (prefix: string) => {
      const keys: string[] = []
      for (var i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i)
        if (key && key.indexOf(prefix) === 0) {
          keys.push(key)
        }
      }
      return keys
  }
  // 期限切れのIDTokenをクリアする
  const clearExpiredIdToken = (liffId: string) => {
    const keyPrefix = `LIFF_STORE:${liffId}:`
    const key = keyPrefix + 'decodedIDToken'
    const decodedIDTokenString = localStorage.getItem(key)
    if (!decodedIDTokenString) {
      return
    }
    const decodedIDToken = JSON.parse(decodedIDTokenString)
    // 有効期限をチェック
    if (new Date().getTime() > decodedIDToken.exp * 1000) {
        const keys = getLiffLocalStorageKeys(keyPrefix)
        keys.forEach(function(key) {
          localStorage.removeItem(key)
        })
    }
  }

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
        clearExpiredIdToken(liffId);
        await liff.init({
          liffId,
          // withLoginOnExternalBrowser: true
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

  /**
   * Establishes a server-side session using the current LIFF ID token.
   * This should be called after LIFF initialization and login, or when a session expires.
   */
  const identify = async (publicId: string): Promise<boolean> => {
    if (!publicId) {
      console.warn('Cannot identify: publicId is required.');
      return false;
    }

    const token = liff.getIDToken();
    if (!token) {
      console.warn('Cannot identify: No ID token available.');
      return false;
    }

    try {
      const response = await fetch('/api/liff/identify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id_token: token,
          public_id: publicId,
        }),
        credentials: 'include',
      });
      return response.ok;
    } catch (err) {
      console.error('Identification failed', err);
      return false;
    }
  };

  return {
    liff,
    isInitialized,
    isLoggedIn,
    idToken,
    error,
    identify,
  };
};
