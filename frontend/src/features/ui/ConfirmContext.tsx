import React, { createContext, useContext, useState, useCallback, useRef, useEffect } from 'react';
import './ConfirmContext.css';

interface ConfirmOptions {
  title?: string;
  message: string;
  confirmLabel?: string;
  danger?: boolean;
}

interface ConfirmContextType {
  confirm: (options: ConfirmOptions) => Promise<boolean>;
}

const ConfirmContext = createContext<ConfirmContextType | undefined>(undefined);

export const ConfirmProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [state, setState] = useState<{
    isOpen: boolean;
    title?: string;
    message: string;
    confirmLabel?: string;
    danger?: boolean;
  }>({
    isOpen: false,
    message: '',
  });

  const resolveRef = useRef<((value: boolean) => void) | null>(null);
  const cancelRef = useRef<HTMLButtonElement>(null);

  const confirm = useCallback((options: ConfirmOptions) => {
    // 前回の確認が残っている場合はキャンセル扱いで解決させる
    if (resolveRef.current) {
      resolveRef.current(false);
    }

    setState({
      isOpen: true,
      ...options,
    });
    return new Promise<boolean>((resolve) => {
      resolveRef.current = resolve;
    });
  }, []);

  const handleConfirm = useCallback(() => {
    const resolve = resolveRef.current;
    resolveRef.current = null;
    setState((prev) => ({ ...prev, isOpen: false }));
    resolve?.(true);
  }, []);

  const handleCancel = useCallback(() => {
    const resolve = resolveRef.current;
    resolveRef.current = null;
    setState((prev) => ({ ...prev, isOpen: false }));
    resolve?.(false);
  }, []);

  useEffect(() => {
    if (state.isOpen) {
      // 誤操作を防ぐため、キャンセルボタンに初期フォーカスを当てる
      const timer = setTimeout(() => {
        cancelRef.current?.focus();
      }, 0);

      const handleEscape = (e: KeyboardEvent) => {
        if (e.key === 'Escape') {
          handleCancel();
        }
      };
      window.addEventListener('keydown', handleEscape);
      return () => {
        window.removeEventListener('keydown', handleEscape);
        clearTimeout(timer);
      };
    }
  }, [state.isOpen, handleCancel]);

  return (
    <ConfirmContext.Provider value={{ confirm }}>
      {children}
      {state.isOpen && (
        <div className="confirm-backdrop" onClick={handleCancel}>
          <div
            className="confirm-modal"
            onClick={(e) => e.stopPropagation()}
            role="dialog"
            aria-modal="true"
            aria-labelledby={state.title ? "confirm-title" : undefined}
            aria-label={!state.title ? "確認" : undefined}
          >
            {state.title && (
              <div className="confirm-header">
                <h3 id="confirm-title">{state.title}</h3>
              </div>
            )}
            <div className="confirm-body">
              <p className="confirm-message">{state.message}</p>
            </div>
            <div className="confirm-footer">
              <button
                type="button"
                className="admin-button admin-button-outline"
                onClick={handleCancel}
                ref={cancelRef}
              >
                キャンセル
              </button>
              <button
                type="button"
                className={`admin-button ${state.danger ? 'admin-button-danger' : 'admin-button-primary'}`}
                onClick={handleConfirm}
              >
                {state.confirmLabel || 'OK'}
              </button>
            </div>
          </div>
        </div>
      )}
    </ConfirmContext.Provider>
  );
};

export const useConfirm = () => {
  const context = useContext(ConfirmContext);
  if (!context) {
    throw new Error('useConfirm must be used within a ConfirmProvider');
  }
  return context.confirm;
};
