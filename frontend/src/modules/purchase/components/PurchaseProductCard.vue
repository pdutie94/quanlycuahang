<template>
    <div class="flex flex-row items-center gap-2 sm:gap-3">
      <div class="flex-1 min-w-0">
        <div class="font-medium text-sm truncate">{{ item.name }}</div>
      </div>
      <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-700" @click="$emit('remove')">Xóa</button>
    </div>

    <div class="mt-1 grid grid-cols-2 md:grid-cols-3 gap-2">
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-0.5">Số lượng</label>
        <div class="relative">
          <input type="number" v-model.number="localQty" @input="onQtyInput" class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-10" />
          <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400">{{ item.unit_name }}</span>
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-0.5">Giá nhập</label>
        <div class="relative">
          <input type="text" v-money-input min="0" v-model.number="localPrice" @input="onPriceInput" class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-7" />
          <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400">đ</span>
        </div>
      </div>
      <div class="col-span-2 md:col-span-1">
        <label class="block text-xs font-medium text-slate-600 mb-0.5">Thành tiền</label>
        <div class="relative">
          <input type="text" v-money-input min="0" v-model.number="localTotal" @input="onTotalInput" class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-7" />
          <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400">đ</span>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
function formatMoney(val) {
  if (val === '' || val === null || val === undefined) return '';
  const num = Number(val) || 0;
  return num > 0 ? num.toLocaleString('vi-VN') : '';
}
const props = defineProps({
  item: { type: Object, required: true }
});
const emit = defineEmits(['update', 'remove']);

const localQty = ref(props.item.qty || 1);
const localPrice = ref(props.item.price || 0);
const localTotal = ref((props.item.qty || 1) * (props.item.price || 0));

// Khi sửa số lượng
function onQtyInput() {
  if (localQty.value < 1) localQty.value = 1;
  localTotal.value = localQty.value * localPrice.value;
  emitUpdate();
}
function onPriceInput(e) {
  let val = Number(e.target.value.replace(/[^\d]/g, ''));
  if (val < 1000 && val !== 0) {
    // Không cho nhập giá nhỏ hơn 1000
    localPrice.value = 1000;
  } else {
    localPrice.value = val;
  }
  localTotal.value = localQty.value * localPrice.value;
  emitUpdate();
}
function onTotalInput(e) {
  let val = Number(e.target.value.replace(/[^\d]/g, ''));
  if (val < 1000 && val !== 0) {
    localTotal.value = 1000;
  } else {
    localTotal.value = val;
  }
  if (localQty.value > 0) {
    localPrice.value = Math.floor(localTotal.value / localQty.value);
    emitUpdate();
  }
}
function emitUpdate() {
  emit('update', {
    ...props.item,
    qty: localQty.value,
    price: localPrice.value,
    total: localTotal.value
  });
}
// Đồng bộ khi props thay đổi
watch(() => props.item, (val) => {
  localQty.value = val.qty || 1;
  localPrice.value = val.price || 0;
  localTotal.value = (val.qty || 1) * (val.price || 0);
}, { deep: true });
</script>