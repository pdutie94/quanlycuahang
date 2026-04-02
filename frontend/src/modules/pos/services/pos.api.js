import { api } from '../../../shared/services/api';

export async function fetchPosBootstrap() {
  const response = await api.get('/pos/bootstrap');
  return response.data;
}

export async function createOrder(payload) {
  const response = await api.post('/orders', payload);
  return response.data;
}