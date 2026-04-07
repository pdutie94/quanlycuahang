import { api } from '../../../shared/services/api';

export async function fetchUnits(params = {}) {
  const response = await api.get('/units', { params });
  return response.data;
}

export async function createUnit(payload) {
  const response = await api.post('/units', payload);
  return response.data;
}

export async function updateUnit(id, payload) {
  const response = await api.patch(`/units/${id}`, payload);
  return response.data;
}

export async function deleteUnit(id) {
  const response = await api.delete(`/units/${id}`);
  return response.data;
}
