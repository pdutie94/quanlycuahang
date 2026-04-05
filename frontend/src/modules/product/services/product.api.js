import { api } from '../../../shared/services/api';

export async function fetchProducts(params = {}) {
  const response = await api.get('/products', { params });
  return response.data;
}

export async function fetchProductFormData() {
  const response = await api.get('/products/bootstrap/form-data');
  return response.data;
}

export async function fetchProductFormEditData(id) {
  const response = await api.get(`/products/${id}/form-data`);
  return response.data;
}

export async function createProduct(payload) {
  const response = await api.post('/products', payload);
  return response.data;
}

export async function updateProduct(id, payload) {
  const response = await api.put(`/products/${id}`, payload);
  return response.data;
}

export async function deleteProduct(id) {
  const response = await api.delete(`/products/${id}`);
  return response.data;
}
