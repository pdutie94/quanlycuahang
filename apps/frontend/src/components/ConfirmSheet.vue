<script setup lang="ts">
import BottomSheet from './BottomSheet.vue'

withDefaults(
  defineProps<{
    modelValue: boolean
    title?: string
    message?: string
    confirmText?: string
    cancelText?: string
    confirmTone?: 'danger' | 'primary'
    busy?: boolean
  }>(),
  {
    title: 'Xác nhận thao tác',
    message: '',
    confirmText: 'Xác nhận',
    cancelText: 'Hủy',
    confirmTone: 'danger',
    busy: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  confirm: []
}>()

function confirm(): void {
  emit('confirm')
}
</script>

<template>
  <BottomSheet :model-value="modelValue" :title="title" :description="message" @update:modelValue="emit('update:modelValue', $event)">
    <template #default="{ close: sheetClose }">
      <div class="grid grid-cols-2 gap-2">
        <button type="button" class="rounded-xl border border-black/15 px-4 py-3 text-sm font-medium" :disabled="busy" @click="sheetClose()">
          {{ cancelText }}
        </button>
        <button
          type="button"
          class="rounded-xl px-4 py-3 text-sm font-medium text-white disabled:opacity-60"
          :class="confirmTone === 'danger' ? 'bg-red-600' : 'bg-pine'"
          :disabled="busy"
          @click="confirm"
        >
          {{ busy ? 'Đang xử lý...' : confirmText }}
        </button>
      </div>
    </template>
  </BottomSheet>
</template>
