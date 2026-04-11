<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { CirclePlus, ClipboardList, Minus, Pencil, Plus, RefreshCw, Tag, Users, X } from '@lucide/vue';
import { useRoute, useRouter } from 'vue-router';
import { useOrderForm } from '../composables/useOrderForm';
import { useToast } from '../../../shared/composables/useToast';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const {
  form,
  order,
  productUnits,
  rows,
  manualItems,
  summary,
  rowDisplayMap,
  loadBootstrap,
  loadEdit,
  addRow,
  removeRow,
  addManualItem,
  removeManualItem,
  syncCustomerFields,
  resetState,
  customers,
  submitCreate,
  submitUpdate,
  bootstrapLoading,
  bootstrapError,
  detailLoading,
  detailError,
  createLoading,
  createError,
  updateLoading,
  updateError
} = useOrderForm();

const showProductSelector = ref(false);
const showCustomerModal = ref(false);
const showPriceModal = ref(false);
const showManualItemModal = ref(false);
const showDiscountModal = ref(false);
const showSurchargeModal = ref(false);
const productKeyword = ref('');
const customerKeyword = ref('');
const activeRowIndex = ref(null);
const editingPriceRowIndex = ref(null);
const priceDraftValue = ref('');
const editingManualIndex = ref(null);
const manualItemDraft = ref({
  item_name: '',
  unit_name: '',
  qty: '1',
  price_buy: '',
  price_sell: ''
});
const pendingCustomerId = ref('');
const customerMode = ref('guest');
const customerNameInput = ref(null);
const discountDraftType = ref('none');
const discountDraftValue = ref('');
const surchargeDraftValue = ref('');

const formatter = new Intl.NumberFormat('vi-VN');

const isEdit = computed(() => Number(route.params.id || 0) > 0);
const pageTitle = computed(() => (isEdit.value ? 'Sửa đơn hàng' : 'Tạo đơn hàng'));
const backTarget = computed(() => (isEdit.value ? { name: 'orders.detail', params: { id: route.params.id } } : '/orders'));
const loading = computed(() => bootstrapLoading.value || (isEdit.value && detailLoading.value));
const saving = computed(() => createLoading.value || updateLoading.value);

import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime, parseAmount } = useFormat();

const formatMoneyInput = (value, allowEmpty = true) => {
  const amount = parseAmount(value);
  if (amount <= 0) {
    return allowEmpty ? '' : '0';
  }

  return formatter.format(amount);
};

const formatPriceInput = (value, allowEmpty = true) => {
  const amount = parsePriceShorthand(value);
  if (amount <= 0) {
    return allowEmpty ? '' : '0';
  }

  return formatter.format(amount);
};

const parsePriceShorthand = (raw) => {
  const str = String(raw ?? '').trim();
  if (!str) return 0;

  const dotIdx = str.indexOf('.');
  if (dotIdx !== -1 && (str.match(/\./g) || []).length === 1) {
    const afterDot = str.slice(dotIdx + 1).replace(/[^0-9]/g, '');
    if (afterDot.length < 3) {
      const num = parseFloat(str.replace(/[^0-9.]/g, ''));
      if (!Number.isNaN(num) && num > 0) {
        return num < 1000 ? Math.round(num * 1000) : Math.round(num);
      }
      return 0;
    }
  }

  const digits = str.replace(/[^0-9]/g, '');
  if (!digits) {
    return 0;
  }

  const num = Number(digits);
  return num > 0 && num < 1000 ? num * 1000 : num;
};

const sanitizePercentInput = (value) => {
  let result = '';
  let hasDot = false;

  for (const ch of String(value ?? '')) {
    if (ch >= '0' && ch <= '9') {
      result += ch;
      continue;
    }

    if (ch === '.' && !hasDot) {
      result += ch;
      hasDot = true;
    }
  }

  return result;
};

const parsePercentValue = (value) => {
  const sanitized = sanitizePercentInput(value).replace(/^\./, '');
  if (!sanitized) {
    return 0;
  }

  const parsed = Number.parseFloat(sanitized);
  if (Number.isNaN(parsed) || parsed <= 0) {
    return 0;
  }

  return Math.min(parsed, 100);
};

const formatNumber = (value, maximumFractionDigits = 3) => Number(value || 0).toLocaleString('vi-VN', {
  minimumFractionDigits: 0,
  maximumFractionDigits
});

const normalizeDecimalDisplay = (value) => {
  const raw = String(value ?? '').trim();
  if (!raw) {
    return '';
  }

  if (!/^\d+(\.\d+)?$/.test(raw)) {
    return raw;
  }

  return raw.replace(/\.0+$/, '').replace(/(\.\d*?[1-9])0+$/, '$1');
};

const roundDownThousand = (value) => {
  const amount = typeof value === 'number' ? value : parseAmount(value);
  if (amount <= 0) return 0;
  const normalizedAmount = Math.round(amount);
  const remainder = normalizedAmount % 1000;
  const baseAmount = normalizedAmount - remainder;
  // <=700 làm tròn xuống, >700 làm tròn lên
  if (remainder > 700) return baseAmount + 1000;
  return baseAmount;
};

const manualRoundTotal = () => {
  // Không reset discount/surcharge, chỉ làm tròn payment_amount
  form.value.payment_amount = formatMoneyInput(roundDownThousand(finalTotal.value));
};

const discountDraftSuffix = computed(() => {
  if (discountDraftType.value === 'fixed') {
    return 'đ';
  }

  if (discountDraftType.value === 'percent') {
    return '%';
  }

  return '';
});

const discountAmount = computed(() => {
  const gross = Number(summary.value.gross || 0);
  const value = form.value.discount_value;

  if (form.value.discount_type === 'fixed') {
    return Math.min(parsePriceShorthand(value), gross);
  }

  if (form.value.discount_type === 'percent') {
    const percent = parsePercentValue(value);
    return Math.round(gross * percent / 100);
  }

  return 0;
});

const surchargeValue = computed(() => parseAmount(form.value.surcharge_amount));

const finalTotal = computed(() => {
  const total = Number(summary.value.gross || 0) - discountAmount.value + surchargeValue.value;
  return roundDownThousand(total < 0 ? 0 : total);
});

const filteredUnits = computed(() => {
  const keyword = String(productKeyword.value || '').trim().toLowerCase();
  if (!keyword) {
    return productUnits.value.slice(0, 80);
  }

  return productUnits.value.filter((unit) => {
    const haystack = [unit.product_name, unit.product_code, unit.unit_name]
      .map((value) => String(value || '').toLowerCase())
      .join(' ');

    return haystack.includes(keyword);
  }).slice(0, 80);
});

const filteredCustomers = computed(() => {
  const keyword = String(customerKeyword.value || '').trim().toLowerCase();
  if (!keyword) {
    return customers.value;
  }

  return customers.value.filter((customer) => {
    const haystack = [customer.name, customer.phone, customer.address]
      .map((value) => String(value || '').toLowerCase())
      .join(' ');

    return haystack.includes(keyword);
  });
});

const selectedCustomer = computed(() => customers.value.find((customer) => Number(customer.id) === Number(form.value.customer_id || 0)) || null);

const selectedCustomerSummary = computed(() => {
  if (!selectedCustomer.value) {
    return {
      title: 'Chưa chọn khách',
      meta: 'Nhấn để chọn khách từ danh sách'
    };
  }

  return {
    title: [selectedCustomer.value.name, selectedCustomer.value.phone].filter(Boolean).join(' - ') || selectedCustomer.value.name || 'Chưa chọn khách',
    meta: selectedCustomer.value.address || ''
  };
});

const editingPriceRow = computed(() => {
  const index = Number(editingPriceRowIndex.value);
  if (index < 0 || index >= rows.value.length) {
    return null;
  }

  return rows.value[index] || null;
});

const editingPriceUnit = computed(() => {
  if (!editingPriceRow.value) {
    return null;
  }

  return rowDisplayMap.value.get(String(editingPriceRow.value.product_unit_id)) || null;
});

const editingPriceLabel = computed(() => {
  if (!editingPriceUnit.value) {
    return '';
  }

  return `${editingPriceUnit.value.product_name}${editingPriceUnit.value.unit_name ? ` - ${editingPriceUnit.value.unit_name}` : ''}`;
});

const hasAnyItems = computed(() => rows.value.length > 0 || manualItems.value.length > 0);

const getUnitDisplay = (row) => rowDisplayMap.value.get(String(row.product_unit_id)) || null;

const getRowStep = (row) => {
  const unit = getUnitDisplay(row);
  const allowFraction = Number(unit?.allow_fraction || 0) === 1;
  const minStep = Number(unit?.min_step || 1);

  if (!allowFraction) {
    return 1;
  }

  if (!Number.isFinite(minStep) || minStep <= 0) {
    return 1;
  }

  return minStep;
};

const formatQtyValue = (value) => Number(value || 0).toFixed(4).replace(/\.?0+$/, '');
const getRowPriceClass = (row) => {
  const basePrice = Number(getUnitDisplay(row)?.price_sell || 0);
  return parseAmount(row?.price || 0) < basePrice ? 'text-amber-700' : 'text-slate-900';
};

const normalizeRowQty = (row) => {
  const step = getRowStep(row);
  const currentQty = Number(row.qty || 0);

  if (!Number.isFinite(currentQty) || currentQty <= 0) {
    row.qty = formatQtyValue(step);
    return;
  }

  if (step === 1) {
    row.qty = formatQtyValue(Math.max(1, Math.round(currentQty)));
    return;
  }

  const normalizedQty = Math.max(step, Math.round(currentQty / step) * step);
  row.qty = formatQtyValue(normalizedQty);
};

const increaseRowQty = (row) => {
  const step = getRowStep(row);
  row.qty = formatQtyValue(Number(row.qty || 0) + step);
};

const decreaseRowQty = (row) => {
  const step = getRowStep(row);
  row.qty = formatQtyValue(Math.max(step, Number(row.qty || 0) - step));
};

const detectManualQtyPrecision = (value) => {
  const rawValue = String(value ?? '').trim();

  if (!rawValue || !rawValue.includes('.')) {
    return 0;
  }

  const fractionalPart = rawValue.split('.')[1].replace(/[^0-9]/g, '').replace(/0+$/, '');
  return fractionalPart.length;
};

const getManualQtyPrecision = (item) => {
  const storedPrecision = Number(item?.qty_precision);
  if (Number.isInteger(storedPrecision) && storedPrecision >= 0) {
    return storedPrecision;
  }

  return detectManualQtyPrecision(item?.qty);
};

const getManualQtyStep = (item) => 1 / (10 ** getManualQtyPrecision(item));
const getManualQtyMin = (item) => getManualQtyStep(item);

const roundManualQtyByPrecision = (value, precision) => {
  const factor = 10 ** precision;
  return Math.round(Number(value || 0) * factor) / factor;
};

const formatManualQtyValue = (value, precision = 4) => Number(value || 0).toFixed(precision).replace(/\.?0+$/, '');

const normalizeManualQty = (item) => {
  const typedPrecision = detectManualQtyPrecision(item.qty);
  item.qty_precision = typedPrecision;

  const currentQty = Number(item.qty || 0);
  const precision = getManualQtyPrecision(item);
  const step = getManualQtyStep(item);
  const minQty = getManualQtyMin(item);

  if (!Number.isFinite(currentQty) || currentQty <= 0) {
    item.qty_precision = 0;
    item.qty = formatManualQtyValue(1, 0);
    return;
  }

  const normalizedQty = Math.max(minQty, roundManualQtyByPrecision(Math.round(currentQty / step) * step, precision));
  item.qty = formatManualQtyValue(normalizedQty, Math.max(precision, 0));
};

const increaseManualQty = (item) => {
  const precision = getManualQtyPrecision(item);
  const step = getManualQtyStep(item);
  const nextQty = roundManualQtyByPrecision(Number(item.qty || 0) + step, precision);
  item.qty = formatManualQtyValue(nextQty, Math.max(precision, 0));
};

const decreaseManualQty = (item) => {
  const precision = getManualQtyPrecision(item);
  const step = getManualQtyStep(item);
  const minQty = getManualQtyMin(item);
  const nextQty = Math.max(minQty, roundManualQtyByPrecision(Number(item.qty || 0) - step, precision));
  item.qty = formatManualQtyValue(nextQty, Math.max(precision, 0));
};

const formatMoneyField = (obj, field) => {
  obj[field] = formatMoneyInput(obj[field]);
};

const formatMoneyFieldRequired = (obj, field) => {
  obj[field] = formatMoneyInput(obj[field], false);
};

const formatPriceField = (obj, field, allowEmpty = true) => {
  obj[field] = formatPriceInput(obj[field], allowEmpty);
};

const hasValue = (value) => value !== null && value !== undefined && String(value) !== '';

const parseRowPrice = (value) => parsePriceShorthand(value);

const resetManualDraft = () => {
  manualItemDraft.value = {
    item_name: '',
    unit_name: '',
    qty: '1',
    price_buy: '',
    price_sell: ''
  };
};

const syncPaymentAmount = () => {
  if (isEdit.value) {
    return;
  }

  if (form.value.payment_status !== 'pay') {
    form.value.payment_amount = '';
    return;
  }

  form.value.payment_amount = finalTotal.value > 0 ? formatMoneyInput(finalTotal.value) : '';
};

const initializeUiState = () => {
  customerMode.value = form.value.customer_id ? 'existing' : (form.value.customer_name ? 'new' : 'guest');
  pendingCustomerId.value = form.value.customer_id ? String(form.value.customer_id) : '';
  syncPaymentAmount();
};

watch(customerMode, async (mode) => {
  if (mode === 'existing') {
    pendingCustomerId.value = form.value.customer_id ? String(form.value.customer_id) : '';
    if (form.value.customer_id) {
      syncCustomerFields();
    } else {
      form.value.customer_name = '';
      form.value.customer_phone = '';
      form.value.customer_address = '';
    }
    return;
  }

  form.value.customer_id = '';
  pendingCustomerId.value = '';

  if (mode === 'guest') {
    form.value.customer_name = '';
    form.value.customer_phone = '';
    form.value.customer_address = '';
    return;
  }

  form.value.customer_name = '';
  form.value.customer_phone = '';
  form.value.customer_address = '';
  await nextTick();
  customerNameInput.value?.focus();
});

watch([() => form.value.payment_status, finalTotal], () => {
  syncPaymentAmount();
}, { immediate: true });

const openProductSelector = (rowIndex = null) => {
  activeRowIndex.value = rowIndex;
  productKeyword.value = '';
  showProductSelector.value = true;
};

const closeProductSelector = () => {
  showProductSelector.value = false;
  activeRowIndex.value = null;
};

const selectProductUnit = (unit) => {
  const nextUnitId = String(unit.id);
  const nextPrice = String(Math.round(Number(unit.price_sell || 0)));
  const nextQty = Number(unit.allow_fraction || 0) === 1 ? String(Number(unit.min_step || 1) || 1) : '1';

  if (activeRowIndex.value === null || activeRowIndex.value < 0 || activeRowIndex.value >= rows.value.length) {
    addRow({
      product_unit_id: nextUnitId,
      qty: nextQty,
      price: nextPrice
    });
  } else {
    const row = rows.value[activeRowIndex.value];
    row.product_unit_id = nextUnitId;
    row.qty = row.qty || nextQty;
    row.price = nextPrice;
    normalizeRowQty(row);
  }

  closeProductSelector();
};

const openCustomerModal = () => {
  customerKeyword.value = '';
  pendingCustomerId.value = form.value.customer_id ? String(form.value.customer_id) : '';
  showCustomerModal.value = true;
};

const closeCustomerModal = () => {
  showCustomerModal.value = false;
};

const applySelectedCustomer = () => {
  if (!pendingCustomerId.value) {
    closeCustomerModal();
    return;
  }

  form.value.customer_id = String(pendingCustomerId.value);
  syncCustomerFields();
  closeCustomerModal();
};

const openPriceModal = (index) => {
  const row = rows.value[index];
  if (!row) {
    return;
  }

  editingPriceRowIndex.value = index;
  priceDraftValue.value = formatMoneyInput(row.price, false);
  showPriceModal.value = true;
};

const closePriceModal = () => {
  showPriceModal.value = false;
  editingPriceRowIndex.value = null;
  priceDraftValue.value = '';
};

const applyPrice = () => {
  if (!editingPriceRow.value) {
    closePriceModal();
    return;
  }

  const basePrice = Number(editingPriceUnit.value?.price_sell || 0);
  let nextPrice = parsePriceShorthand(priceDraftValue.value);
  if (nextPrice <= 0) {
    nextPrice = basePrice;
  }

  editingPriceRow.value.price = String(nextPrice);
  closePriceModal();
};

const openManualItemModal = (index = null) => {
  editingManualIndex.value = index;

  if (index === null || index < 0 || index >= manualItems.value.length) {
    resetManualDraft();
  } else {
    const source = manualItems.value[index];
    manualItemDraft.value = {
      item_name: source.item_name || '',
      unit_name: source.unit_name || '',
      qty: String(source.qty || '1'),
      price_buy: hasValue(source.price_buy) ? formatMoneyInput(source.price_buy, false) : '',
      price_sell: hasValue(source.price_sell) ? formatMoneyInput(source.price_sell, false) : ''
    };
  }

  showManualItemModal.value = true;
};

const closeManualItemModal = () => {
  showManualItemModal.value = false;
  editingManualIndex.value = null;
};

const saveManualItem = () => {
  const normalizedItem = {
    item_name: String(manualItemDraft.value.item_name || '').trim(),
    unit_name: String(manualItemDraft.value.unit_name || '').trim(),
    qty: String(Number(manualItemDraft.value.qty || 0) > 0 ? manualItemDraft.value.qty : '1'),
    price_buy: String(parsePriceShorthand(manualItemDraft.value.price_buy || '0')),
    price_sell: String(parsePriceShorthand(manualItemDraft.value.price_sell || '0'))
  };

  if (!normalizedItem.item_name) {
    toast.error('Vui lòng nhập tên sản phẩm tự do.');
    return;
  }

  if (Number(normalizedItem.qty || 0) <= 0 || parseAmount(normalizedItem.price_sell) < 0) {
    toast.error('Sản phẩm tự do phải có số lượng và giá bán hợp lệ.');
    return;
  }

  if (editingManualIndex.value === null) {
    addManualItem(normalizedItem);
  } else {
    manualItems.value[editingManualIndex.value] = normalizedItem;
  }

  closeManualItemModal();
};

const onDiscountDraftValueInput = () => {
  if (discountDraftType.value === 'percent') {
    const percent = parsePercentValue(discountDraftValue.value);
    discountDraftValue.value = percent > 0 ? normalizeDecimalDisplay(percent) : '';
    return;
  }

  if (discountDraftType.value === 'fixed') {
    discountDraftValue.value = formatMoneyInput(discountDraftValue.value);
    return;
  }

  discountDraftValue.value = '';
};

const openDiscountModal = () => {
  discountDraftType.value = form.value.discount_type || 'none';
  if (discountDraftType.value === 'percent') {
    discountDraftValue.value = normalizeDecimalDisplay(form.value.discount_value);
  } else if (discountDraftType.value === 'fixed') {
    discountDraftValue.value = formatMoneyInput(form.value.discount_value);
  } else {
    discountDraftValue.value = '';
  }
  showDiscountModal.value = true;
};

const closeDiscountModal = () => {
  showDiscountModal.value = false;
};

const applyDiscount = () => {
  form.value.discount_type = discountDraftType.value;

  if (discountDraftType.value === 'fixed') {
    const fixedValue = parsePriceShorthand(discountDraftValue.value);
    form.value.discount_value = fixedValue > 0 ? String(fixedValue) : '';
  } else if (discountDraftType.value === 'percent') {
    const percentValue = parsePercentValue(discountDraftValue.value);
    form.value.discount_value = percentValue > 0 ? normalizeDecimalDisplay(percentValue) : '';
  } else {
    form.value.discount_value = '';
  }

  closeDiscountModal();
};

const openSurchargeModal = () => {
  surchargeDraftValue.value = formatMoneyInput(form.value.surcharge_amount);
  showSurchargeModal.value = true;
};

const closeSurchargeModal = () => {
  showSurchargeModal.value = false;
};

const applySurcharge = () => {
  form.value.surcharge_amount = surchargeDraftValue.value;
  closeSurchargeModal();
};

const resetTotals = () => {
  form.value.discount_type = 'none';
  form.value.discount_value = '';
  form.value.surcharge_amount = '';
};

const validateBeforeSubmit = async () => {
  if (!hasAnyItems.value) {
    toast.error('Chưa có sản phẩm nào.');
    return false;
  }

  const hasInvalidRow = rows.value.some((row) => !row.product_unit_id || Number(row.qty || 0) <= 0 || String(row.price ?? '').trim() === '' || parseRowPrice(row.price) < 0);
  if (hasInvalidRow) {
    toast.error('Vui lòng chọn sản phẩm và nhập số lượng, đơn giá hợp lệ.');
    return false;
  }

  const hasInvalidManualItem = manualItems.value.some((item) => !String(item.item_name || '').trim() || Number(item.qty || 0) <= 0 || String(item.price_sell ?? '').trim() === '' || parseAmount(item.price_sell) < 0);
  if (hasInvalidManualItem) {
    toast.error('Sản phẩm tự do phải có tên, số lượng và giá bán hợp lệ.');
    return false;
  }

  if (customerMode.value === 'existing' && !form.value.customer_id) {
    toast.error('Vui lòng chọn khách hàng cũ trước khi lưu đơn.');
    openCustomerModal();
    return false;
  }

  if (customerMode.value === 'new' && !String(form.value.customer_name || '').trim()) {
    toast.error('Vui lòng nhập tên khách hàng.');
    await nextTick();
    customerNameInput.value?.focus();
    return false;
  }

  return true;
};

const submit = async () => {
  if (!(await validateBeforeSubmit())) {
    return;
  }

  try {
    const payload = isEdit.value
      ? await submitUpdate(Number(route.params.id || 0))
      : await submitCreate();

    toast.success(payload?.message || (isEdit.value ? 'Đã cập nhật đơn hàng.' : 'Đã tạo đơn hàng.'));

    const nextId = Number(payload?.data?.id || route.params.id || 0);
    if (nextId > 0) {
      await router.push({ name: 'orders.detail', params: { id: nextId } });
      return;
    }

    await router.push('/orders');
  } catch (_err) {
    toast.error((isEdit.value ? updateError.value : createError.value) || 'Không thể lưu đơn hàng.');
  }
};

const initializePage = async () => {
  try {
    resetState();
    await loadBootstrap();
    if (isEdit.value) {
      await loadEdit(Number(route.params.id || 0));
      rows.value.forEach((row) => {
        normalizeRowQty(row);
      });
      manualItems.value.forEach((item) => {
        normalizeManualQty(item);
      });
    }

    initializeUiState();

    if (!isEdit.value && !rows.value.length) {
      openProductSelector();
    }
  } catch (_err) {
    toast.error(bootstrapError.value || detailError.value || 'Không thể tải dữ liệu đơn hàng.');
  }
};

watch(
  () => `${route.name || ''}:${route.params.id || ''}`,
  async (_next, prev) => {
    if (prev === undefined) {
      return;
    }

    showProductSelector.value = false;
    showCustomerModal.value = false;
    showPriceModal.value = false;
    showManualItemModal.value = false;
    showDiscountModal.value = false;
    showSurchargeModal.value = false;

    await initializePage();
  }
);

onMounted(async () => {
  await initializePage();
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="pageTitle" :back-to="backTarget" />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải dữ liệu form...</div>

    <form v-else class="space-y-4" @submit.prevent="submit">
      <section class="app-card">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><ClipboardList class="h-4 w-4" /></span>
            <span>Sản phẩm</span>
          </div>
          <button type="button" class="app-btn-secondary !min-h-0 px-3 py-1 text-sm" @click="openProductSelector()">Thêm SP</button>
        </div>

        <div v-if="rows.length" class="mt-3 space-y-2">
            <div v-for="(row, index) in rows" :key="`row-${index}`" class="rounded-xl border border-slate-200 bg-white px-3 py-2">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <button type="button" class="block max-w-full text-left" @click="openProductSelector(index)">
                    <div class="text-sm font-medium text-slate-900">
                      {{ getUnitDisplay(row)?.product_name || 'Chọn sản phẩm' }}<span v-if="getUnitDisplay(row)?.unit_name" class="text-slate-500"> - {{ getUnitDisplay(row)?.unit_name }}</span>
                    </div>
                  </button>

                  <div class="mt-1 flex flex-wrap items-center gap-1 text-sm text-slate-600">
                    <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-300 text-slate-600" @click="decreaseRowQty(row)">
                      <Minus class="h-3.5 w-3.5" />
                    </button>
                    <input v-model="row.qty" :min="getRowStep(row)" :step="getRowStep(row)" type="number" class="h-6 w-10 rounded-md border border-slate-300 text-center text-sm outline-none" @change="normalizeRowQty(row)" />
                    <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-300 text-slate-600" @click="increaseRowQty(row)">
                      <Plus class="h-3.5 w-3.5" />
                    </button>
                    <button v-if="getUnitDisplay(row)" type="button" class="inline-flex items-center gap-1 rounded-lg hover:bg-brand-50" @click="openPriceModal(index)">
                      <span class="font-medium" :class="getRowPriceClass(row)">{{ formatMoney(row.price) }}</span>
                      <Pencil class="h-3 w-3 text-slate-500" />
                    </button>
                  </div>
                </div>

                <div class="flex flex-col items-end gap-2">
                  <button type="button" class="inline-flex h-5 w-5 items-center justify-center text-rose-500" @click="removeRow(index)">
                    <X class="h-4 w-4" />
                  </button>
                  <div class="text-sm font-medium text-brand-600">{{ formatMoney(Number(row.qty || 0) * parseAmount(row.price || 0)) }}</div>
                </div>
              </div>
            </div>
        </div>
        <div v-else class="mt-3 app-empty-state">Đơn hàng chưa có mặt hàng nào.</div>

        <div class="mt-4 flex items-center justify-between">
          <div class="flex items-center gap-2 text-base font-medium text-orange-700">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-600">
              <Tag class="h-3.5 w-3.5" />
            </span>
            <span>Sản phẩm khác</span>
          </div>
          <button type="button" class="app-btn-secondary !min-h-0 px-3 py-1 text-sm" @click="openManualItemModal()">Thêm SP</button>
        </div>

        <div v-if="manualItems.length" class="mt-3 space-y-2">
              <div
                v-for="(item, index) in manualItems"
                :key="`manual-${index}`"
                class="cursor-pointer rounded-xl border border-amber-200 bg-amber-50/40 px-3 py-2"
                @click="openManualItemModal(index)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-slate-900">
                      {{ item.item_name || 'Chưa nhập tên hàng' }}<span v-if="item.unit_name" class="text-slate-500"> - {{ item.unit_name }}</span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-1 text-sm text-slate-600">
                      <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-300 text-slate-600" @click.stop="decreaseManualQty(item)">
                        <Minus class="h-3.5 w-3.5" />
                      </button>
                      <input
                        v-model="item.qty"
                        :min="getManualQtyMin(item)"
                        :step="getManualQtyStep(item)"
                        type="number"
                        class="h-6 w-10 rounded-md border border-slate-300 text-center text-sm outline-none"
                        @click.stop
                        @change="normalizeManualQty(item)"
                      />
                      <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-300 text-slate-600" @click.stop="increaseManualQty(item)">
                        <Plus class="h-3.5 w-3.5" />
                      </button>
                      <div class="inline-flex items-center gap-1 rounded-lg px-1 text-slate-600">
                        <span class="font-medium text-slate-900">{{ formatMoney(parseAmount(item.price_sell || 0)) }}</span>
                        <Pencil class="h-3 w-3 text-slate-500" />
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col items-end gap-2">
                    <button type="button" class="inline-flex h-5 w-5 items-center justify-center text-rose-500" @click.stop="removeManualItem(index)">
                      <X class="h-4 w-4" />
                    </button>
                    <div class="text-sm font-medium text-brand-600">{{ formatMoney(Number(item.qty || 0) * parseAmount(item.price_sell || 0)) }}</div>
                  </div>
                </div>
              </div>
        </div>
        <div v-else class="mt-3 app-empty-state">Chưa có sản phẩm khác nào.</div>

        <div class="mt-4 space-y-1.5 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-slate-700">Tạm tính</span>
              <span class="font-medium text-slate-900">{{ formatMoney(summary.gross) }}</span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-slate-700">Giảm giá</span>
              <button type="button" class="inline-flex items-center gap-1 font-medium text-rose-600" @click="openDiscountModal">
                <span>-{{ formatMoney(discountAmount) }}</span>
                <Pencil class="h-3 w-3" />
              </button>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-slate-700">Phụ thu</span>
              <button type="button" class="inline-flex items-center gap-1 font-medium text-amber-600" @click="openSurchargeModal">
                <span>+{{ formatMoney(surchargeValue) }}</span>
                <CirclePlus class="h-3 w-3" />
              </button>
            </div>

            <div class="flex items-center justify-between pt-1">
              <span class="text-lg font-medium text-slate-900">Tổng cộng</span>
              <div class="inline-flex items-center gap-1 text-lg font-semibold text-brand-700">
                <span>{{ formatMoney(finalTotal) }}</span>
                <button type="button" class="inline-flex h-5 w-5 items-center justify-center text-slate-400" @click="manualRoundTotal">
                  <RefreshCw class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
        </div>
      </section>

      <section class="rounded-lg border border-slate-200 bg-white">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2 text-sm font-medium text-slate-800">
          <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700"><Users class="h-4 w-4" /></span>
          <span>{{ isEdit ? 'Thông tin khách hàng' : 'Khách hàng và thanh toán' }}</span>
        </div>

        <div class="grid gap-4 px-4 py-3 md:grid-cols-2">
          <div class="space-y-2 md:col-span-2">
            <label class="block text-sm text-slate-700">Khách hàng</label>
            <div class="app-segment">
              <button type="button" class="app-segment-item" :class="customerMode === 'existing' ? 'app-segment-item-active' : ''" @click="customerMode = 'existing'">Khách cũ</button>
              <button type="button" class="app-segment-item" :class="customerMode === 'new' ? 'app-segment-item-active' : ''" @click="customerMode = 'new'">Khách mới</button>
              <button type="button" class="app-segment-item" :class="customerMode === 'guest' ? 'app-segment-item-active' : ''" @click="customerMode = 'guest'">Khách lẻ</button>
            </div>
          </div>

          <div v-if="customerMode === 'existing'" class="space-y-1 md:col-span-2">
            <label class="block text-sm text-slate-700">Khách cũ</label>
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-lg border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-left text-sm text-amber-800 hover:border-amber-400 hover:bg-amber-100"
              @click="openCustomerModal"
            >
              <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                  <Users class="h-4 w-4" />
                </span>
                <span class="flex flex-col">
                  <span class="font-medium">{{ selectedCustomerSummary.title }}</span>
                  <span class="text-xs text-amber-700">{{ selectedCustomerSummary.meta }}</span>
                </span>
              </div>
              <span class="ml-2 inline-flex h-6 w-6 items-center justify-center text-amber-500">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                  <path d="m8 6 4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </span>
            </button>
          </div>

          <template v-if="customerMode === 'new'">
            <label class="space-y-1">
              <span class="app-label">Tên khách hàng</span>
              <input ref="customerNameInput" v-model="form.customer_name" type="text" class="app-input" />
            </label>
            <label class="space-y-1">
              <span class="app-label">Số điện thoại</span>
              <input v-model="form.customer_phone" type="text" class="app-input" />
            </label>
            <label class="space-y-1 md:col-span-2">
              <span class="app-label">Địa chỉ</span>
              <input v-model="form.customer_address" type="text" class="app-input" />
            </label>
          </template>

          <label v-if="isEdit" class="space-y-1 md:col-span-2">
            <span class="app-label">Ngày giờ đơn hàng</span>
            <input v-model="form.order_date" type="datetime-local" class="app-input" />
          </label>

          <template v-if="!isEdit">
            <div class="space-y-1 md:col-span-2">
              <span class="app-label">Thanh toán</span>
              <div class="app-segment">
                <button type="button" class="app-segment-item" :class="form.payment_status === 'pay' ? 'app-segment-item-active' : ''" @click="form.payment_status = 'pay'">Thanh toán</button>
                <button type="button" class="app-segment-item" :class="form.payment_status === 'debt' ? 'app-segment-item-active' : ''" @click="form.payment_status = 'debt'">Ghi nợ</button>
              </div>
            </div>

            <div v-if="form.payment_status === 'pay'" class="space-y-1 md:col-span-2">
              <span class="app-label">Hình thức thanh toán</span>
              <div class="app-segment">
                <button type="button" class="app-segment-item" :class="form.payment_method === 'cash' ? 'app-segment-item-active' : ''" @click="form.payment_method = 'cash'">Tiền mặt</button>
                <button type="button" class="app-segment-item" :class="form.payment_method === 'bank' ? 'app-segment-item-active' : ''" @click="form.payment_method = 'bank'">Chuyển khoản</button>
              </div>
            </div>

            <label v-if="form.payment_status === 'pay'" class="space-y-1 md:col-span-2">
              <span class="app-label">Số tiền thanh toán</span>
              <div class="relative">
                <input v-model="form.payment_amount" type="text" inputmode="numeric" v-money-input class="app-input pr-8 text-right" />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
              </div>
            </label>
          </template>

          <label class="space-y-1 md:col-span-2">
            <span class="app-label">Ghi chú</span>
            <textarea v-model="form.note" rows="3" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm outline-none transition focus:border-brand-500"></textarea>
          </label>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-4 py-3" data-floating-actions>
          <button type="button" class="app-btn-secondary" @click="router.push(backTarget)">Hủy</button>
          <button type="submit" class="app-btn-primary" :disabled="saving">{{ isEdit ? 'Lưu thay đổi' : 'Nhập đơn' }}</button>
        </div>
      </section>
    </form>

    <Teleport to="body">
      <transition name="app-modal-fade-up">
        <div v-if="showProductSelector" class="app-modal-overlay app-modal-open" @click.self="closeProductSelector">
          <div class="app-modal-sheet app-modal-sheet-fill">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Chọn sản phẩm</h2>
              <button type="button" class="app-modal-close" @click="closeProductSelector">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body app-modal-body-fill pt-2 pb-3">
              <div class="mb-2">
                <input v-model="productKeyword" type="search" placeholder="Tìm sản phẩm, mã hàng..." class="app-input" />
              </div>
              <div class="app-modal-body-scroll rounded-lg border border-slate-200">
                <button
                  v-for="unit in filteredUnits"
                  :key="unit.id"
                  type="button"
                  class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-left last:border-b-0"
                  @click="selectProductUnit(unit)"
                >
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-slate-900">{{ unit.product_name }}<span v-if="unit.unit_name" class="text-slate-500"> - {{ unit.unit_name }}</span></div>
                    <div class="mt-0.5 text-sm text-slate-500">{{ unit.product_code || 'Không có mã' }}</div>
                    <div class="mt-0.5 text-sm font-medium text-brand-700">{{ formatMoney(unit.price_sell) }}</div>
                  </div>
                </button>
                <div v-if="!filteredUnits.length" class="p-3 text-center text-sm text-slate-500">Không tìm thấy sản phẩm phù hợp.</div>
              </div>
            </div>
          </div>
        </div>
      </transition>

      <transition name="app-modal-fade-up">
        <div v-if="showCustomerModal" class="app-modal-overlay app-modal-open" @click.self="closeCustomerModal">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Chọn khách hàng</h2>
              <button type="button" class="app-modal-close" @click="closeCustomerModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-3">
              <input v-model="customerKeyword" type="search" placeholder="Tìm tên, SĐT, địa chỉ..." class="app-input" />
              <div class="max-h-80 overflow-y-auto rounded-xl border border-slate-200">
                <button
                  v-for="customer in filteredCustomers"
                  :key="customer.id"
                  type="button"
                  class="flex w-full items-start justify-between gap-2 border-b border-slate-100 px-3 py-2 text-left last:border-b-0"
                  @click="pendingCustomerId = String(customer.id)"
                >
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-slate-900">{{ customer.name || 'Khách hàng' }}</div>
                    <div class="mt-0.5 text-sm text-slate-500">{{ [customer.phone, customer.address].filter(Boolean).join(' - ') }}</div>
                  </div>
                  <span class="inline-flex h-5 w-5 items-center justify-center rounded-md border text-xs font-semibold"
                    :class="String(pendingCustomerId) === String(customer.id) ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-transparent'">
                    ✓
                  </span>
                </button>
                <div v-if="!filteredCustomers.length" class="p-3 text-center text-sm text-slate-500">Không tìm thấy khách hàng phù hợp.</div>
              </div>
            </div>
            <div class="app-modal-footer">
              <button type="button" class="app-btn-secondary" @click="closeCustomerModal">Hủy</button>
              <button type="button" class="app-btn-primary" @click="applySelectedCustomer">Áp dụng</button>
            </div>
          </div>
        </div>
      </transition>

      <transition name="app-modal-fade-up">
        <div v-if="showPriceModal" class="app-modal-overlay app-modal-open" @click.self="closePriceModal">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Chỉnh đơn giá</h2>
              <button type="button" class="app-modal-close" @click="closePriceModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-4">
              <div class="text-sm text-slate-600">{{ editingPriceLabel }}</div>
              <div class="space-y-1 rounded-lg bg-slate-50 p-2 text-sm text-slate-600">
                <div class="flex items-center justify-between">
                  <span>Giá gốc</span>
                  <span class="font-medium text-slate-900">{{ formatMoney(editingPriceUnit?.price_sell || 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Đơn giá hiện tại</span>
                  <span class="font-medium text-slate-900">{{ formatMoney(parseAmount(editingPriceRow?.price || 0)) }}</span>
                </div>
              </div>
              <label class="space-y-1">
                <span class="app-label">Đơn giá mới</span>
                <div class="relative">
                  <input v-model="priceDraftValue" type="text" inputmode="numeric" class="app-input pr-8 text-right font-medium text-slate-900" @input="priceDraftValue = formatPriceInput(priceDraftValue, false)" />
                  <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                </div>
              </label>
            </div>
            <div class="app-modal-footer">
              <button type="button" class="app-btn-secondary" @click="closePriceModal">Hủy</button>
              <button type="button" class="app-btn-primary" @click="applyPrice">Áp dụng</button>
            </div>
          </div>
        </div>
      </transition>

      <transition name="app-modal-fade-up">
        <div v-if="showManualItemModal" class="app-modal-overlay app-modal-open" @click.self="closeManualItemModal">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">{{ editingManualIndex === null ? 'Thêm sản phẩm tự do' : 'Chỉnh sửa sản phẩm tự do' }}</h2>
              <button type="button" class="app-modal-close" @click="closeManualItemModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-4">
              <label class="space-y-1">
                <span class="app-label">Tên hàng</span>
                <input v-model="manualItemDraft.item_name" type="text" class="app-input" />
              </label>
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-1">
                  <span class="app-label">Đơn vị</span>
                  <input v-model="manualItemDraft.unit_name" type="text" class="app-input" />
                </label>
                <label class="space-y-1">
                  <span class="app-label">Số lượng</span>
                  <input v-model="manualItemDraft.qty" type="number" min="0" step="0.01" class="app-input text-right" />
                </label>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-1">
                  <span class="app-label">Giá vốn</span>
                  <div class="relative">
                    <input v-model="manualItemDraft.price_buy" type="text" inputmode="numeric" class="app-input pr-8 text-right" @input="formatPriceField(manualItemDraft, 'price_buy', false)" />
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                  </div>
                </label>
                <label class="space-y-1">
                  <span class="app-label">Giá bán</span>
                  <div class="relative">
                    <input v-model="manualItemDraft.price_sell" type="text" inputmode="numeric" class="app-input pr-8 text-right" @input="formatPriceField(manualItemDraft, 'price_sell', false)" />
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                  </div>
                </label>
              </div>
            </div>
            <div class="app-modal-footer">
              <button type="button" class="app-btn-secondary" @click="closeManualItemModal">Hủy</button>
              <button type="button" class="app-btn-primary" @click="saveManualItem">Lưu</button>
            </div>
          </div>
        </div>
      </transition>

      <transition name="app-modal-fade-up">
        <div v-if="showDiscountModal" class="app-modal-overlay app-modal-open" @click.self="closeDiscountModal">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Giảm giá đơn hàng</h2>
              <button type="button" class="app-modal-close" @click="closeDiscountModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-4">
              <div class="app-segment">
                <button type="button" class="app-segment-item" :class="discountDraftType === 'none' ? 'app-segment-item-active' : ''" @click="discountDraftType = 'none'; discountDraftValue = ''">Không giảm</button>
                <button type="button" class="app-segment-item" :class="discountDraftType === 'fixed' ? 'app-segment-item-active' : ''" @click="discountDraftType = 'fixed'; onDiscountDraftValueInput()">Theo tiền</button>
                <button type="button" class="app-segment-item" :class="discountDraftType === 'percent' ? 'app-segment-item-active' : ''" @click="discountDraftType = 'percent'; onDiscountDraftValueInput()">Theo %</button>
              </div>
              <label v-if="discountDraftType !== 'none'" class="space-y-1">
                <span class="app-label">Giá trị giảm</span>
                <div class="relative">
                  <input v-model="discountDraftValue" type="text" inputmode="numeric" class="app-input pr-8 text-right" :data-money-format="discountDraftType === 'percent' ? 'off' : null" :data-money-x1000="discountDraftType === 'percent' ? 'off' : null" @input="onDiscountDraftValueInput" />
                  <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">{{ discountDraftSuffix }}</span>
                </div>
              </label>
            </div>
            <div class="app-modal-footer">
              <button type="button" class="app-btn-secondary" @click="closeDiscountModal">Hủy</button>
              <button type="button" class="app-btn-primary" @click="applyDiscount">Áp dụng</button>
            </div>
          </div>
        </div>
      </transition>

      <transition name="app-modal-fade-up">
        <div v-if="showSurchargeModal" class="app-modal-overlay app-modal-open" @click.self="closeSurchargeModal">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Phụ thu đơn hàng</h2>
              <button type="button" class="app-modal-close" @click="closeSurchargeModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-4">
              <label class="space-y-1">
                <span class="app-label">Số tiền phụ thu</span>
                <div class="relative">
                  <input v-model="surchargeDraftValue" type="text" inputmode="numeric" class="app-input pr-8 text-right" @input="surchargeDraftValue = formatMoneyInput(surchargeDraftValue, false)" />
                  <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                </div>
              </label>
            </div>
            <div class="app-modal-footer">
              <button type="button" class="app-btn-secondary" @click="closeSurchargeModal">Hủy</button>
              <button type="button" class="app-btn-primary" @click="applySurcharge">Áp dụng</button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </section>
</template>