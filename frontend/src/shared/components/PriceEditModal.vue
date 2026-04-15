<script setup lang="ts">
import { X } from '@lucide/vue';
import { useFormat } from '../composables/useFormat';

defineProps<{
  open: boolean;
  productLabel?: string;
  basePrice: number;
  currentPrice: number;
}>();

defineEmits<{
  apply: [];
  close: [];
}>();

const draftValue = defineModel<string>({ default: '' });
const { formatMoney } = useFormat();
</script>

<template>
  <Teleport to="body">
    <transition name="app-modal-fade-up">
      <div v-if="open" class="app-modal-overlay app-modal-open" @click.self="$emit('close')">
        <div class="app-modal-sheet-sm">
          <div class="app-modal-header">
            <h2 class="app-modal-title">Chỉnh đơn giá</h2>
            <button type="button" class="app-modal-close" @click="$emit('close')">
              <X class="h-4 w-4" />
            </button>
          </div>
          <div class="app-modal-body space-y-4">
            <div v-if="productLabel" class="text-sm text-slate-600">{{ productLabel }}</div>
            <div class="space-y-1 rounded-lg bg-slate-50 p-2 text-sm text-slate-600">
              <div class="flex items-center justify-between">
                <span>Giá gốc</span>
                <span class="font-medium text-slate-900">{{ formatMoney(basePrice) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Đơn giá hiện tại</span>
                <span class="font-medium text-slate-900">{{ formatMoney(currentPrice) }}</span>
              </div>
            </div>
            <label class="space-y-1">
              <span class="app-label">Đơn giá mới</span>
              <div class="relative">
                <input v-model="draftValue" type="text" v-money-input class="app-input pr-8 text-right font-medium text-slate-900" />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
              </div>
            </label>
          </div>
          <div class="app-modal-footer">
            <button type="button" class="app-btn-secondary" @click="$emit('close')">Hủy</button>
            <button type="button" class="app-btn-primary" @click="$emit('apply')">Áp dụng</button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>
