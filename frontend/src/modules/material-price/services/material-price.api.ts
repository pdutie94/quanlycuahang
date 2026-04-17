import { api } from '../../../shared/services/api';

export async function fetchMaterialPrices(params: Record<string, any> = {}) {
  const response = await api.get('/material-prices', { params });
  return response.data;
}

export async function createMaterialPrice(payload: Record<string, any>) {
  const response = await api.post('/material-prices', payload);
  return response.data;
}

export async function updateMaterialPrice(id: number | string, payload: Record<string, any>) {
  const response = await api.patch(`/material-prices/${id}`, payload);
  return response.data;
}

export async function deleteMaterialPrice(id: number | string) {
  const response = await api.delete(`/material-prices/${id}`);
  return response.data;
}
