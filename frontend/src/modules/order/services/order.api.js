import { api } from '../../../shared/services/api';

export async function fetchOrders(params = {}) {
  const response = await api.get('/orders', { params });
  return response.data;
}

export async function fetchOrderDetail(id) {
  const response = await api.get(`/orders/${id}`);
  return response.data;
}

export async function fetchOrderPreview(id) {
  const response = await api.get(`/orders/${id}/preview`);
  return response.data;
}

export async function fetchOrderInvoice(id) {
  const response = await api.get(`/orders/${id}/invoice`);
  return response.data;
}

export async function fetchOrderReturnInfo(id) {
  const response = await api.get(`/orders/${id}/return`);
  return response.data;
}

export async function submitOrderReturn(id, payload) {
  const response = await api.post(`/orders/${id}/return`, payload);
  return response.data;
}

export async function fetchOrderFormBootstrap() {
  const response = await api.get('/pos/bootstrap');
  return response.data;
}

export async function createOrder(payload) {
  const response = await api.post('/orders', payload);
  return response.data;
}

export async function updateOrder(id, payload) {
  const response = await api.put(`/orders/${id}`, payload);
  return response.data;
}

export async function deleteOrder(id) {
  const response = await api.delete(`/orders/${id}`);
  return response.data;
}

export async function recordOrderPayment(id, payload) {
  const response = await api.post(`/orders/${id}/payment`, payload);
  return response.data;
}

export async function resetOrderPayment(id) {
  const response = await api.post(`/orders/${id}/payment/reset`);
  return response.data;
}