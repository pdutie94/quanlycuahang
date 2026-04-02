import { api } from '../../../shared/services/api';

export async function fetchPurchases(params = {}) {
  const response = await api.get('/purchases', { params });
  return response.data;
}

export async function fetchPurchaseDetail(id) {
  const response = await api.get(`/purchases/${id}`);
  return response.data;
}

export async function fetchPurchaseFormData() {
  const response = await api.get('/purchases/bootstrap/form-data');
  return response.data;
}

export async function createPurchase(payload) {
  const response = await api.post('/purchases', payload);
  return response.data;
}

export async function updatePurchase(id, payload) {
  const response = await api.patch(`/purchases/${id}`, payload);
  return response.data;
}

export async function recordPurchasePayment(id, payload) {
  const response = await api.post(`/purchases/${id}/payment`, payload);
  return response.data;
}