export interface Customer {
  id: number | string;
  name: string;
  phone?: string;
  address?: string;
  debt?: number;
  total_buy?: number;
  last_buy_date?: string;
}

export interface Unit {
  id: number | string;
  name: string;
}

export interface Product {
  id: number | string;
  name: string;
  code?: string;
  category_id?: number | string;
  base_unit_id?: number | string;
}

export interface ProductUnit {
  id: number | string;
  product_id: number | string;
  product_name: string;
  product_code?: string;
  unit_id: number | string;
  unit_name: string;
  price_sell: number;
  price_cost: number;
  allow_fraction: number; // 0 or 1
  min_step: number;
}
