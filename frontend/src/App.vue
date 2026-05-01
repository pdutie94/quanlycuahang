<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import {
  ArrowRight,
  ChartPie,
  DollarSign,
  House,
  LayoutGrid,
  Menu,
  Package,
  ReceiptText,
  Ruler,
  ScanLine,
  ShoppingCart,
  Store,
  User,
  Users,
  X
} from '@lucide/vue';
import { logout } from './modules/auth/services/auth.api';
import { useToast } from './shared/composables/useToast';
import { usePreloadRoutes } from './shared/composables/usePreloadRoutes';
import AppToast from './shared/components/AppToast.vue';
import { onMounted } from 'vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const showMenu = ref(false);
const showShell = computed(() => route.meta?.hideShell !== true);

// Preload critical routes after login for better UX
const { preloadCriticalRoutes } = usePreloadRoutes();
const hasPreloaded = ref(false);

// Watch for navigation to dashboard (after login) and trigger preload
watch(
  () => route.name,
  (routeName) => {
    if (routeName === 'dashboard' && !hasPreloaded.value) {
      hasPreloaded.value = true;
      // Preload critical routes 2 seconds after login
      setTimeout(() => {
        preloadCriticalRoutes();
      }, 2000);
    }
  }
);

const onLogout = async () => {
  try {
    const result = await logout();
    toast.success(result?.message || 'Đã đăng xuất.');
  } catch (error: any) {
    toast.error(error?.response?.data?.message || 'Đã đăng xuất.');
  } finally {
    showMenu.value = false;
    router.push('/login');
  }
};

watch(
  () => route.fullPath,
  () => {
    showMenu.value = false;
  }
);
</script>

<template>
  <AppToast />
  <div class="app-shell flex min-h-screen flex-col">
    <main class="flex-1" :class="showShell ? 'pb-[3.5rem]' : ''">
      <div v-if="showShell" id="app-list-header-title-host" class="app-list-header-title-host"></div>
      <div v-if="showShell" id="app-list-header-form-host" class="app-list-header-form-host"></div>
      <div class="app-content-wrap py-4 pb-6">
        <router-view v-slot="{ Component, route: currentRoute }">
          <transition name="page-fade" mode="out-in" appear>
            <component :is="Component" :key="currentRoute.path" />
          </transition>
        </router-view>
      </div>
    </main>

    <nav v-if="showShell" class="app-bottom-nav">
      <div class="app-bottom-nav-inner text-center text-slate-700">
        <RouterLink to="/dashboard" class="app-bottom-nav-link" active-class="app-bottom-nav-link-active">
          <House class="h-5 w-5" />
          <span>Home</span>
        </RouterLink>
        <RouterLink to="/pos" class="app-bottom-nav-link" active-class="app-bottom-nav-link-active">
          <ScanLine class="h-5 w-5" />
          <span>Tạo đơn</span>
        </RouterLink>
        <RouterLink to="/products" class="app-bottom-nav-link" active-class="app-bottom-nav-link-active">
          <Package class="h-5 w-5" />
          <span>Sản phẩm</span>
        </RouterLink>
        <RouterLink to="/reports" class="app-bottom-nav-link" active-class="app-bottom-nav-link-active">
          <ChartPie class="h-5 w-5" />
          <span>Báo cáo</span>
        </RouterLink>
        <button type="button" class="app-bottom-nav-link" @click="showMenu = true">
          <Menu class="h-5 w-5" />
          <span>Menu</span>
        </button>
      </div>
    </nav>

    <Teleport to="body">
      <transition name="app-modal-fade-up" appear>
        <div v-if="showShell && showMenu" class="app-modal-overlay app-modal-open" @click.self="showMenu = false">
          <div class="app-modal-sheet">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
          <div class="font-display text-base font-semibold text-slate-900">Menu quản lý</div>
          <button type="button" class="app-modal-close" aria-label="Đóng" @click="showMenu = false">
            <X class="h-5 w-5" />
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 text-sm">
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <RouterLink to="/dashboard" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><House class="h-4 w-4 text-slate-500" />Tổng quan</RouterLink>
            <RouterLink to="/pos" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><ScanLine class="h-4 w-4 text-slate-500" />Bán hàng</RouterLink>
            <RouterLink to="/orders" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><ShoppingCart class="h-4 w-4 text-slate-500" />Đơn hàng</RouterLink>
            <RouterLink to="/products" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><Package class="h-4 w-4 text-slate-500" />Sản phẩm</RouterLink>
            <RouterLink to="/purchases" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><ReceiptText class="h-4 w-4 text-slate-500" />Phiếu nhập</RouterLink>
            <RouterLink to="/customers" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><Users class="h-4 w-4 text-slate-500" />Khách hàng</RouterLink>
            <RouterLink to="/suppliers" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><Store class="h-4 w-4 text-slate-500" />Nhà cung cấp</RouterLink>
            <RouterLink to="/reports" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><ChartPie class="h-4 w-4 text-slate-500" />Báo cáo tổng quan</RouterLink>
            <RouterLink to="/reports/inventory" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><ChartPie class="h-4 w-4 text-slate-500" />Báo cáo tồn kho</RouterLink>
            <RouterLink to="/categories" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><LayoutGrid class="h-4 w-4 text-slate-500" />Danh mục</RouterLink>
            <RouterLink to="/units" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><Ruler class="h-4 w-4 text-slate-500" />Đơn vị tính</RouterLink>
            <RouterLink to="/material-prices" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><DollarSign class="h-4 w-4 text-slate-500" />Giá vật liệu</RouterLink>
            <RouterLink to="/change-password" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><User class="h-4 w-4 text-slate-500" />Tài khoản</RouterLink>
            <RouterLink to="/migrations" class="flex min-h-10 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 hover:bg-slate-50"><ArrowRight class="h-4 w-4 text-slate-500" />Migration</RouterLink>
          </div>
        </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<style>
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

.page-fade-enter-to,
.page-fade-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>
