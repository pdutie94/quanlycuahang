<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { ChartPie, ClipboardList, Plus, ShoppingCart } from '@lucide/vue';
import { useToast } from '../../../shared/composables/useToast';
import { useReportOverview } from '../../report/composables/useReportOverview';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';

const toast = useToast();
const { overview, loading, error, load } = useReportOverview();

const formatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;
const formatDateTime = (value) => {
  if (!value) return '';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
};

const weekdayNames = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];

const todayText = computed(() => {
  const today = new Date();
  const weekday = weekdayNames[today.getDay()] || '';
  const dateText = new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(today);

  return `${weekday}, ${dateText}`;
});

const recentOrders = computed(() => overview.value?.recent_orders || []);

const lowStockPreview = computed(() => (overview.value?.low_stock_items || []).slice(0, 5));

onMounted(async () => {
  try {
    await load();
  } catch (_err) {
    toast.error(error.value || 'Không thể tải dashboard.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <h1 class="font-display text-2xl font-bold text-slate-900">Hôm nay</h1>
      <span class="inline-flex rounded-chip border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700">{{ todayText }}</span>
    </div>

    <div v-if="loading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>

    <template v-else>
      <section class="grid grid-cols-2 gap-3 text-sm xl:grid-cols-4">
        <article class="app-kpi-card">
          <div class="text-sm text-slate-500">Doanh thu</div>
          <div class="mt-0.5 text-xl font-semibold text-brand-700">{{ formatMoney(overview.orders_today?.total_amount) }}</div>
        </article>
        <article class="app-kpi-card">
          <div class="text-sm text-slate-500">Lợi nhuận</div>
          <div class="mt-0.5 text-xl font-semibold text-emerald-700">{{ formatMoney(overview.orders_today?.profit) }}</div>
        </article>
        <article class="app-kpi-card">
          <div class="text-sm text-slate-500">Đã thu</div>
          <div class="mt-0.5 text-xl font-semibold text-cyan-700">{{ formatMoney(overview.orders_today?.paid_amount) }}</div>
        </article>
        <article class="app-kpi-card">
          <div class="text-sm text-slate-500">Còn nợ</div>
          <div class="mt-0.5 text-xl font-semibold text-rose-700">{{ formatMoney(overview.orders_today?.debt_amount) }}</div>
        </article>
      </section>

      <section class="space-y-2">
        <div class="text-sm font-semibold text-brand-800">Lối tắt</div>
        <div class="grid grid-cols-2 gap-2 xl:grid-cols-4">
          <RouterLink to="/pos" class="app-shortcut-link">
            <span class="app-shortcut-icon">
              <ShoppingCart class="h-4 w-4" />
            </span>
            <span>Tạo đơn</span>
          </RouterLink>
          <RouterLink to="/orders" class="app-shortcut-link">
            <span class="app-shortcut-icon">
              <ClipboardList class="h-4 w-4" />
            </span>
            <span>Đơn hàng</span>
          </RouterLink>
          <RouterLink to="/products/create" class="app-shortcut-link">
            <span class="app-shortcut-icon">
              <Plus class="h-4 w-4" />
            </span>
            <span>Thêm SP</span>
          </RouterLink>
          <RouterLink to="/reports" class="app-shortcut-link">
            <span class="app-shortcut-icon">
              <ChartPie class="h-4 w-4" />
            </span>
            <span>Báo cáo</span>
          </RouterLink>
        </div>
      </section>

      <section v-if="lowStockPreview.length" class="space-y-2">
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold text-rose-600">Hàng sắp hết</div>
          <RouterLink to="/reports/inventory" class="text-sm font-medium text-rose-600 hover:text-rose-700">Xem tồn kho</RouterLink>
        </div>
        <div class="space-y-2">
          <div v-for="item in lowStockPreview" :key="item.product_id || item.id" class="flex items-center justify-between rounded-card border border-rose-100 bg-white px-4 py-3 text-sm">
            <div class="min-w-0">
              <div class="truncate font-medium text-rose-900">{{ item.name }}</div>
              <div class="mt-0.5 text-sm text-rose-700">Tồn: {{ Number(item.qty_base || 0) }} {{ item.base_unit_name || '' }}</div>
            </div>
            <span class="ml-2 rounded-lg bg-rose-500 px-2 py-0.5 text-sm font-medium text-white">Sắp hết</span>
          </div>
        </div>
      </section>

      <section class="space-y-2">
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold text-slate-700">Đơn hàng gần đây</div>
          <RouterLink to="/orders" class="text-sm font-medium text-brand-600 hover:text-brand-700">Xem tất cả</RouterLink>
        </div>
        <div v-if="!recentOrders.length" class="rounded-card border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">Không có đơn hàng gần đây.</div>
        <OrderItemCard v-for="order in recentOrders" :key="order.id" :order="order" :to="{ name: 'orders.detail', params: { id: order.id } }" customer-fallback="Khách lẻ" />
      </section>
    </template>
  </section>
</template>
