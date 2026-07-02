import React, { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import type { AdminUser } from './adminAuthApi';
import { adminAuthApi } from './adminAuthApi';

interface AdminAuthContextType {
  user: AdminUser | null;
  isLoading: boolean;
  login: (idToken: string) => Promise<void>;
  logout: () => Promise<void>;
  updateUser: (updatedUser: AdminUser) => void;
}

const AdminAuthContext = createContext<AdminAuthContextType | null>(null);

export const AdminAuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const versionRef = useRef(0);

  const checkAuth = useCallback(async () => {
    const currentVersion = ++versionRef.current;
    try {
      const user = await adminAuthApi.getCurrentUser();
      if (currentVersion === versionRef.current) {
        setUser(user);
      }
    } catch (error) {
      // Ignore
    } finally {
      if (currentVersion === versionRef.current) {
        setIsLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  const login = async (idToken: string) => {
    const currentVersion = ++versionRef.current;
    const data = await adminAuthApi.login(idToken);
    if (currentVersion === versionRef.current) {
      setUser(data.user);
    }
  };

  const logout = async () => {
    try {
      await adminAuthApi.logout();
    } catch (err) {
      console.error('Logout API failed', err);
    } finally {
      versionRef.current++;
      setUser(null);
      window.location.href = '/admin/login';
    }
  };

  const updateUser = useCallback((updatedUser: AdminUser) => {
    setUser(updatedUser);
  }, []);

  return (
    <AdminAuthContext.Provider value={{ user, isLoading, login, logout, updateUser }}>
      {children}
    </AdminAuthContext.Provider>
  );
};

export const useAdminAuth = () => {
  const context = useContext(AdminAuthContext);
  if (!context) {
    throw new Error('useAdminAuth must be used within an AdminAuthProvider');
  }
  return context;
};
