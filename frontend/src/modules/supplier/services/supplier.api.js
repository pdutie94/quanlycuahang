import { api } from '../../../shared/services/api';

export async function fetchSuppliers(params = {}) {
  const response = await api.get('/suppliers', { params });
  return response.data;
}

export async function fetchSupplierDetail(id) {
  const response = await api.get(`/suppliers/${id}`);
  return response.data;
}

export async function fetchSupplierFormData() {
  const response = await api.get('/suppliers/bootstrap/form-data');
  return response.data;
}

export async function fetchSupplierFormEditData(id) {
  const response = await api.get(`/suppliers/${id}/form-data`);
  return response.data;
}

export async function createSupplier(payload) {
  const response = await api.post('/suppliers', payload);
  return response.data;
}

export async function updateSupplier(id, payload) {
  const response = await api.patch(`/suppliers/${id}`, payload);
  return response.data;
}

export async function deleteSupplier(id) {
  const response = await api.delete(`/suppliers/${id}`);
  return response.data;
}
