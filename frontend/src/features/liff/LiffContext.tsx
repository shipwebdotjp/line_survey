import React, { createContext, useContext } from 'react';
import type { ReactNode } from 'react';
import { useLiff } from './useLiff';
import type { UseLiffReturn } from './useLiff';

const LiffContext = createContext<UseLiffReturn | null>(null);

interface LiffProviderProps {
  children: ReactNode;
  enabled: boolean;
}

export const LiffProvider: React.FC<LiffProviderProps> = ({ children, enabled }) => {
  const liffState = useLiff({ enabled });

  return (
    <LiffContext.Provider value={liffState}>
      {children}
    </LiffContext.Provider>
  );
};

export const useLiffContext = (): UseLiffReturn => {
  const context = useContext(LiffContext);
  if (!context) {
    throw new Error('useLiffContext must be used within a LiffProvider');
  }
  return context;
};
