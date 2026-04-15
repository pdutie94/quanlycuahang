<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { X } from '@lucide/vue';
import { useFormat } from '../composables/useFormat';

type DiscountType = 'none' | 'fixed' | 'percent';

const props = withDefaults(defineProps<{
  open: boolean;
  initialType?: DiscountType;
  initialValue?: string;
}>(), {
  initialType: 'none',
  initialValue: '',
});

const emit = defineEmits<{
  apply: [type: DiscountType, value: string];
  close: [];
}>();

const { formatMoneyInput } = useFormat();

const draftType = ref<DiscountType>('none');
const draftValue = ref('');

watch(() => props.open, (val) => {
  if (val) {
    draftType.value = props.initialType;
    draftValue.value = props.initialValue ?? '';
  }
});

const suffix = computed(() => {
  if (draftType.value === 'fixed') return 'đ';
  if (draftType.value === 'percent') return '%';
  return '';
});

const sanitizePercent = (value: string): string => {
  let result = '';
  let hasDot = false;
  for (const ch of value) {
    if (ch >= '0' && ch <= '9') { result += ch; continue; }
    if (ch === '.' && !hasDot) { result += ch; hasDot = true; }
  }
  return result;
};

const normalizeDecimal = (value: string | number): string => {
  const raw = String(value ?? '').trim();
  if (!raw || !/^\d+(\.\d+)?$/.test(raw)) return raw;
  return raw.replace(/\.0+$/, '').replace(/(\.\d*?[1-9])0+$/, '$1');
};

const onValueInput = () => {
  if (draftType.value === 'percent') {
    const sanitized = sanitizePercent(draftValue.value).replace(/^\./, '');
    const parsed = parseFloat(sanitized);
    const percent = !isNaN(parsed) && parsed > 0 ? Math.min(parsed, 100) : 0;
    draftValue.value = percent > 0 ? normalizeDecimal(percent) : '';
    return;
  }
  if (draftType.value === 'fixed') {
    draftValue.value = formatMoneyInput(draftValue.value);
    return;
  }
  draftValue.value = '';
};

watch(draftType, () => {
  onValueInput();
});
</script>

<template>
  <Teleport to="body">
    <transition name="app-modal-fade-up">
      <div v-if="open" class="app-modal-overlay app-modal-open" @click.self="$emit('close')">
        <div class="app-modal-sheet-sm">
          <div class="app-modal-header">
            <h2 class="app-modal-title">Giảm giá</h2>
            <button type="button" class="app-modal-close" @click="$emit('close')">
              <X class="h-4 w-4" />
            </button>
          </div>
          <div class="app-modal-body space-y-4">
            <div class="app-segment">
              <button type="button" class="app-segment-item" :class="draftType === 'none' ? 'app-segment-item-active' : ''" @click="draftType = 'none'">Không giảm</button>
              <button type="button" class="app-segment-item" :class="draftType === 'fixed' ? 'app-segment-item-active' : ''" @click="draftType = 'fixed'">Cố định</button>
              <button type="button" class="app-segment-item" :class="draftType === 'percent' ? 'app-segment-item-active' : ''" @click="draftType = 'percent'">Phần trăm</button>
            </div>
            <label v-if="draftType !== 'none'" class="space-y-1">
              <span class="app-label">Giá trị giảm</span>
              <div class="relative">
                <input
                  v-model="draftValue"
                  type="text"
                  v-money-input
                  :data-money-format="draftType === 'percent' ? 'off' : null"
                  :data-money-x1000="draftType === 'percent' ? 'off' : null"
                  class="app-input pr-8 text-right"
                  @input="onValueInput"
                />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">{{ suffix }}</span>
              </div>
            </label>
          </div>
          <div class="app-modal-footer">
            <button type="button" class="app-btn-secondary" @click="$emit('close')">Hủy</button>
            <button type="button" class="app-btn-primary" @click="emit('apply', draftType, draftValue)">Áp dụng</button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>
