import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import type { AdminUser } from './adminAuthApi';
import { adminAuthApi } from './adminAuthApi';

interface AdminAuthContextType {
  user: AdminUser | null;
  isLoading: boolean;
  login: (idToken: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AdminAuthContext = createContext<AdminAuthContextType | null>(null);

export const AdminAuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const checkAuth = useCallback(async () => {
    try {
      const user = await adminAuthApi.getCurrentUser();
      setUser(user);
      setIsLoading(false);
    } catch (error) {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  const login = async (idToken: string) => {
    const data = await adminAuthApi.login(idToken);
    setUser(data.user);
  };

  const logout = async () => {
    await adminAuthApi.logout();
    setUser(null);
    window.location.href = '/admin/login';
  };

  return (
    <AdminAuthContext.Provider value={{ user, isLoading, login, logout }}>
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
