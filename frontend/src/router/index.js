import { createRouter, createWebHistory } from 'vue-router';
import LoginPage from '../modules/auth/pages/LoginPage.vue';
import { clearAuthCache, me } from '../modules/auth/services/auth.api';
import CategoryFormPage from '../modules/category/pages/CategoryFormPage.vue';
import CategoryListPage from '../modules/category/pages/CategoryListPage.vue';
import CustomerDetailPage from '../modules/customer/pages/CustomerDetailPage.vue';
import CustomerFormPage from '../modules/customer/pages/CustomerFormPage.vue';
import CustomerListPage from '../modules/customer/pages/CustomerListPage.vue';
import CustomerPaymentPage from '../modules/customer/pages/CustomerPaymentPage.vue';
import DashboardPage from '../modules/dashboard/pages/DashboardPage.vue';
import SupplierDetailPage from '../modules/supplier/pages/SupplierDetailPage.vue';
import SupplierFormPage from '../modules/supplier/pages/SupplierFormPage.vue';
import SupplierListPage from '../modules/supplier/pages/SupplierListPage.vue';
import UnitListPage from '../modules/unit/pages/UnitListPage.vue';
import OrderDetailPage from '../modules/order/pages/OrderDetailPage.vue';
import OrderFormPage from '../modules/order/pages/OrderFormPage.vue';
import OrderInvoicePage from '../modules/order/pages/OrderInvoicePage.vue';
import OrderListPage from '../modules/order/pages/OrderListPage.vue';
import OrderReturnPage from '../modules/order/pages/OrderReturnPage.vue';
import PosPage from '../modules/pos/pages/PosPage.vue';
import PurchaseDetailPage from '../modules/purchase/pages/PurchaseDetailPage.vue';
import PurchaseFormPage from '../modules/purchase/pages/PurchaseFormPage.vue';
import PurchaseListPage from '../modules/purchase/pages/PurchaseListPage.vue';
import ProductFormPage from '../modules/product/pages/ProductFormPage.vue';
import ProductListPage from '../modules/product/pages/ProductListPage.vue';
import CustomerDebtReportPage from '../modules/report/pages/CustomerDebtReportPage.vue';
import InventoryReportPage from '../modules/report/pages/InventoryReportPage.vue';
import MissingCostReportPage from '../modules/report/pages/MissingCostReportPage.vue';
import ReportIndexPage from '../modules/report/pages/ReportIndexPage.vue';
import SalesOrdersListReportPage from '../modules/report/pages/SalesOrdersListReportPage.vue';
import SalesReportPage from '../modules/report/pages/SalesReportPage.vue';
import SupplierDebtReportPage from '../modules/report/pages/SupplierDebtReportPage.vue';
import ChangePasswordPage from '../modules/system/pages/ChangePasswordPage.vue';
import MigrationPage from '../modules/system/pages/MigrationPage.vue';

const basePath = document?.body?.dataset?.basePath || '/';
const routerBase = basePath && basePath !== '/' ? `${basePath}/` : '/';

export const router = createRouter({
  history: createWebHistory(routerBase),
  routes: [
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
      path: '/reports/sales-orders',
      name: 'reports.salesOrders',
      component: SalesOrdersListReportPage
    },
    {
      path: '/reports/inventory',
      name: 'reports.inventory',
      component: InventoryReportPage
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
      path: '/orders/create',
      name: 'orders.create',
      component: OrderFormPage
    },
    {
      path: '/orders/:id',
      name: 'orders.detail',
      component: OrderDetailPage
    },
    {
      path: '/orders/:id/edit',
      name: 'orders.edit',
      component: OrderFormPage
    },
    {
      path: '/orders/:id/return',
      name: 'orders.return',
      component: OrderReturnPage
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
      path: '/suppliers/:id/edit',
      name: 'suppliers.edit',
      component: SupplierFormPage
    }
  ]
});

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
