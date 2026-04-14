import type { Unit, Product } from '../../shared/types';

export interface Category {
  id: number | string;
  name: string;
}

export interface ProductUnitDetailed {
  id: number | string;
  product_id: number | string;
  unit_id: number | string;
  unit_name: string;
  price_sell: number;
  price_cost: number;
  allow_fraction: number;
  min_step: number;
}

export interface ProductLog {
  id: number;
  product_id: number;
  action: string;
  detail: string; // Added this
  description: string;
  created_at: string;
  user_name: string;
}

export interface ProductFormData {
  units: Unit[];
  categories: Category[];
}

export interface ProductEditData {
  product: Product & { 
    auto_price_enabled: boolean | number;
    auto_price_value: string | number;
    min_stock_qty: number | string;
  };
  product_units: ProductUnitDetailed[];
  product_logs: ProductLog[];
  inventory_qty_base: number | string;
}

export interface ProductFormState {
  name: string;
  code: string;
  base_unit_id: string;
  category_id: string;
  price_sell_single: string;
  price_cost_single: string;
  allow_fraction: boolean;
  min_step: string;
  inventory_qty_base: string;
  min_stock_qty: string;
  redirect: 'stay' | 'list' | 'detail';
  auto_price_enabled: number; // 0 or 1
  auto_price_value: string;
}
