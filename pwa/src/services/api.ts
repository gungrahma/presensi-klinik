import axios, { AxiosError } from 'axios';
import type { AxiosInstance } from 'axios';
import Dexie from 'dexie';
import type { Table } from 'dexie';
import type { PendingSubmission } from '../types';

const API_URL = import.meta.env.VITE_API_URL || '/api';

export const api: AxiosInstance = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (res) => res,
  (err: AxiosError) => {
    if (err.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      if (location.pathname !== '/login') {
        location.href = '/login';
      }
    }
    return Promise.reject(err);
  },
);

export function setAuthToken(token: string | null) {
  if (token) {
    localStorage.setItem('auth_token', token);
  } else {
    localStorage.removeItem('auth_token');
  }
}

class PendingDB extends Dexie {
  submissions!: Table<PendingSubmission, number>;

  constructor() {
    super('AbsensiKlinikDB');
    this.version(1).stores({
      submissions: '++id,type,created_at',
    });
  }
}

export const pendingDB = new PendingDB();

export async function queueSubmission(sub: Omit<PendingSubmission, 'id' | 'created_at'>) {
  return pendingDB.submissions.add({
    ...sub,
    created_at: Date.now(),
  });
}

export async function syncQueue(): Promise<{ synced: number; failed: number }> {
  const items = await pendingDB.submissions.orderBy('created_at').toArray();
  let synced = 0;
  let failed = 0;

  for (const item of items) {
    try {
      if (item.type === 'clock_in' || item.type === 'clock_out') {
        const form = new FormData();
        const p = item.payload as { photoBlob: Blob; lat: number; lng: number; accuracy?: number };
        form.append('photo', p.photoBlob);
        form.append('lat', String(p.lat));
        form.append('lng', String(p.lng));
        if (p.accuracy) form.append('accuracy', String(p.accuracy));
        const endpoint = item.type === 'clock_in' ? '/attendance/clock-in' : '/attendance/clock-out';
        await api.post(endpoint, form, { headers: { 'Content-Type': 'multipart/form-data' } });
      } else if (item.type === 'leave') {
        const form = new FormData();
        const p = item.payload as Record<string, string | number | Blob>;
        for (const [k, v] of Object.entries(p)) {
          form.append(k, v as string | Blob);
        }
        await api.post('/leave-requests', form, { headers: { 'Content-Type': 'multipart/form-data' } });
      }
      if (item.id !== undefined) {
        await pendingDB.submissions.delete(item.id);
      }
      synced++;
    } catch (e) {
      failed++;
    }
  }

  return { synced, failed };
}
