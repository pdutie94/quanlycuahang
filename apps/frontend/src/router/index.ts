import { createRouter, createWebHistory } from 'vue-router'
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
import { useUiStore } from '../stores/ui'

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
      meta: { requiresAuth: true, depth: 1 },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: DashboardView,
          meta: { depth: 1 },
        },
        {
          path: 'products',
          name: 'products',
          component: ProductListView,
          meta: { depth: 2 },
        },
        {
          path: 'products/new',
          name: 'products-create',
          component: ProductFormView,
          meta: { depth: 3 },
        },
        {
          path: 'products/:id/edit',
          name: 'products-edit',
          component: ProductFormView,
          meta: { depth: 3 },
        },
        {
          path: 'categories',
          name: 'categories',
          component: CategoriesView,
          meta: { depth: 2 },
        },
        {
          path: 'units',
          name: 'units',
          component: UnitsView,
          meta: { depth: 2 },
        },
        {
          path: 'suppliers',
          name: 'suppliers',
          component: SupplierListView,
          meta: { depth: 2 },
        },
        {
          path: 'suppliers/new',
          name: 'suppliers-create',
          component: SupplierFormView,
          meta: { depth: 3 },
        },
        {
          path: 'suppliers/:id',
          name: 'suppliers-detail',
          component: SupplierDetailView,
          meta: { depth: 3 },
        },
        {
          path: 'suppliers/:id/edit',
          name: 'suppliers-edit',
          component: SupplierFormView,
          meta: { depth: 4 },
        },
        {
          path: 'customers',
          name: 'customers',
          component: CustomerListView,
          meta: { depth: 2 },
        },
        {
          path: 'customers/new',
          name: 'customers-create',
          component: CustomerFormView,
          meta: { depth: 3 },
        },
        {
          path: 'customers/:id',
          name: 'customers-detail',
          component: CustomerDetailView,
          meta: { depth: 3 },
        },
        {
          path: 'customers/:id/edit',
          name: 'customers-edit',
          component: CustomerFormView,
          meta: { depth: 4 },
        },
        {
          path: 'orders',
          name: 'orders',
          component: OrderListView,
          meta: { depth: 2 },
        },
        {
          path: 'orders/new',
          name: 'orders-create',
          component: OrderFormView,
          meta: { depth: 3 },
        },
        {
          path: 'orders/:id',
          name: 'orders-detail',
          component: OrderDetailView,
          meta: { depth: 3 },
        },
        {
          path: 'pos',
          name: 'pos',
          component: PosView,
          meta: { depth: 2 },
        },
        {
          path: 'purchases',
          name: 'purchases',
          component: PurchaseListView,
          meta: { depth: 2 },
        },
        {
          path: 'purchases/new',
          name: 'purchases-create',
          component: PurchaseFormView,
          meta: { depth: 3 },
        },
        {
          path: 'purchases/:id',
          name: 'purchases-detail',
          component: PurchaseDetailView,
          meta: { depth: 3 },
        },
        {
          path: 'purchases/:id/edit',
          name: 'purchases-edit',
          component: PurchaseFormView,
          meta: { depth: 4 },
        },
        {
          path: 'reports',
          name: 'reports',
          component: ReportsHomeView,
          meta: { depth: 2 },
        },
        {
          path: 'reports/sales',
          name: 'reports-sales',
          component: ReportSalesView,
          meta: { depth: 3 },
        },
        {
          path: 'reports/customer-debt',
          name: 'reports-customer-debt',
          component: ReportCustomerDebtView,
          meta: { depth: 3 },
        },
        {
          path: 'reports/supplier-debt',
          name: 'reports-supplier-debt',
          component: ReportSupplierDebtView,
          meta: { depth: 3 },
        },
        {
          path: 'reports/missing-cost',
          name: 'reports-missing-cost',
          component: ReportMissingCostView,
          meta: { depth: 3 },
        },
        {
          path: 'reports/inventory',
          name: 'reports-inventory',
          component: ReportInventoryView,
          meta: { depth: 3 },
        },
        {
          path: 'reports/inventory-adjust',
          name: 'reports-inventory-adjust',
          component: ReportInventoryAdjustView,
          meta: { depth: 3 },
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView,
      meta: { depth: 1 },
    },
  ],
})

router.beforeEach(async (to, from) => {
  const auth = useAuthStore()
  const ui = useUiStore()
  await auth.init()

  const toDepth = Number(to.meta.depth ?? 1)
  const fromDepth = Number(from.meta.depth ?? 1)
  ui.setTransitionName(toDepth >= fromDepth ? 'slide-left' : 'slide-right')

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && auth.isLoggedIn) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
