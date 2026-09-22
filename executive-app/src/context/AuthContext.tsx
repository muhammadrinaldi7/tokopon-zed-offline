import React, { createContext, useContext, useState, useEffect } from 'react';
import type { UserProfile } from '../types';
import { executiveApi } from '../api/executive';

interface AuthContextType {
  token: string | null;
  user: UserProfile | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [token, setToken] = useState<string | null>(() => localStorage.getItem('executive_token'));
  const [user, setUser] = useState<UserProfile | null>(() => {
    const saved = localStorage.getItem('executive_user');
    return saved ? JSON.parse(saved) : null;
  });
  const [isLoading, setIsLoading] = useState<boolean>(true);

  // Validate token with /me on load
  useEffect(() => {
    const initAuth = async () => {
      const storedToken = localStorage.getItem('executive_token');
      if (storedToken) {
        try {
          const res = await executiveApi.getMe();
          if (res.success && res.data) {
            setUser(res.data);
            localStorage.setItem('executive_user', JSON.stringify(res.data));
          }
        } catch {
          // Token invalid or expired
          setToken(null);
          setUser(null);
          localStorage.removeItem('executive_token');
          localStorage.removeItem('executive_user');
        }
      }
      setIsLoading(false);
    };

    initAuth();

    // Listen for 401 unauthorized events
    const handleUnauthorized = () => {
      setToken(null);
      setUser(null);
    };

    window.addEventListener('executive:unauthorized', handleUnauthorized);
    return () => window.removeEventListener('executive:unauthorized', handleUnauthorized);
  }, []);

  const login = async (email: string, password: string) => {
    const res = await executiveApi.login({
      email,
      password,
      device_name: 'Executive Web Suite',
    });

    if (res.success && res.data) {
      const { token: newToken, user: newUser } = res.data;
      setToken(newToken);
      setUser(newUser);
      localStorage.setItem('executive_token', newToken);
      localStorage.setItem('executive_user', JSON.stringify(newUser));
    }
  };

  const logout = async () => {
    try {
      await executiveApi.logout();
    } catch {
      // Ignore network errors during logout
    } finally {
      setToken(null);
      setUser(null);
      localStorage.removeItem('executive_token');
      localStorage.removeItem('executive_user');
    }
  };

  return (
    <AuthContext.Provider
      value={{
        token,
        user,
        isAuthenticated: !!token,
        isLoading,
        login,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
