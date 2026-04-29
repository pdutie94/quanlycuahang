import { api } from '../../../shared/services/api';

export async function fetchCustomers(params: Record<string, any> = {}) {
  const response = await api.get('/customers', { params });
  return response.data;
}

export async function fetchCustomerDetail(id: number | string) {
  const response = await api.get(`/customers/${id}`);
  return response.data;
}

export async function fetchCustomerDebtPaymentInfo(id: number | string) {
  const response = await api.get(`/customers/${id}/payment`);
  return response.data;
}

export async function createCustomer(payload: Record<string, any>) {
  const response = await api.post('/customers', payload);
  return response.data;
}

export async function updateCustomer(id: number | string, payload: Record<string, any>) {
  const response = await api.patch(`/customers/${id}`, payload);
  return response.data;
}

export async function deleteCustomer(id: number | string) {
  const response = await api.delete(`/customers/${id}`);
  return response.data;
}

export async function fetchCustomerPaymentInfo(orderId: number | string) {
  const response = await api.get(`/customers/orders/${orderId}/payment`);
  return response.data;
}

export async function submitCustomerPayment(orderId: number | string, payload: Record<string, any>) {
  const response = await api.post(`/customers/orders/${orderId}/payment`, payload);
  return response.data;
}

export async function submitCustomerDebtPayment(id: number | string, payload: Record<string, any>) {
  const response = await api.post(`/customers/${id}/payment`, payload);
  return response.data;
}

export async function fetchCustomerPayments(id: number | string) {
  const response = await api.get(`/customers/${id}/payments`);
  return response.data;
}
