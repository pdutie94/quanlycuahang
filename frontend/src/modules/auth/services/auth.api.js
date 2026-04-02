import { api } from '../../../shared/services/api';

let authCache = null;

export async function login(payload) {
  const response = await api.post('/auth/login', payload);
  authCache = response?.data?.data?.user || null;
  return response.data;
}

export async function me(force = false) {
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

export async function changePassword(payload) {
  const response = await api.post('/auth/change-password', payload);
  return response.data;
}

export function clearAuthCache() {
  authCache = null;
}
