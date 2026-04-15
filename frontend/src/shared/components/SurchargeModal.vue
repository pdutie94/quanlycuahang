<script setup lang="ts">
import { ref, watch } from 'vue';
import { X } from '@lucide/vue';

const props = withDefaults(defineProps<{
  open: boolean;
  title?: string;
  initialValue?: string;
}>(), {
  title: 'Phụ thu',
  initialValue: '',
});

const emit = defineEmits<{
  apply: [value: string];
  close: [];
}>();

const draftValue = ref('');

watch(() => props.open, (val) => {
  if (val) draftValue.value = props.initialValue ?? '';
});
</script>

<template>
  <Teleport to="body">
    <transition name="app-modal-fade-up">
      <div v-if="open" class="app-modal-overlay app-modal-open" @click.self="$emit('close')">
        <div class="app-modal-sheet-sm">
          <div class="app-modal-header">
            <h2 class="app-modal-title">{{ title }}</h2>
            <button type="button" class="app-modal-close" @click="$emit('close')">
              <X class="h-4 w-4" />
            </button>
          </div>
          <div class="app-modal-body space-y-4">
            <label class="space-y-1">
              <span class="app-label">Số tiền phụ thu</span>
              <div class="relative">
                <input v-model="draftValue" type="text" v-money-input class="app-input pr-8 text-right" />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
              </div>
            </label>
          </div>
          <div class="app-modal-footer">
            <button type="button" class="app-btn-secondary" @click="$emit('close')">Hủy</button>
            <button type="button" class="app-btn-primary" @click="emit('apply', draftValue)">Áp dụng</button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>
