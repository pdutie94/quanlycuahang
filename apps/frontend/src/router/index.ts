import { createRouter, createWebHistory } from 'vue-router'
import type { Component } from 'vue'
import AppLayout from '../layouts/AppLayout.vue'
import DashboardView from '../views/DashboardView.vue'
import LoginView from '../views/auth/LoginView.vue'
import NotFoundView from '../views/NotFoundView.vue'
import ProductListView from '../views/products/ProductListView.vue'
import ProductFormView from '../views/products/ProductFormView.vue'
import CategoriesView from '../views/master-data/CategoriesView.vue'
import UnitsView from '../views/master-data/UnitsView.vue'
import SupplierListView from '../views/suppliers/SupplierListView.vue'
import SupplierFormView from '../views/suppliers/SupplierFormView.vue'
import SupplierDetailView from '../views/suppliers/SupplierDetailView.vue'
import CustomerListView from '../views/customers/CustomerListView.vue'
import CustomerFormView from '../views/customers/CustomerFormView.vue'
import CustomerDetailView from '../views/customers/CustomerDetailView.vue'
import OrderListView from '../views/orders/OrderListView.vue'
import OrderDetailView from '../views/orders/OrderDetailView.vue'
import OrderFormView from '../views/orders/OrderFormView.vue'
import PosView from '../views/pos/PosView.vue'
import PurchaseListView from '../views/purchases/PurchaseListView.vue'
import PurchaseDetailView from '../views/purchases/PurchaseDetailView.vue'
import PurchaseFormView from '../views/purchases/PurchaseFormView.vue'
import ReportsHomeView from '../views/reports/ReportsHomeView.vue'
import ReportSalesView from '../views/reports/ReportSalesView.vue'
import ReportCustomerDebtView from '../views/reports/ReportCustomerDebtView.vue'
import ReportSupplierDebtView from '../views/reports/ReportSupplierDebtView.vue'
import ReportMissingCostView from '../views/reports/ReportMissingCostView.vue'
import ReportInventoryView from '../views/reports/ReportInventoryView.vue'
import ReportInventoryAdjustView from '../views/reports/ReportInventoryAdjustView.vue'
import { useAuthStore } from '../stores/auth'

const childRoute = (
  path: string,
  name: string,
  component: Component,
  depth: number,
  pullToRefresh = false,
) => ({
  path,
  name,
  component,
  meta: {
    depth,
    ...(pullToRefresh ? { pullToRefresh: true } : {}),
  },
})

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true, depth: 0 },
    },
    {
      path: '/',
      component: AppLayout,
      meta: { requiresAuth: true, depth: 0 },
      children: [
        childRoute('', 'dashboard', DashboardView, 1, true),
        childRoute('products', 'products', ProductListView, 2, true),
        childRoute('products/new', 'products-create', ProductFormView, 3),
        childRoute('products/:id/edit', 'products-edit', ProductFormView, 3),
        childRoute('categories', 'categories', CategoriesView, 2),
        childRoute('units', 'units', UnitsView, 2),
        childRoute('suppliers', 'suppliers', SupplierListView, 2, true),
        childRoute('suppliers/new', 'suppliers-create', SupplierFormView, 3),
        childRoute('suppliers/:id', 'suppliers-detail', SupplierDetailView, 3),
        childRoute('suppliers/:id/edit', 'suppliers-edit', SupplierFormView, 3),
        childRoute('customers', 'customers', CustomerListView, 2, true),
        childRoute('customers/new', 'customers-create', CustomerFormView, 3),
        childRoute('customers/:id', 'customers-detail', CustomerDetailView, 3),
        childRoute('customers/:id/edit', 'customers-edit', CustomerFormView, 3),
        childRoute('orders', 'orders', OrderListView, 2, true),
        childRoute('orders/new', 'orders-create', OrderFormView, 3),
        childRoute('orders/:id', 'orders-detail', OrderDetailView, 3),
        childRoute('pos', 'pos', PosView, 2),
        childRoute('purchases', 'purchases', PurchaseListView, 2, true),
        childRoute('purchases/new', 'purchases-create', PurchaseFormView, 3),
        childRoute('purchases/:id', 'purchases-detail', PurchaseDetailView, 3),
        childRoute('purchases/:id/edit', 'purchases-edit', PurchaseFormView, 3),
        childRoute('reports', 'reports', ReportsHomeView, 2),
        childRoute('reports/sales', 'reports-sales', ReportSalesView, 3),
        childRoute('reports/customer-debt', 'reports-customer-debt', ReportCustomerDebtView, 3),
        childRoute('reports/supplier-debt', 'reports-supplier-debt', ReportSupplierDebtView, 3),
        childRoute('reports/missing-cost', 'reports-missing-cost', ReportMissingCostView, 3),
        childRoute('reports/inventory', 'reports-inventory', ReportInventoryView, 3),
        childRoute('reports/inventory-adjust', 'reports-inventory-adjust', ReportInventoryAdjustView, 3),
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView,
      meta: { depth: 99 },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.init()

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && auth.isLoggedIn) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
