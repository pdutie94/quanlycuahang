import type { ProductUnit } from '../../shared/types';

export interface PurchaseItem {
  id?: number | string;
  product_unit_id: string;
  qty: string | number;
  price_cost: string | number;
  amount: string | number;
  allow_fraction?: number;
  min_step?: number;
  update_cost?: boolean;
}

export interface ManualPurchaseItem {
  item_name: string;
  unit_name: string;
  qty: string | number;
  price_cost: string | number;
  amount: string | number;
  qty_precision?: number;
}

export interface Purchase {
  id: number | string;
  supplier_id: number | string | null;
  supplier_name: string;
  purchase_date: string;
  note?: string;
  status: 'pay' | 'debt';
  paid_amount: number;
  total_amount: number;
  items?: any[];
  manual_items?: any[];
}

export interface PurchaseFormState {
  supplier_id: string;
  purchase_date: string;
  payment_status: 'pay' | 'debt';
  payment_method: 'cash' | 'bank';
  paid_amount: string;
  note: string;
}

export interface PurchaseBootstrapData {
  suppliers: any[];
  product_units: ProductUnit[];
}
