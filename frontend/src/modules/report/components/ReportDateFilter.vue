<script setup lang="ts">
export type ReportDateFilterValue = {
  filter_mode: 'day' | 'month' | 'quarter' | 'year';
  day: string;
  month: string;
  quarter: string;
  quarter_year: string;
  year: string;
};

const props = defineProps<{ modelValue: ReportDateFilterValue; loading?: boolean }>();
const emit = defineEmits<{ (event: 'update:modelValue', value: ReportDateFilterValue): void; (event: 'apply'): void }>();

function patch(values: Partial<ReportDateFilterValue>) {
  emit('update:modelValue', { ...props.modelValue, ...values });
}
</script>

<template>
  <form class="app-card mt-3 flex flex-col gap-2" @submit.prevent="emit('apply')">
    <div class="flex flex-wrap gap-2">
      <button v-for="mode in [['day', 'Ngày'], ['month', 'Tháng'], ['quarter', 'Quý'], ['year', 'Năm']]" :key="mode[0]" type="button" class="inline-flex h-9 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100" :class="{ 'border-brand-600 bg-brand-50 text-brand-700': modelValue.filter_mode === mode[0] }" @click="patch({ filter_mode: mode[0] as ReportDateFilterValue['filter_mode'] })">{{ mode[1] }}</button>
    </div>
    <div class="flex flex-wrap items-end gap-2">
      <div v-if="modelValue.filter_mode === 'day'"><label class="app-label">Chọn ngày</label><input :value="modelValue.day" type="date" class="h-10 min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm" @input="patch({ day: ($event.target as HTMLInputElement).value })" /></div>
      <div v-if="modelValue.filter_mode === 'month'"><label class="app-label">Chọn tháng</label><input :value="modelValue.month" type="month" class="h-10 min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm" @input="patch({ month: ($event.target as HTMLInputElement).value })" /></div>
      <template v-if="modelValue.filter_mode === 'quarter'"><div><label class="app-label">Quý</label><select :value="modelValue.quarter" class="app-select min-w-[9rem]" @change="patch({ quarter: ($event.target as HTMLSelectElement).value })"><option value="1">Quý 1</option><option value="2">Quý 2</option><option value="3">Quý 3</option><option value="4">Quý 4</option></select></div><div><label class="app-label">Năm</label><input :value="modelValue.quarter_year" type="number" min="2000" max="2100" class="h-10 min-w-[9rem] rounded-xl border border-slate-300 px-3 text-sm" @input="patch({ quarter_year: ($event.target as HTMLInputElement).value })" /></div></template>
      <div v-if="modelValue.filter_mode === 'year'"><label class="app-label">Chọn năm</label><input :value="modelValue.year" type="number" min="2000" max="2100" class="h-10 min-w-[9rem] rounded-xl border border-slate-300 px-3 text-sm" @input="patch({ year: ($event.target as HTMLInputElement).value })" /></div>
      <slot name="actions" />
      <button type="submit" class="h-10 rounded-xl bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="loading">Lọc</button>
    </div>
  </form>
</template>
