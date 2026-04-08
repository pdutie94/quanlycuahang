import { api } from '../../../shared/services/api';

export async function fetchCostUpdateList() {
  const response = await api.get('/reports/cost-update');
  return response.data;
}

export async function submitCostUpdate(payload) {
  const response = await api.post('/reports/cost-update', payload);
  return response.data;
}
