import { api } from '../../../shared/services/api';

let authCache: any = null;

export async function login(payload: Record<string, any>) {
  const response = await api.post('/auth/login', payload);
  authCache = response?.data?.data?.user || null;
  return response.data;
}

export async function me(force: boolean = false) {
  if (!force && authCache) {
    return {
      success: true,
      data: { user: authCache },
      message: ''
    };
  }
  const response = await api.get('/auth/me');
  authCache = response?.data?.data?.user || null;
  return response.data;
}

export async function logout() {
  const response = await api.post('/auth/logout');
  authCache = null;
  return response.data;
}

export async function changePassword(payload: Record<string, any>) {
  const response = await api.post('/auth/change-password', payload);
  return response.data;
}

export function clearAuthCache() {
  authCache = null;
}
