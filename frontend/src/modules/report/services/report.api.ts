import { api } from '../../../shared/services/api';
import type { ApiResponse } from '../../order/services/order.api';
import type { 
  ReportOverview, 
  DebtReportItem, 
  SalesReportItem, 
  InventoryReportItem, 
  MissingCostItem 
} from '../types';

export async function fetchReportOverview(): Promise<ApiResponse<ReportOverview>> {
  const response = await api.get('/reports/overview');
  return response.data;
}

export async function fetchCustomerDebtReport(params: Record<string, any> = {}): Promise<ApiResponse<DebtReportItem[]>> {
  const response = await api.get('/reports/customer-debt', { params });
  return response.data;
}

export async function fetchSupplierDebtReport(params: Record<string, any> = {}): Promise<ApiResponse<DebtReportItem[]>> {
  const response = await api.get('/reports/supplier-debt', { params });
  return response.data;
}

export async function fetchSalesReport(params: Record<string, any> = {}): Promise<ApiResponse<SalesReportItem[]>> {
  const response = await api.get('/reports/sales', { params });
  return response.data;
}

export async function fetchInventoryReport(): Promise<ApiResponse<InventoryReportItem[]>> {
  const response = await api.get('/reports/inventory');
  return response.data;
}

export async function submitInventoryAdjust(payload: Record<string, any>): Promise<ApiResponse<any>> {
  const response = await api.post('/reports/inventory/adjust', payload);
  return response.data;
}

export async function fetchMissingCostReport(params: Record<string, any> = {}): Promise<ApiResponse<MissingCostItem[]>> {
  const response = await api.get('/reports/missing-cost', { params });
  return response.data;
}

export async function submitMissingCostUpdate(payload: Record<string, any>): Promise<ApiResponse<any>> {
  const response = await api.post('/reports/missing-cost/update', payload);
  return response.data;
}
