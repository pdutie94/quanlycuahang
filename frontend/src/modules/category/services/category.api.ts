import { api } from '../../../shared/services/api';

export async function fetchCategories(params: Record<string, any> = {}) {
  const response = await api.get('/categories', { params });
  return response.data;
}

export async function fetchCategoryDetail(id: number | string) {
  const response = await api.get(`/categories/${id}`);
  return response.data;
}

export async function createCategory(payload: Record<string, any>) {
  const response = await api.post('/categories', payload);
  return response.data;
}

export async function updateCategory(id: number | string, payload: Record<string, any>) {
  const response = await api.patch(`/categories/${id}`, payload);
  return response.data;
}

export async function deleteCategory(id: number | string) {
  const response = await api.delete(`/categories/${id}`);
  return response.data;
}
