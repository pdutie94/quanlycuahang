import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { clearAuthCache, me } from '../modules/auth/services/auth.api';

const LoginPage = () => import('../modules/auth/pages/LoginPage.vue');
const CategoryFormPage = () => import('../modules/category/pages/CategoryFormPage.vue');
const CategoryListPage = () => import('../modules/category/pages/CategoryListPage.vue');
const CustomerDebtPaymentPage = () => import('../modules/customer/pages/CustomerDebtPaymentPage.vue');
const CustomerDetailPage = () => import('../modules/customer/pages/CustomerDetailPage.vue');
const CustomerFormPage = () => import('../modules/customer/pages/CustomerFormPage.vue');
const CustomerListPage = () => import('../modules/customer/pages/CustomerListPage.vue');
const CustomerPaymentPage = () => import('../modules/customer/pages/CustomerPaymentPage.vue');
const DashboardPage = () => import('../modules/dashboard/pages/DashboardPage.vue');
const SupplierDetailPage = () => import('../modules/supplier/pages/SupplierDetailPage.vue');
const SupplierDebtPaymentPage = () => import('../modules/supplier/pages/SupplierDebtPaymentPage.vue');
const SupplierFormPage = () => import('../modules/supplier/pages/SupplierFormPage.vue');
const SupplierListPage = () => import('../modules/supplier/pages/SupplierListPage.vue');
const UnitListPage = () => import('../modules/unit/pages/UnitListPage.vue');
const OrderDetailPage = () => import('../modules/order/pages/OrderDetailPage.vue');
const OrderFormPage = () => import('../modules/order/pages/OrderFormPage.vue');
const OrderInvoicePage = () => import('../modules/order/pages/OrderInvoicePage.vue');
const OrderListPage = () => import('../modules/order/pages/OrderListPage.vue');
const OrderReturnPage = () => import('../modules/order/pages/OrderReturnPage.vue');
const OrderTrashPage = () => import('../modules/order/pages/OrderTrashPage.vue');
const PosPage = () => import('../modules/pos/pages/PosPage.vue');
const PurchaseDetailPage = () => import('../modules/purchase/pages/PurchaseDetailPage.vue');
const PurchaseFormPage = () => import('../modules/purchase/pages/PurchaseFormPage.vue');
const PurchaseListPage = () => import('../modules/purchase/pages/PurchaseListPage.vue');
const ProductFormPage = () => import('../modules/product/pages/ProductFormPage.vue');
const ProductListPage = () => import('../modules/product/pages/ProductListPage.vue');
const MaterialPricePage = () => import('../modules/material-price/pages/MaterialPriceListPage.vue');
const CustomerDebtReportPage = () => import('../modules/report/pages/CustomerDebtReportPage.vue');
const InventoryReportPage = () => import('../modules/report/pages/InventoryReportPage.vue');
const MissingCostReportPage = () => import('../modules/report/pages/MissingCostReportPage.vue');
const ReportIndexPage = () => import('../modules/report/pages/ReportIndexPage.vue');
const SalesReportPage = () => import('../modules/report/pages/SalesReportPage.vue');
const SupplierDebtReportPage = () => import('../modules/report/pages/SupplierDebtReportPage.vue');
const ChangePasswordPage = () => import('../modules/system/pages/ChangePasswordPage.vue');
const MigrationPage = () => import('../modules/system/pages/MigrationPage.vue');
const CostUpdatePage = () => import('../modules/report/pages/CostUpdatePage.vue');

const routerBase = (window as any).__BASE_PATH__ || '';

const routes: RouteRecordRaw[] = [
  {
    path: '/reports/inventory',
    name: 'reports.inventory',
    component: InventoryReportPage
  },
  {
    path: '/reports/cost-update',
    name: 'reports.costUpdate',
    component: CostUpdatePage
  },
  {
    path: '/login',
    name: 'auth.login',
    component: LoginPage,
    meta: { public: true, hideShell: true }
  },
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'dashboard.index',
    component: DashboardPage
  },
  {
    path: '/products',
    name: 'products.list',
    component: ProductListPage
  },
  {
    path: '/products/create',
    name: 'products.create',
    component: ProductFormPage
  },
  {
    path: '/products/:id/edit',
    name: 'products.edit',
    component: ProductFormPage
  },
  {
    path: '/material-prices',
    name: 'material.prices',
    component: MaterialPricePage
  },
  {
    path: '/categories',
    name: 'categories.list',
    component: CategoryListPage
  },
  {
    path: '/categories/create',
    name: 'categories.create',
    component: CategoryFormPage
  },
  {
    path: '/categories/:id/edit',
    name: 'categories.edit',
    component: CategoryFormPage
  },
  {
    path: '/units',
    name: 'units.list',
    component: UnitListPage
  },
  {
    path: '/reports',
    name: 'reports.index',
    component: ReportIndexPage
  },
  {
    path: '/reports/customer-debt',
    name: 'reports.customerDebt',
    component: CustomerDebtReportPage
  },
  {
    path: '/reports/supplier-debt',
    name: 'reports.supplierDebt',
    component: SupplierDebtReportPage
  },
  {
    path: '/reports/sales',
    name: 'reports.sales',
    component: SalesReportPage
  },
  {
    path: '/reports/missing-cost',
    name: 'reports.missingCost',
    component: MissingCostReportPage
  },
  {
    path: '/change-password',
    name: 'auth.changePassword',
    component: ChangePasswordPage
  },
  {
    path: '/migrations',
    name: 'system.migrations',
    component: MigrationPage
  },
  {
    path: '/customers',
    name: 'customers.list',
    component: CustomerListPage
  },
  {
    path: '/customers/create',
    name: 'customers.create',
    component: CustomerFormPage
  },
  {
    path: '/customers/:id',
    name: 'customers.detail',
    component: CustomerDetailPage
  },
  {
    path: '/customers/:id/payment',
    name: 'customers.debtPayment',
    component: CustomerDebtPaymentPage
  },
  {
    path: '/customers/:id/edit',
    name: 'customers.edit',
    component: CustomerFormPage
  },
  {
    path: '/customers/orders/:orderId/payment',
    name: 'customers.payment',
    component: CustomerPaymentPage
  },
  {
    path: '/orders',
    name: 'orders.list',
    component: OrderListPage
  },
  {
    path: '/orders/deleted',
    name: 'orders.deleted',
    component: OrderTrashPage
  },
    // Đã bỏ route tạo đơn hàng riêng, chuyển sang POS
    {
      path: '/orders/:id',
      name: 'orders.detail',
      component: OrderDetailPage
    },
    {
      path: '/orders/:id/return',
      name: 'orders.return',
      component: OrderReturnPage
    },
    {
      path: '/orders/:id/edit',
      name: 'orders.edit',
      component: OrderFormPage
    },
    {
      path: '/orders/:id/invoice',
      name: 'orders.invoice',
      component: OrderInvoicePage
    },
    {
      path: '/pos',
      name: 'pos.index',
      component: PosPage
    },
    {
      path: '/purchases',
      name: 'purchases.list',
      component: PurchaseListPage
    },
    {
      path: '/purchases/create',
      name: 'purchases.create',
      component: PurchaseFormPage
    },
    {
      path: '/purchases/:id',
      name: 'purchases.detail',
      component: PurchaseDetailPage
    },
    {
      path: '/purchases/:id/edit',
      name: 'purchases.edit',
      component: PurchaseFormPage
    },
    {
      path: '/suppliers',
      name: 'suppliers.list',
      component: SupplierListPage
    },
    {
      path: '/suppliers/create',
      name: 'suppliers.create',
      component: SupplierFormPage
    },
    {
      path: '/suppliers/:id',
      name: 'suppliers.detail',
      component: SupplierDetailPage
    },
    {
      path: '/suppliers/:id/payment',
      name: 'suppliers.debtPayment',
      component: SupplierDebtPaymentPage
    },
    {
      path: '/suppliers/:id/edit',
      name: 'suppliers.edit',
      component: SupplierFormPage
    }
  ]

const router = createRouter({
  history: createWebHistory(routerBase),
  routes,
  scrollBehavior() {
    return { top: 0 };
  }
});

export default router;

router.beforeEach(async (to) => {
  if (to.meta?.public) {
    try {
      const result = await me();
      if (result?.success && result?.data?.user) {
        if (to.path === '/login') {
          return '/dashboard';
        }
      }
    } catch (error) {
      clearAuthCache();
    }
    return true;
  }

  try {
    const result = await me();
    if (result?.success && result?.data?.user) {
      return true;
    }
  } catch (error) {
    clearAuthCache();
  }

  return {
    path: '/login',
    query: {
      redirect: to.fullPath
    }
  };
});
