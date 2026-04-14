import { api } from '../../../shared/services/api';
import type { ApiResponse } from '../../order/services/order.api'; // Reuse ApiResponse for now
import type { Product, Unit } from '../../../shared/types';
import type { ProductFormData, ProductEditData } from '../types';

export async function fetchProducts(params: Record<string, any> = {}): Promise<ApiResponse<Product[]>> {
  const response = await api.get('/products', { params });
  return response.data;
}

export async function fetchProductFormData(): Promise<ApiResponse<ProductFormData>> {
  const response = await api.get('/products/bootstrap/form-data');
  return response.data;
}

export async function fetchProductFormEditData(id: number | string): Promise<ApiResponse<ProductEditData>> {
  const response = await api.get(`/products/${id}/form-data`);
  return response.data;
}

export async function createProduct(payload: Record<string, any>): Promise<ApiResponse<Product>> {
  const response = await api.post('/products', payload);
  return response.data;
}

export async function updateProduct(id: number | string, payload: Record<string, any>): Promise<ApiResponse<Product>> {
  const response = await api.put(`/products/${id}`, payload);
  return response.data;
}

export async function deleteProduct(id: number | string): Promise<ApiResponse<any>> {
  const response = await api.delete(`/products/${id}`);
  return response.data;
}
