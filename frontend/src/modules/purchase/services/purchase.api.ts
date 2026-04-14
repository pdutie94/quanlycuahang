import { api } from '../../../shared/services/api';
import type { ApiResponse } from '../../order/services/order.api';
import type { Purchase, PurchaseBootstrapData } from '../types';

export async function fetchPurchases(params: Record<string, any> = {}): Promise<ApiResponse<Purchase[]>> {
  const response = await api.get('/purchases', { params });
  return response.data;
}

export async function fetchPurchaseDetail(id: number | string): Promise<ApiResponse<{ purchase: Purchase; items: any[]; manual_items: any[] }>> {
  const response = await api.get(`/purchases/${id}`);
  return response.data;
}

export async function fetchPurchaseFormData(): Promise<ApiResponse<PurchaseBootstrapData>> {
  const response = await api.get('/purchases/bootstrap/form-data');
  return response.data;
}

export async function createPurchase(payload: Record<string, any>): Promise<ApiResponse<Purchase>> {
  const response = await api.post('/purchases', payload);
  return response.data;
}

export async function updatePurchase(id: number | string, payload: Record<string, any>): Promise<ApiResponse<Purchase>> {
  const response = await api.patch(`/purchases/${id}`, payload);
  return response.data;
}

export async function recordPurchasePayment(id: number | string, payload: Record<string, any>): Promise<ApiResponse<any>> {
  const response = await api.post(`/purchases/${id}/payment`, payload);
  return response.data;
}

export async function deletePurchase(id: number | string): Promise<ApiResponse<any>> {
  const response = await api.delete(`/purchases/${id}`);
  return response.data;
}
