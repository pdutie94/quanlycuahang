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
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true },
    },
    {
      path: '/',
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: DashboardView,
        },
        {
          path: 'products',
          name: 'products',
          component: ProductListView,
        },
        {
          path: 'products/new',
          name: 'products-create',
          component: ProductFormView,
        },
        {
          path: 'products/:id/edit',
          name: 'products-edit',
          component: ProductFormView,
        },
        {
          path: 'categories',
          name: 'categories',
          component: CategoriesView,
        },
        {
          path: 'units',
          name: 'units',
          component: UnitsView,
        },
        {
          path: 'suppliers',
          name: 'suppliers',
          component: SupplierListView,
        },
        {
          path: 'suppliers/new',
          name: 'suppliers-create',
          component: SupplierFormView,
        },
        {
          path: 'suppliers/:id',
          name: 'suppliers-detail',
          component: SupplierDetailView,
        },
        {
          path: 'suppliers/:id/edit',
          name: 'suppliers-edit',
          component: SupplierFormView,
        },
        {
          path: 'customers',
          name: 'customers',
          component: CustomerListView,
        },
        {
          path: 'customers/new',
          name: 'customers-create',
          component: CustomerFormView,
        },
        {
          path: 'customers/:id',
          name: 'customers-detail',
          component: CustomerDetailView,
        },
        {
          path: 'customers/:id/edit',
          name: 'customers-edit',
          component: CustomerFormView,
        },
        {
          path: 'orders',
          name: 'orders',
          component: OrderListView,
        },
        {
          path: 'orders/new',
          name: 'orders-create',
          component: OrderFormView,
        },
        {
          path: 'orders/:id',
          name: 'orders-detail',
          component: OrderDetailView,
        },
        {
          path: 'pos',
          name: 'pos',
          component: PosView,
        },
        {
          path: 'purchases',
          name: 'purchases',
          component: PurchaseListView,
        },
        {
          path: 'purchases/new',
          name: 'purchases-create',
          component: PurchaseFormView,
        },
        {
          path: 'purchases/:id',
          name: 'purchases-detail',
          component: PurchaseDetailView,
        },
        {
          path: 'purchases/:id/edit',
          name: 'purchases-edit',
          component: PurchaseFormView,
        },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView,
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
