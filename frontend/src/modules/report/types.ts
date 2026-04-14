export interface ReportOverviewStats {
  total_amount: number;
  order_count: number;
  profit?: number;
  paid_amount?: number;
  debt_amount?: number;
}

export interface DeltaInfo {
  amount: number;
  percent: number | null;
}

export interface ReportOverview {
  orders_today: ReportOverviewStats;
  orders_month: ReportOverviewStats;
  purchases_month: ReportOverviewStats;
  customer_debt: number;
  supplier_debt: number;
  delta: {
    orders_today_total: DeltaInfo;
    orders_month_total: DeltaInfo;
    orders_today_profit: DeltaInfo;
    orders_month_profit: DeltaInfo;
    purchases_month_total: DeltaInfo;
    customer_debt: DeltaInfo;
    supplier_debt: DeltaInfo;
  };
  updated_at_text: string;
  recent_orders?: any[];
  low_stock_items?: any[];
}

export interface DebtReportSummary {
  total_amount: number;
  paid_amount: number;
  debt_amount: number;
}

export interface DebtReportItem {
  id: number | string;
  name: string;
  phone: string;
  total_debt: number;
  last_order_date?: string;
  last_purchase_date?: string;
  total_amount?: number;
  paid_amount?: number;
}

export interface SalesReportSummary {
  order_count: number;
  total_amount: number;
  total_cost: number;
  profit: number;
  paid_amount: number;
  debt_amount: number;
}

export interface SalesReportItem {
  date: string;
  order_count: number;
  total_sales: number;
  total_profit: number;
}

export interface InventoryReportItem {
  id: number | string;
  name: string;
  product_code?: string;
  base_unit_name: string;
  qty_base: number | string;
  min_step: number | string;
  min_stock_qty?: number | string;
  status?: 'normal' | 'low';
}

export interface MissingCostSummary {
  item_count: number;
  order_count: number;
  total_delta_cost: number;
}

export interface MissingCostItem {
  item_id: number | string;
  order_id: number | string;
  order_code: string;
  order_date: string;
  customer_name?: string;
  product_name: string;
  unit_name: string;
  qty: number;
  item_price_cost: number;
  unit_price_cost: number;
}

export interface CostUpdateItem {
  id: number | string;
  name: string;
  code: string;
  base_unit_name: string;
  price_cost: number | string;
}
