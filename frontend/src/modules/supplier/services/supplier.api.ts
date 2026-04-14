import { api } from '../../../shared/services/api';

export async function fetchSuppliers(params: Record<string, any> = {}) {
  const response = await api.get('/suppliers', { params });
  return response.data;
}

export async function fetchSupplierDetail(id: number | string) {
  const response = await api.get(`/suppliers/${id}`);
  return response.data;
}

export async function fetchSupplierFormData() {
  const response = await api.get('/suppliers/bootstrap/form-data');
  return response.data;
}

export async function fetchSupplierFormEditData(id: number | string) {
  const response = await api.get(`/suppliers/${id}/form-data`);
  return response.data;
}

export async function createSupplier(payload: Record<string, any>) {
  const response = await api.post('/suppliers', payload);
  return response.data;
}

export async function updateSupplier(id: number | string, payload: Record<string, any>) {
  const response = await api.patch(`/suppliers/${id}`, payload);
  return response.data;
}

export async function deleteSupplier(id: number | string) {
  const response = await api.delete(`/suppliers/${id}`);
  return response.data;
}
