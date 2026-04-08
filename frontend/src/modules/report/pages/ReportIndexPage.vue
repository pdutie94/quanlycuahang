<script setup>
import { BarChart2, Package, User, Truck, Tag } from '@lucide/vue';
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney } = useFormat();
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '../../../shared/composables/useToast';
import { useReportOverview } from '../composables/useReportOverview';

const toast = useToast();
const { overview, loading, error, load } = useReportOverview();

// Đã thay thế bằng useFormat

onMounted(async () => {
  try {
    await load();
  } catch (_err) {
    toast.error(error.value || 'Không thể tải báo cáo tổng quan.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <header>
      <h1 class="text-lg font-semibold text-slate-900">Báo cáo tổng quan</h1>
      <p class="mt-1 text-sm text-slate-500">Tổng hợp nhanh doanh thu, nhập hàng và công nợ hiện tại.</p>
      <p v-if="overview.updated_at_text" class="mt-1 text-sm text-slate-400">Cập nhật lần cuối: {{ overview.updated_at_text }}</p>
      <div class="mt-3 flex flex-wrap gap-2 overflow-x-auto">
        <RouterLink to="/reports/sales" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-brand-300 bg-brand-50 text-brand-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-brand-500 text-white"><BarChart2 class="h-4 w-4" /></span><span>Doanh thu chi tiết</span></RouterLink>
        <RouterLink to="/reports/inventory" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-sky-300 bg-sky-50 text-sky-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-sky-500 text-white"><Package class="h-4 w-4" /></span><span>Cập nhật tồn kho</span></RouterLink>
        <RouterLink to="/reports/customer-debt" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-rose-300 bg-rose-50 text-rose-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-rose-500 text-white"><User class="h-4 w-4" /></span><span>Công nợ khách hàng</span></RouterLink>
        <RouterLink to="/reports/supplier-debt" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-violet-300 bg-violet-50 text-violet-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-500 text-white"><Truck class="h-4 w-4" /></span><span>Công nợ nhà cung cấp</span></RouterLink>
        <RouterLink to="/reports/missing-cost" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-amber-300 bg-amber-50 text-amber-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-amber-500 text-white"><Tag class="h-4 w-4" /></span><span>Cập nhật giá vốn</span></RouterLink>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>

    <section v-else>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-sky-100 bg-white p-3">
          <div class="text-sm font-medium text-slate-500">Doanh thu tháng này</div>
          <div class="mt-0.5 text-lg font-semibold text-slate-900">{{ formatMoney(overview.orders_month?.total_amount) }}</div>
          <div v-if="overview.delta?.orders_month_total" :class="['mt-1 text-xs', overview.delta.orders_month_total.amount > 0 ? 'text-brand-600' : (overview.delta.orders_month_total.amount < 0 ? 'text-rose-600' : 'text-slate-500')]">
            {{ (overview.delta.orders_month_total.percent !== null && overview.delta.orders_month_total.percent !== undefined) ? (overview.delta.orders_month_total.amount > 0 ? '+' : '') + overview.delta.orders_month_total.percent.toFixed(1).replace(/\.0$/, '') + '%' : 'Không đổi' }} so với tháng trước
          </div>
        </article>
        <article class="rounded-2xl border border-brand-100 bg-white p-3">
          <div class="text-sm font-medium text-slate-500">Lợi nhuận tháng này</div>
          <div class="mt-0.5 text-lg font-semibold text-slate-900">{{ formatMoney(overview.orders_month?.profit) }}</div>
          <div v-if="overview.delta?.orders_month_profit" :class="['mt-1 text-xs', overview.delta.orders_month_profit.amount > 0 ? 'text-brand-600' : (overview.delta.orders_month_profit.amount < 0 ? 'text-rose-600' : 'text-slate-500')]">
            {{ (overview.delta.orders_month_profit.percent !== null && overview.delta.orders_month_profit.percent !== undefined) ? (overview.delta.orders_month_profit.amount > 0 ? '+' : '') + overview.delta.orders_month_profit.percent.toFixed(1).replace(/\.0$/, '') + '%' : 'Không đổi' }} so với tháng trước
          </div>
        </article>
        <article class="rounded-2xl border border-brand-100 bg-white p-3">
          <div class="text-sm font-medium text-slate-500">Doanh thu hôm nay</div>
          <div class="mt-0.5 text-lg font-semibold text-slate-900">{{ formatMoney(overview.orders_today?.total_amount) }}</div>
          <div v-if="overview.delta?.orders_today_total" :class="['mt-1 text-xs', overview.delta.orders_today_total.amount > 0 ? 'text-brand-600' : (overview.delta.orders_today_total.amount < 0 ? 'text-rose-600' : 'text-slate-500')]">
            {{ (overview.delta.orders_today_total.percent !== null && overview.delta.orders_today_total.percent !== undefined) ? (overview.delta.orders_today_total.amount > 0 ? '+' : '') + overview.delta.orders_today_total.percent.toFixed(1).replace(/\.0$/, '') + '%' : 'Không đổi' }} so với hôm qua
          </div>
        </article>
        <article class="rounded-2xl border border-teal-100 bg-white p-3">
          <div class="text-sm font-medium text-slate-500">Lợi nhuận hôm nay</div>
          <div class="mt-0.5 text-lg font-semibold text-slate-900">{{ formatMoney(overview.orders_today?.profit) }}</div>
          <div v-if="overview.delta?.orders_today_profit" :class="['mt-1 text-xs', overview.delta.orders_today_profit.amount > 0 ? 'text-brand-600' : (overview.delta.orders_today_profit.amount < 0 ? 'text-rose-600' : 'text-slate-500')]">
            {{ (overview.delta.orders_today_profit.percent !== null && overview.delta.orders_today_profit.percent !== undefined) ? (overview.delta.orders_today_profit.amount > 0 ? '+' : '') + overview.delta.orders_today_profit.percent.toFixed(1).replace(/\.0$/, '') + '%' : 'Không đổi' }} so với hôm qua
          </div>
        </article>
        <article class="rounded-2xl border border-amber-100 bg-white p-3">
          <div class="text-sm font-medium text-slate-500">Nhập hàng tháng này</div>
          <div class="mt-0.5 text-lg font-semibold text-slate-900">{{ formatMoney(overview.purchases_month?.total_amount) }}</div>
          <div v-if="overview.delta?.purchases_month_total" :class="['mt-1 text-xs', overview.delta.purchases_month_total.amount > 0 ? 'text-brand-600' : (overview.delta.purchases_month_total.amount < 0 ? 'text-rose-600' : 'text-slate-500')]">
            {{ (overview.delta.purchases_month_total.percent !== null && overview.delta.purchases_month_total.percent !== undefined) ? (overview.delta.purchases_month_total.amount > 0 ? '+' : '') + overview.delta.purchases_month_total.percent.toFixed(1).replace(/\.0$/, '') + '%' : 'Không đổi' }} so với tháng trước
          </div>
        </article>
        <article class="rounded-2xl border border-rose-100 bg-white p-3">
          <div class="text-sm font-medium text-slate-500">Khách hàng còn nợ</div>
          <div class="mt-0.5 text-lg font-semibold text-rose-700">{{ formatMoney(overview.customer_debt) }}</div>
          <div v-if="overview.delta?.customer_debt" :class="['mt-1 text-xs', overview.delta.customer_debt.amount > 0 ? 'text-brand-600' : (overview.delta.customer_debt.amount < 0 ? 'text-rose-600' : 'text-slate-500')]">
            {{ (overview.delta.customer_debt.percent !== null && overview.delta.customer_debt.percent !== undefined) ? (overview.delta.customer_debt.amount > 0 ? '+' : '') + overview.delta.customer_debt.percent.toFixed(1).replace(/\.0$/, '') + '%' : 'Không đổi' }} so với đầu tháng
          </div>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-white p-3">
          <div class="text-sm font-medium text-slate-500">Còn nợ nhà cung cấp</div>
          <div class="mt-0.5 text-lg font-semibold text-violet-700">{{ formatMoney(overview.supplier_debt) }}</div>
          <div v-if="overview.delta?.supplier_debt" :class="['mt-1 text-xs', overview.delta.supplier_debt.amount > 0 ? 'text-brand-600' : (overview.delta.supplier_debt.amount < 0 ? 'text-rose-600' : 'text-slate-500')]">
            {{ (overview.delta.supplier_debt.percent !== null && overview.delta.supplier_debt.percent !== undefined) ? (overview.delta.supplier_debt.amount > 0 ? '+' : '') + overview.delta.supplier_debt.percent.toFixed(1).replace(/\.0$/, '') + '%' : 'Không đổi' }} so với đầu tháng
          </div>
        </article>
      </div>
    </section>
  </section>
</template>
