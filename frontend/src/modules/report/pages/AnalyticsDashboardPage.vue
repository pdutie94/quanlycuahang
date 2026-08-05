<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import { BarChart3, DollarSign, ShoppingCart, TrendingUp } from '@lucide/vue';
import ReportNavButtons from '../components/ReportNavButtons.vue';
import ReportGroupTabs from '../components/ReportGroupTabs.vue';
import { useAnalytics } from '../composables/useAnalytics';
import { useFormat } from '../../../shared/composables/useFormat';

const { data, loading, error, load } = useAnalytics();
const { formatMoney } = useFormat();
const period = ref('30d');
const revenueCanvas = ref<HTMLCanvasElement | null>(null);
const ordersCanvas = ref<HTMLCanvasElement | null>(null);
const isSampleData = ref(false);
let revenueChart: Chart | null = null;
let ordersChart: Chart | null = null;

const periods = [
  { value: '7d', label: '7 ngày' },
  { value: '30d', label: '30 ngày' },
  { value: '90d', label: '90 ngày' },
  { value: '1y', label: '1 năm' },
];

const summary = computed(() => data.value?.summary || {});
const topProducts = computed(() => data.value?.top_products || []);

const sampleLabels = Array.from({ length: 30 }, (_, index) => `${String(index + 1).padStart(2, '0')}/07`);
const sampleCharts = {
  revenue_trend: {
    labels: sampleLabels,
    datasets: [
      { label: 'Doanh thu', color: '#0ea5a4', data: [8200000, 6400000, 7500000, 9100000, 8000000, 10600000, 9400000, 11800000, 10100000, 8700000, 9600000, 12500000, 11100000, 13700000, 12000000, 10200000, 8900000, 9800000, 7400000, 6800000, 8100000, 10900000, 12400000, 11600000, 13200000, 10500000, 9000000, 11700000, 14100000, 12800000] },
      { label: 'Lợi nhuận', color: '#22c55e', data: [2300000, 1700000, 2000000, 2800000, 2500000, 3300000, 2900000, 3800000, 3100000, 2600000, 3000000, 4100000, 3500000, 4400000, 3800000, 3000000, 2600000, 3100000, 2100000, 1900000, 2400000, 3400000, 3900000, 3600000, 4200000, 3200000, 2700000, 3700000, 4500000, 4000000] },
    ],
  },
  orders_trend: {
    labels: sampleLabels,
    datasets: [{ label: 'Số đơn hàng', color: '#0ea5a4', data: [42, 35, 48, 39, 56, 44, 61, 52, 67, 49, 58, 73, 64, 78, 55, 46, 63, 51, 40, 47, 59, 71, 66, 74, 57, 45, 62, 76, 68, 54] }],
  },
};

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
  if (!revenueCanvas.value || !ordersCanvas.value) return;

  destroyCharts();
  const revenueHasData = hasTrendData(charts.revenue_trend);
  const ordersHaveData = hasTrendData(charts.orders_trend);
  const revenueTrend = revenueHasData ? charts.revenue_trend : sampleCharts.revenue_trend;
  const ordersTrend = ordersHaveData ? charts.orders_trend : sampleCharts.orders_trend;
  isSampleData.value = !revenueHasData || !ordersHaveData;

  revenueChart = new Chart(revenueCanvas.value, {
    type: 'line',
    data: {
      labels: revenueTrend.labels || [],
      datasets: lineDatasets(revenueCanvas.value, revenueTrend.datasets, '#0ea5a4'),
    },
    options: moneyChartOptions,
  });

  ordersChart = new Chart(ordersCanvas.value, {
    type: 'line',
    data: {
      labels: ordersTrend.labels || [],
      datasets: lineDatasets(ordersCanvas.value, ordersTrend.datasets, '#0ea5a4'),
    },
    options: { ...chartOptions, plugins: { ...chartOptions.plugins, legend: { ...chartOptions.plugins.legend, display: false } } },
  });
}

async function loadData() {
  try {
    await load(period.value);
    await nextTick();
    renderCharts();
  } catch {
    destroyCharts();
  }
}

watch(period, () => { void loadData(); });
onMounted(() => { void loadData(); });
onBeforeUnmount(destroyCharts);
</script>

<template>
  <section class="space-y-6">
    <header>
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-slate-900">Phân tích bán hàng</h1>
          <p class="text-sm text-slate-500">Theo dõi doanh thu và hiệu suất đơn chưa hủy</p>
        </div>
        <div class="relative grid min-w-[6rem]">
          <select v-model="period" class="app-select col-start-1 row-start-1 h-9">
            <option v-for="item in periods" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
          <span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
        </div>
      </div>
      <ReportNavButtons />
      <ReportGroupTabs group="sales" />
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
      <div v-if="isSampleData" class="-mb-3 flex justify-end">
        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Dữ liệu minh họa</span>
      </div>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><h2 class="mb-4 text-base font-medium">Doanh thu & lợi nhuận</h2><div class="h-72"><canvas ref="revenueCanvas" /></div></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><h2 class="mb-4 text-base font-medium">Số đơn hàng</h2><div class="h-72"><canvas ref="ordersCanvas" /></div></div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="mb-4 text-base font-medium">Top sản phẩm bán chạy</h2>
        <p v-if="!topProducts.length" class="py-6 text-center text-sm text-slate-500">Chưa có dữ liệu sản phẩm trong kỳ này.</p>
        <div v-else class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-600"><th class="pb-2">Sản phẩm</th><th class="pb-2 text-right">Số lượng</th><th class="pb-2 text-right">Doanh thu</th><th class="pb-2 text-right">Lợi nhuận</th></tr></thead><tbody><tr v-for="product in topProducts" :key="product.id" class="border-b border-slate-100"><td class="py-3"><div class="font-medium">{{ product.name }}</div><div class="text-xs text-slate-500">{{ product.code }}</div></td><td class="py-3 text-right">{{ product.total_qty?.toLocaleString() }}</td><td class="py-3 text-right">{{ formatMoney(product.total_revenue) }}</td><td class="py-3 text-right text-green-600">{{ formatMoney(product.total_profit) }}</td></tr></tbody></table></div>
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
