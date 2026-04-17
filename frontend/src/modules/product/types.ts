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

export interface MaterialPrice {
  id: number;
  material_type: string;
  price_per_kg: number;
  created_at?: string;
  updated_at?: string;
}

export interface ProductEditData {
  product: Product & { 
    auto_price_enabled: boolean | number;
    auto_price_value: string | number;
    min_stock_qty: number | string;
    weight_price_enabled: boolean | number;
    weight_value: number;
    material_type: string;
  };
  product_units: ProductUnitDetailed[];
  product_logs: ProductLog[];
  inventory_qty_base: number | string;
  material_prices: MaterialPrice[];
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
  redirect: string;
  auto_price_enabled: number; // 0 or 1
  auto_price_value: string;
  weight_price_enabled: number; // 0 or 1
  weight_value: string;
  material_type: string;
}
