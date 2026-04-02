import { api } from '../../../shared/services/api';

export async function fetchReportOverview() {
  const response = await api.get('/reports/overview');
  return response.data;
}

export async function fetchCustomerDebtReport(params = {}) {
  const response = await api.get('/reports/customer-debt', { params });
  return response.data;
}

export async function fetchSupplierDebtReport(params = {}) {
  const response = await api.get('/reports/supplier-debt', { params });
  return response.data;
}

export async function fetchSalesReport(params = {}) {
  const response = await api.get('/reports/sales', { params });
  return response.data;
}

export async function fetchInventoryReport() {
  const response = await api.get('/reports/inventory');
  return response.data;
}

export async function submitInventoryAdjust(payload) {
  const response = await api.post('/reports/inventory/adjust', payload);
  return response.data;
}

export async function fetchMissingCostReport(params = {}) {
  const response = await api.get('/reports/missing-cost', { params });
  return response.data;
}

export async function submitMissingCostUpdate(payload) {
  const response = await api.post('/reports/missing-cost/update', payload);
  return response.data;
}
