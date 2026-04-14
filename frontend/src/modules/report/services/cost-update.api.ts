import { api } from '../../../shared/services/api';
import type { ApiResponse } from '../../order/services/order.api';
import type { CostUpdateItem } from '../types';

export async function fetchCostUpdateList(): Promise<ApiResponse<{ items: CostUpdateItem[] }>> {
  const response = await api.get('/reports/cost-update');
  return response.data;
}

export async function submitCostUpdate(payload: { product_id: number | string; price_cost: number | string }): Promise<ApiResponse<any>> {
  const response = await api.post('/reports/cost-update', payload);
  return response.data;
}
