import { api } from '../../../shared/services/api';

export async function fetchUnits(params: Record<string, any> = {}) {
  const response = await api.get('/units', { params });
  return response.data;
}

export async function createUnit(payload: Record<string, any>) {
  const response = await api.post('/units', payload);
  return response.data;
}

export async function updateUnit(id: number | string, payload: Record<string, any>) {
  const response = await api.patch(`/units/${id}`, payload);
  return response.data;
}

export async function deleteUnit(id: number | string) {
  const response = await api.delete(`/units/${id}`);
  return response.data;
}
