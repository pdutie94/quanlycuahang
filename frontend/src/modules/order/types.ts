import type { Customer, ProductUnit } from '../../shared/types';

export interface OrderItem {
  id?: number | string;
  source_id?: number | string;
  product_unit_id: string;
  qty: string | number;
  price: string | number;
}

export interface ManualOrderItem {
  item_name: string;
  unit_name: string;
  qty: string | number;
  price_buy: string | number;
  price_sell: string | number;
}

export interface Order {
  id: number | string;
  customer_id: number | string | null;
  customer_name: string;
  customer_phone?: string;
  customer_address?: string;
  order_date: string;
  note?: string;
  status: 'pay' | 'debt';
  paid_amount: number;
  total_amount: number;
  discount_type: 'none' | 'fixed' | 'percent';
  discount_amount: number;
  discount_value: string | number;
  surcharge_amount: number;
  items?: any[]; // Detailed items if fetched
  manual_items?: any[];
}

export interface OrderFormState {
  customer_id: string;
  customer_name: string;
  customer_phone: string;
  customer_address: string;
  order_date: string;
  note: string;
  payment_status: 'pay' | 'debt';
  payment_method: 'cash' | 'bank';
  payment_amount: string;
  discount_type: 'none' | 'fixed' | 'percent';
  discount_value: string;
  surcharge_amount: string;
}

export interface OrderBootstrapData {
  products: any[];
  product_units_by_product: Record<string, any[]>;
  customers: Customer[];
}
