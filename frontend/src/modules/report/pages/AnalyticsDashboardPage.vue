<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import Chart from 'chart.js/auto';
import { BarChart3, DollarSign, ShoppingCart, TrendingUp } from '@lucide/vue';
import ReportNavButtons from '../components/ReportNavButtons.vue';
import ReportGroupTabs from '../components/ReportGroupTabs.vue';
import ReportDateFilter, { type ReportDateFilterValue } from '../components/ReportDateFilter.vue';
import { useAnalytics } from '../composables/useAnalytics';
import { useFormat } from '../../../shared/composables/useFormat';

const { data, loading, error, load } = useAnalytics();
const { formatMoney } = useFormat();
const today = new Date().toISOString().slice(0, 10);
const filter = reactive<ReportDateFilterValue>({
  filter_mode: 'month', day: today, month: today.slice(0, 7),
  quarter: String(Math.floor(new Date().getMonth() / 3) + 1), quarter_year: String(new Date().getFullYear()), year: String(new Date().getFullYear()),
});
const revenueCanvas = ref<HTMLCanvasElement | null>(null);
const ordersCanvas = ref<HTMLCanvasElement | null>(null);
let revenueChart: Chart | null = null;
let ordersChart: Chart | null = null;

const summary = computed(() => data.value?.summary || {});

function buildParams() {
  const params: Record<string, string> = { filter_mode: filter.filter_mode };
  if (filter.filter_mode === 'day') params.day = filter.day;
  if (filter.filter_mode === 'month') params.month = filter.month;
  if (filter.filter_mode === 'quarter') { params.quarter = filter.quarter; params.quarter_year = filter.quarter_year; }
  if (filter.filter_mode === 'year') params.year = filter.year;
  return params;
}

function hasTrendData(trend: any) {
  return Boolean(
    Array.isArray(trend?.labels)
      && trend.labels.length
      && Array.isArray(trend?.datasets)
      && trend.datasets.some((dataset: any) => Array.isArray(dataset?.data) && dataset.data.length),
  );
}

function destroyCharts() {
  revenueChart?.destroy();
  ordersChart?.destroy();
  revenueChart = null;
  ordersChart = null;
}

function colorWithAlpha(color: string, alpha: number) {
  const normalized = color.trim();
  const hex = normalized.replace('#', '');

  if (/^[0-9a-f]{3}$/i.test(hex)) {
    const [r, g, b] = hex.split('').map((part) => Number.parseInt(`${part}${part}`, 16));
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  if (/^[0-9a-f]{6}$/i.test(hex)) {
    const r = Number.parseInt(hex.slice(0, 2), 16);
    const g = Number.parseInt(hex.slice(2, 4), 16);
    const b = Number.parseInt(hex.slice(4, 6), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  return color;
}

function createAreaGradient(canvas: HTMLCanvasElement, color: string) {
  const context = canvas.getContext('2d');
  if (!context) return colorWithAlpha(color, 0.14);

  const gradient = context.createLinearGradient(0, 0, 0, canvas.clientHeight || 240);
  gradient.addColorStop(0, colorWithAlpha(color, 0.14));
  gradient.addColorStop(0.35, colorWithAlpha(color, 0.045));
  gradient.addColorStop(0.72, colorWithAlpha(color, 0.012));
  gradient.addColorStop(1, colorWithAlpha(color, 0));
  return gradient;
}

function lineDatasets(canvas: HTMLCanvasElement, datasets: any[], fallbackColor: string) {
  return (datasets || []).map((dataset: any) => {
    const color = dataset.color || fallbackColor;
    return {
      label: dataset.label,
      data: dataset.data || [],
      borderColor: color,
      backgroundColor: createAreaGradient(canvas, color),
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 3,
      fill: true,
      tension: 0.35,
    };
  });
}

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  elements: { line: { capBezierPoints: true } },
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: { usePointStyle: true, boxWidth: 7, boxHeight: 7, padding: 16 },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: '#94a3b8', maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
    },
    y: {
      beginAtZero: true,
      border: { display: false },
      grid: { color: 'rgba(148, 163, 184, 0.16)', drawTicks: false },
      ticks: { color: '#94a3b8', padding: 8 },
    },
  },
};

function formatCompactValue(value: number) {
  if (Math.abs(value) >= 1_000_000_000) return `${(value / 1_000_000_000).toLocaleString('vi-VN', { maximumFractionDigits: 1 })} tỷ`;
  if (Math.abs(value) >= 1_000_000) return `${(value / 1_000_000).toLocaleString('vi-VN', { maximumFractionDigits: 1 })} tr`;
  if (Math.abs(value) >= 1_000) return `${(value / 1_000).toLocaleString('vi-VN', { maximumFractionDigits: 1 })}k`;
  return value.toLocaleString('vi-VN');
}

const moneyChartOptions = {
  ...chartOptions,
  scales: {
    ...chartOptions.scales,
    y: {
      ...chartOptions.scales.y,
      ticks: {
        ...chartOptions.scales.y.ticks,
        callback: (value: any) => formatCompactValue(Number(value)),
      },
    },
  },
};

function renderCharts() {
  const charts = data.value?.charts || {};
  destroyCharts();

  if (revenueCanvas.value && hasTrendData(charts.revenue_trend)) revenueChart = new Chart(revenueCanvas.value, {
    type: 'line',
    data: {
      labels: charts.revenue_trend.labels || [],
      datasets: lineDatasets(revenueCanvas.value, charts.revenue_trend.datasets, '#0ea5a4'),
    },
    options: moneyChartOptions,
  });

  if (ordersCanvas.value && hasTrendData(charts.orders_trend)) ordersChart = new Chart(ordersCanvas.value, {
    type: 'line',
    data: {
      labels: charts.orders_trend.labels || [],
      datasets: lineDatasets(ordersCanvas.value, charts.orders_trend.datasets, '#0ea5a4'),
    },
    options: { ...chartOptions, plugins: { ...chartOptions.plugins, legend: { ...chartOptions.plugins.legend, display: false } } },
  });
}

async function loadData() {
  try {
    await load(buildParams());
    await nextTick();
    renderCharts();
  } catch {
    destroyCharts();
  }
}

onMounted(() => { void loadData(); });
onBeforeUnmount(destroyCharts);
</script>

<template>
  <section class="space-y-6">
    <header>
      <div>
        <div>
          <h1 class="text-xl font-semibold text-slate-900">Phân tích bán hàng</h1>
          <p class="text-sm text-slate-500">Theo dõi doanh thu và hiệu suất đơn chưa hủy</p>
        </div>
      </div>
      <ReportNavButtons />
      <ReportGroupTabs group="sales" />
      <ReportDateFilter :model-value="filter" :loading="loading" @update:model-value="Object.assign(filter, $event)" @apply="loadData" />
    </header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center gap-3"><DollarSign class="h-5 w-5 text-blue-600" /><div><p class="text-sm text-slate-500">Doanh thu</p><p class="text-lg font-semibold">{{ formatMoney(summary.total_revenue) }}</p></div></div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center gap-3"><TrendingUp class="h-5 w-5 text-green-600" /><div><p class="text-sm text-slate-500">Lợi nhuận</p><p class="text-lg font-semibold">{{ formatMoney(summary.total_profit) }}</p></div></div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center gap-3"><ShoppingCart class="h-5 w-5 text-amber-600" /><div><p class="text-sm text-slate-500">Số đơn hàng</p><p class="text-lg font-semibold">{{ summary.total_orders?.toLocaleString() || 0 }}</p></div></div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center gap-3"><BarChart3 class="h-5 w-5 text-purple-600" /><div><p class="text-sm text-slate-500">Tỷ suất lợi nhuận</p><p class="text-lg font-semibold">{{ summary.profit_margin || 0 }}%</p></div></div></div>
    </div>

    <div v-if="loading" class="flex justify-center py-12"><div class="h-8 w-8 animate-spin rounded-full border-2 border-brand-500 border-t-transparent" /></div>
    <div v-else-if="error" class="rounded-lg bg-red-50 p-4 text-center text-red-600">{{ error }}</div>
    <template v-else>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><h2 class="mb-4 text-base font-medium">Doanh thu & lợi nhuận</h2><div v-if="hasTrendData(data?.charts?.revenue_trend)" class="h-72"><canvas ref="revenueCanvas" /></div><p v-else class="flex h-72 items-center justify-center text-sm text-slate-500">Chưa có dữ liệu trong kỳ đã chọn.</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><h2 class="mb-4 text-base font-medium">Số đơn hàng</h2><div v-if="hasTrendData(data?.charts?.orders_trend)" class="h-72"><canvas ref="ordersCanvas" /></div><p v-else class="flex h-72 items-center justify-center text-sm text-slate-500">Chưa có dữ liệu trong kỳ đã chọn.</p></div>
      </div>
    </template>
  </section>
</template>

<style scoped>
:deep(.h-72) {
  height: 150px;
  max-height: 170px;
}

@media (min-width: 640px) {
  :deep(.h-72) {
    height: 170px;
  }
}
</style>
