import { api } from '../../../shared/services/api';
import type { Order, OrderBootstrapData } from '../types';

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export async function fetchOrders(params: Record<string, any> = {}): Promise<ApiResponse<Order[]>> {
  const response = await api.get('/orders', { params });
  return response.data;
}

export async function fetchDeletedOrders(params: Record<string, any> = {}): Promise<ApiResponse<Order[]>> {
  const response = await api.get('/orders/deleted', { params });
  return response.data;
}

export async function fetchOrderDetail(id: number | string): Promise<ApiResponse<{ order: Order; items: any[]; manual_items: any[] }>> {
  const response = await api.get(`/orders/${id}`);
  return response.data;
}

export async function fetchOrderPreview(id: number | string): Promise<ApiResponse<any>> {
  const response = await api.get(`/orders/${id}/preview`);
  return response.data;
}

export async function fetchOrderInvoice(id: number | string): Promise<ApiResponse<any>> {
  const response = await api.get(`/orders/${id}/invoice`);
  return response.data;
}

export async function fetchOrderReturnInfo(id: number | string): Promise<ApiResponse<any>> {
  const response = await api.get(`/orders/${id}/return`);
  return response.data;
}

export async function submitOrderReturn(id: number | string, payload: Record<string, any>): Promise<ApiResponse<any>> {
  const response = await api.post(`/orders/${id}/return`, payload);
  return response.data;
}

export async function fetchOrderFormBootstrap(): Promise<ApiResponse<OrderBootstrapData>> {
  const response = await api.get('/pos/bootstrap');
  return response.data;
}

export async function createOrder(payload: Record<string, any>): Promise<ApiResponse<Order>> {
  const response = await api.post('/orders', payload);
  return response.data;
}

export async function updateOrder(id: number | string, payload: Record<string, any>): Promise<ApiResponse<Order>> {
  const response = await api.put(`/orders/${id}`, payload);
  return response.data;
}

export async function updateOrderStatus(id: number | string, payload: Record<string, any>): Promise<ApiResponse<any>> {
  const response = await api.post(`/orders/${id}/status`, payload);
  return response.data;
}

export async function deleteOrder(id: number | string): Promise<ApiResponse<any>> {
  const response = await api.delete(`/orders/${id}`);
  return response.data;
}

export async function restoreOrder(id: number | string): Promise<ApiResponse<any>> {
  const response = await api.post(`/orders/${id}/restore`);
  return response.data;
}

export async function purgeDeletedOrders(ids: (number | string)[]): Promise<ApiResponse<any>> {
  const response = await api.post('/orders/purge-selected', { ids });
  return response.data;
}

export async function recordOrderPayment(id: number | string, payload: Record<string, any>): Promise<ApiResponse<any>> {
  const response = await api.post(`/orders/${id}/payment`, payload);
  return response.data;
}

export async function resetOrderPayment(id: number | string): Promise<ApiResponse<any>> {
  const response = await api.post(`/orders/${id}/payment/reset`);
  return response.data;
}
