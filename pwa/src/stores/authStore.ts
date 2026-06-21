import { create } from 'zustand';
import type { User } from '../types';
import { api, setAuthToken } from '../services/api';

interface AuthState {
  user: User | null;
  token: string | null;
  loading: boolean;
  login: (email: string, password: string, deviceName?: string) => Promise<void>;
  logout: () => Promise<void>;
  loadFromStorage: () => Promise<void>;
  setUser: (u: User) => void;
}

export const useAuth = create<AuthState>((set) => ({
  user: JSON.parse(localStorage.getItem('user') || 'null'),
  token: localStorage.getItem('auth_token'),
  loading: false,

  login: async (email, password, deviceName) => {
    set({ loading: true });
    try {
      const res = await api.post('/auth/login', { email, password, device_name: deviceName });
      const { token, user } = res.data;
      setAuthToken(token);
      localStorage.setItem('user', JSON.stringify(user));
      set({ token, user, loading: false });
    } catch (e) {
      set({ loading: false });
      throw e;
    }
  },

  logout: async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // ignore
    }
    setAuthToken(null);
    localStorage.removeItem('user');
    set({ token: null, user: null });
  },

  loadFromStorage: async () => {
    const token = localStorage.getItem('auth_token');
    const userStr = localStorage.getItem('user');
    if (token && userStr) {
      set({ token, user: JSON.parse(userStr) });
      try {
        const res = await api.get('/auth/me');
        set({ user: res.data.user });
        localStorage.setItem('user', JSON.stringify(res.data.user));
      } catch {
        setAuthToken(null);
        localStorage.removeItem('user');
        set({ token: null, user: null });
      }
    }
  },

  setUser: (u) => {
    localStorage.setItem('user', JSON.stringify(u));
    set({ user: u });
  },
}));
