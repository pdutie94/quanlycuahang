import { api } from '../../../shared/services/api';

export async function fetchCustomers(params = {}) {
  const response = await api.get('/customers', { params });
  return response.data;
}

export async function fetchCustomerDetail(id) {
  const response = await api.get(`/customers/${id}`);
  return response.data;
}

export async function createCustomer(payload) {
  const response = await api.post('/customers', payload);
  return response.data;
}

export async function updateCustomer(id, payload) {
  const response = await api.patch(`/customers/${id}`, payload);
  return response.data;
}

export async function deleteCustomer(id) {
  const response = await api.delete(`/customers/${id}`);
  return response.data;
}

export async function fetchCustomerPaymentInfo(orderId) {
  const response = await api.get(`/customers/orders/${orderId}/payment`);
  return response.data;
}

export async function submitCustomerPayment(orderId, payload) {
  const response = await api.post(`/customers/orders/${orderId}/payment`, payload);
  return response.data;
}