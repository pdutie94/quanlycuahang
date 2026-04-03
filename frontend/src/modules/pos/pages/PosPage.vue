<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { CirclePlus, Minus, Pencil, Plus, RefreshCw, Tag, X } from '@lucide/vue';
import { usePos } from '../composables/usePos';
import { useToast } from '../../../shared/composables/useToast';

const router = useRouter();
const toast = useToast();

const {
  products,
  productUnitsByProduct,
  customers,
  cartItems,
  manualItems,
  loading,
  error,
  loadBootstrap,
  addProduct,
  removeCartItem,
  addManualItem,
  removeManualItem,
  subtotal,
  submitOrder,
  submitting,
  submitError
} = usePos();

const showProductSelector = ref(false);
const showManualItemModal = ref(false);
const showDiscountModal = ref(false);
const showSurchargeModal = ref(false);
const showCustomerModal = ref(false);
const showPriceModal = ref(false);
const productSelectorKeyword = ref('');
const customerKeyword = ref('');
const selectedProductIds = ref([]);
const pendingCustomerId = ref('');
const editingPriceItemId = ref('');
const priceDraftValue = ref('');
const editingManualIndex = ref(null);
const manualItemDraft = ref({
  item_name: '',
  unit_name: '',
  qty: 1,
  price_buy: '',
  price_sell: ''
});
const selectedCustomerId = ref('');
const customerName = ref('');
const customerPhone = ref('');
const customerAddress = ref('');
const note = ref('');
const paymentStatus = ref('pay');
const paymentMethod = ref('cash');
const paymentAmount = ref('');
const discountType = ref('none');
const discountValue = ref('');
const surchargeAmount = ref('');
const discountDraftType = ref('none');
const discountDraftValue = ref('');
const surchargeDraftValue = ref('');
const customerMode = ref('guest');

const numberFormatter = new Intl.NumberFormat('vi-VN');

const formatMoney = (amount) => `${numberFormatter.format(Number(amount || 0))} đ`;

const parseAmount = (value) => {
  if (value === null || value === undefined) {
    return 0;
  }
  const digits = String(value).replace(/[^0-9-]/g, '');
  if (!digits || digits === '-') {
    return 0;
  }
  return Number(digits);
};

const formatMoneyInput = (value, allowEmpty = true) => {
  const amount = parseAmount(value);
  if (amount <= 0) {
    return allowEmpty ? '' : '0';
  }

  return numberFormatter.format(amount);
};

// Parses a price input that may use decimal shorthand: "24.5" → 24500, "2" → 2000.
// Keeps discount/percent paths separate (parseAmount is unchanged).
const parsePriceShorthand = (raw) => {
  const str = String(raw ?? '').trim();
  if (!str) return 0;
  const dotIdx = str.indexOf('.');
  if (dotIdx !== -1 && (str.match(/\./g) || []).length === 1) {
    const afterDot = str.slice(dotIdx + 1).replace(/[^0-9]/g, '');
    if (afterDot.length < 3) {
      const num = parseFloat(str.replace(/[^0-9.]/g, ''));
      if (!isNaN(num) && num > 0) return num < 1000 ? Math.round(num * 1000) : Math.round(num);
      return 0;
    }
  }
  const digits = str.replace(/[^0-9]/g, '');
  if (!digits) return 0;
  const num = Number(digits);
  return num > 0 && num < 1000 ? num * 1000 : num;
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

const roundDownThousand = (value) => {
  const amount = parseAmount(value);
  if (amount <= 0) {
    return 0;
  }
  return Math.floor(amount / 1000) * 1000;
};

const filteredProducts = computed(() => {
  const keyword = productSelectorKeyword.value.trim().toLowerCase();
  if (!keyword) {
    return products.value.slice(0, 80);
  }

  return products.value
    .filter((product) => [product.name, product.code, product.category_name].some((field) => String(field || '').toLowerCase().includes(keyword)))
    .slice(0, 80);
});

const discountAmount = computed(() => {
  const currentSubtotal = subtotal.value;
  const value = parseAmount(discountValue.value);
  if (discountType.value === 'fixed') {
    return Math.min(value, currentSubtotal);
  }
  if (discountType.value === 'percent') {
    const percent = Math.min(value, 100);
    return Math.round(currentSubtotal * percent / 100);
  }
  return 0;
});

const surchargeValue = computed(() => parseAmount(surchargeAmount.value));

const finalTotal = computed(() => {
  const nextTotal = subtotal.value - discountAmount.value + surchargeValue.value;
  return roundDownThousand(nextTotal < 0 ? 0 : nextTotal);
});

const selectedCustomer = computed(() => {
  if (!selectedCustomerId.value) {
    return null;
  }
  return customers.value.find((customer) => Number(customer.id) === Number(selectedCustomerId.value)) || null;
});

const showExistingCustomerPicker = computed(() => customerMode.value === 'existing');
const showNewCustomerFields = computed(() => customerMode.value === 'new');
const isPayNow = computed(() => paymentStatus.value === 'pay');

const filteredCustomers = computed(() => {
  const keyword = customerKeyword.value.trim().toLowerCase();
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

const syncCustomerFields = () => {
  if (!selectedCustomer.value) {
    return;
  }
  customerName.value = selectedCustomer.value.name || '';
  customerPhone.value = selectedCustomer.value.phone || '';
  customerAddress.value = selectedCustomer.value.address || '';
};

watch(customerMode, (mode) => {
  if (mode === 'guest') {
    selectedCustomerId.value = '';
    customerName.value = '';
    customerPhone.value = '';
    customerAddress.value = '';
  }

  if (mode === 'existing') {
    pendingCustomerId.value = selectedCustomerId.value ? String(selectedCustomerId.value) : '';
    syncCustomerFields();
  }
});

watch(discountDraftType, () => {
  onDiscountDraftValueInput();
});

const getUnits = (productId) => productUnitsByProduct.value[String(productId)] || productUnitsByProduct.value[productId] || [];
const getCartItemUnit = (item) => getUnits(item.product_id).find((unit) => Number(unit.unit_id) === Number(item.unit_id)) || null;
const getCartItemUnitName = (item) => getUnits(item.product_id).find((unit) => Number(unit.unit_id) === Number(item.unit_id))?.unit_name || '';
const getCartItemBasePrice = (item) => Number(item?.base_price || item?.price || 0);
const getCartItemStep = (item) => {
  const unit = getCartItemUnit(item);
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
const getCartItemMinQty = (item) => getCartItemStep(item);
const formatQtyValue = (value) => Number(value || 0).toFixed(4).replace(/\.?0+$/, '');
const normalizeCartQty = (item) => {
  const step = getCartItemStep(item);
  const minQty = getCartItemMinQty(item);
  const currentQty = Number(item.quantity || 0);

  if (!Number.isFinite(currentQty) || currentQty <= 0) {
    item.quantity = formatQtyValue(minQty);
    return;
  }

  if (step === 1) {
    item.quantity = formatQtyValue(Math.max(minQty, Math.round(currentQty)));
    return;
  }

  const normalizedQty = Math.max(minQty, Math.round(currentQty / step) * step);
  item.quantity = formatQtyValue(normalizedQty);
};
const increaseCartQty = (item) => {
  const step = getCartItemStep(item);
  item.quantity = formatQtyValue(Number(item.quantity || 0) + step);
};
const decreaseCartQty = (item) => {
  const step = getCartItemStep(item);
  const minQty = getCartItemMinQty(item);
  const nextQty = Math.max(minQty, Number(item.quantity || 0) - step);
  item.quantity = formatQtyValue(nextQty);
};

const isProductSelected = (productId) => selectedProductIds.value.includes(Number(productId));
const isPendingCustomer = (customerId) => String(pendingCustomerId.value) === String(customerId);
const getDefaultUnit = (productId) => getUnits(productId)[0] || null;
const editingPriceItem = computed(() => cartItems.value.find((item) => String(item.id) === String(editingPriceItemId.value)) || null);
const editingPriceProductLabel = computed(() => {
  if (!editingPriceItem.value) {
    return '';
  }

  const unitName = getCartItemUnitName(editingPriceItem.value);
  return `${editingPriceItem.value.product_name}${unitName ? ` - ${unitName}` : ''}`;
});

const resetManualDraft = () => {
  manualItemDraft.value = {
    item_name: '',
    unit_name: '',
    qty: 1,
    price_buy: '',
    price_sell: ''
  };
};

const openProductSelector = () => {
  productSelectorKeyword.value = '';
  selectedProductIds.value = [];
  showProductSelector.value = true;
};

const closeProductSelector = () => {
  showProductSelector.value = false;
  selectedProductIds.value = [];
};

const toggleProductSelection = (productId) => {
  const nextId = Number(productId);
  if (isProductSelected(nextId)) {
    selectedProductIds.value = selectedProductIds.value.filter((id) => id !== nextId);
    return;
  }

  selectedProductIds.value = [...selectedProductIds.value, nextId];
};

const applySelectedProducts = () => {
  if (!selectedProductIds.value.length) {
    return;
  }

  let addedCount = 0;
  let invalidCount = 0;

  selectedProductIds.value.forEach((productId) => {
    const product = products.value.find((entry) => Number(entry.id) === Number(productId));
    if (!product) {
      return;
    }

    if (addProduct(product)) {
      addedCount += 1;
      return;
    }

    invalidCount += 1;
  });

  if (invalidCount > 0) {
    toast.error(`${invalidCount} sản phẩm chưa có đơn vị bán.`);
  }

  if (addedCount > 0) {
    closeProductSelector();
  }
};

const openCustomerModal = () => {
  customerKeyword.value = '';
  pendingCustomerId.value = selectedCustomerId.value ? String(selectedCustomerId.value) : '';
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

  selectedCustomerId.value = String(pendingCustomerId.value);
  syncCustomerFields();
  closeCustomerModal();
};

const onDiscountDraftValueInput = () => {
  if (discountDraftType.value === 'percent') {
    const percent = Math.min(parseAmount(discountDraftValue.value), 100);
    discountDraftValue.value = percent > 0 ? String(percent) : '';
    return;
  }

  if (discountDraftType.value === 'fixed') {
    discountDraftValue.value = formatMoneyInput(discountDraftValue.value);
    return;
  }

  discountDraftValue.value = '';
};

const openPriceModal = (item) => {
  editingPriceItemId.value = String(item.id);
  priceDraftValue.value = formatMoneyInput(Math.max(0, Number(item.price || 0)), false);
  showPriceModal.value = true;
};

const closePriceModal = () => {
  showPriceModal.value = false;
  editingPriceItemId.value = '';
  priceDraftValue.value = '';
};

const applyPrice = () => {
  if (!editingPriceItem.value) {
    closePriceModal();
    return;
  }

  const basePrice = getCartItemBasePrice(editingPriceItem.value);
  let nextPrice = parsePriceShorthand(priceDraftValue.value);

  if (nextPrice <= 0) {
    nextPrice = basePrice;
  }

  editingPriceItem.value.price = nextPrice;
  closePriceModal();
};

const openDiscountModal = () => {
  discountDraftType.value = discountType.value;
  if (discountType.value === 'percent') {
    const percent = Math.min(parseAmount(discountValue.value), 100);
    discountDraftValue.value = percent > 0 ? String(percent) : '';
  } else if (discountType.value === 'fixed') {
    discountDraftValue.value = formatMoneyInput(discountValue.value);
  } else {
    discountDraftValue.value = '';
  }
  showDiscountModal.value = true;
};

const closeDiscountModal = () => {
  showDiscountModal.value = false;
};

const applyDiscount = () => {
  discountType.value = discountDraftType.value;
  discountValue.value = discountDraftValue.value;
  closeDiscountModal();
};

const openSurchargeModal = () => {
  surchargeDraftValue.value = formatMoneyInput(surchargeAmount.value);
  showSurchargeModal.value = true;
};

const closeSurchargeModal = () => {
  showSurchargeModal.value = false;
};

const applySurcharge = () => {
  surchargeAmount.value = surchargeDraftValue.value;
  closeSurchargeModal();
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
      qty: source.qty || 1,
      price_buy: source.price_buy || '',
      price_sell: source.price_sell || ''
    };
  }
  showManualItemModal.value = true;
};

const closeManualItemModal = () => {
  showManualItemModal.value = false;
  editingManualIndex.value = null;
};

const saveManualItem = () => {
  const parsedBuy = parsePriceShorthand(manualItemDraft.value.price_buy);
  const parsedSell = parsePriceShorthand(manualItemDraft.value.price_sell);
  manualItemDraft.value.price_buy = parsedBuy > 0 ? formatMoneyInput(parsedBuy) : '';
  manualItemDraft.value.price_sell = parsedSell > 0 ? formatMoneyInput(parsedSell) : '';

  if (editingManualIndex.value === null) {
    addManualItem();
    const newIndex = manualItems.value.length - 1;
    manualItems.value[newIndex] = {
      ...manualItems.value[newIndex],
      ...manualItemDraft.value
    };
  } else {
    manualItems.value[editingManualIndex.value] = {
      ...manualItems.value[editingManualIndex.value],
      ...manualItemDraft.value
    };
  }

  closeManualItemModal();
};

const buildPayload = () => ({
  items: cartItems.value.map((item) => ({
    product_id: item.product_id,
    unit_id: item.unit_id,
    quantity: Number(item.quantity || 0),
    price: Number(item.price || 0)
  })),
  customer_id: customerMode.value === 'existing' && selectedCustomerId.value ? Number(selectedCustomerId.value) : 0,
  customer_mode: customerMode.value,
  customer_name: customerName.value,
  customer_phone: customerPhone.value,
  customer_address: customerAddress.value,
  note: note.value,
  payment_status: paymentStatus.value,
  payment_method: paymentMethod.value,
  payment_amount: paymentAmount.value,
  discount_type: discountType.value,
  discount_value: discountValue.value,
  surcharge_amount: surchargeAmount.value,
  manual_item_name: manualItems.value.map((item) => item.item_name),
  manual_unit_name: manualItems.value.map((item) => item.unit_name),
  manual_qty: manualItems.value.map((item) => item.qty),
  manual_price_buy: manualItems.value.map((item) => item.price_buy),
  manual_price_sell: manualItems.value.map((item) => item.price_sell)
});

const createPosOrder = async () => {
  try {
    const payload = await submitOrder(buildPayload());
    toast.success(payload?.message || 'Đã lưu đơn hàng.');
    const nextId = Number(payload?.data?.id || 0);
    if (nextId > 0) {
      await router.push({ name: 'orders.detail', params: { id: nextId } });
    }
  } catch (_err) {
    toast.error(submitError.value || 'Không thể lưu đơn hàng.');
  }
};

onMounted(async () => {
  try {
    await loadBootstrap();
  } catch (_err) {
    toast.error(error.value || 'Không thể tải dữ liệu POS.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <header>
      <h1 class="text-lg font-medium tracking-tight text-slate-900">Bán hàng</h1>
    </header>

    <div v-if="loading" class="app-card text-center text-sm text-slate-500">Đang tải dữ liệu POS...</div>

    <form v-else class="space-y-4" @submit.prevent="createPosOrder">
      <section class="app-card">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-medium text-slate-900">Sản phẩm</h2>
          <button type="button" class="app-btn-secondary !min-h-0 px-3 py-1 text-sm" @click="openProductSelector">Thêm SP</button>
        </div>

        <div v-if="cartItems.length" class="mt-3 space-y-2">
          <div v-for="item in cartItems" :key="item.id" class="rounded-xl border border-slate-200 bg-white px-3 py-2">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-slate-900">
                  {{ item.product_name }}<span v-if="getCartItemUnitName(item)" class="text-slate-500"> - {{ getCartItemUnitName(item) }}</span>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-1 text-sm text-slate-600">
                  <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-300 text-slate-600" @click="decreaseCartQty(item)">
                    <Minus class="h-3.5 w-3.5" />
                  </button>
                  <input v-model="item.quantity" :min="getCartItemMinQty(item)" :step="getCartItemStep(item)" type="number" class="h-6 w-14 rounded-md border border-slate-300 text-center text-sm outline-none" @change="normalizeCartQty(item)" />
                  <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-300 text-slate-600" @click="increaseCartQty(item)">
                    <Plus class="h-3.5 w-3.5" />
                  </button>
                  <button type="button" class="inline-flex items-center gap-1 rounded-lg hover:bg-brand-50" @click="openPriceModal(item)">
                    <span class="font-medium" :class="Number(item.price || 0) < getCartItemBasePrice(item) ? 'text-amber-700' : 'text-slate-900'">{{ formatMoney(item.price) }}</span>
                    <Pencil class="h-3 w-3 text-slate-500" />
                  </button>
                </div>
              </div>
              <div class="flex flex-col items-end gap-2">
                <button type="button" class="inline-flex h-5 w-5 items-center justify-center text-rose-500" @click="removeCartItem(item.id)">
                  <X class="h-4 w-4" />
                </button>
                <div class="text-sm font-medium text-brand-600">{{ formatMoney(Number(item.quantity || 0) * Number(item.price || 0)) }}</div>
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
            class="relative cursor-pointer rounded-xl border border-amber-200 bg-amber-50/40 p-3"
            @click="openManualItemModal(index)"
          >
            <div class="min-w-0">
              <div class="text-sm font-medium text-slate-900">
                <span>{{ item.item_name || 'Chưa nhập tên hàng' }}</span>
                <span v-if="item.unit_name" class="ml-1 text-sm text-slate-500">{{ item.unit_name }}</span>
              </div>
              <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                <span>SL: <span class="font-medium">{{ item.qty || 0 }}</span></span>
                <span>Giá: <span class="font-medium">{{ formatMoney(item.price_sell || 0) }}</span></span>
                <span>Tổng: <span class="font-medium">{{ formatMoney(Number(item.qty || 0) * Number(item.price_sell || 0)) }}</span></span>
              </div>
            </div>
            <div class="mt-2 flex items-center justify-between">
              <button type="button" class="inline-flex items-center gap-1 text-sm font-medium text-slate-700" @click.stop="openManualItemModal(index)">
                <Pencil class="h-3 w-3" />
                <span>Sửa</span>
              </button>
              <button type="button" class="text-sm font-medium text-rose-600" @click.stop="removeManualItem(index)">Xóa</button>
            </div>
          </div>
        </div>
        <div v-else class="mt-3 app-empty-state">Chưa có sản phẩm khác nào.</div>

        <div class="mt-4 space-y-1.5 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-slate-700">Tạm tính</span>
            <span class="font-medium text-slate-900">{{ formatMoney(subtotal) }}</span>
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
              <button type="button" class="inline-flex h-5 w-5 items-center justify-center text-slate-400" @click="discountType = 'none'; discountValue = ''; surchargeAmount = ''">
                <RefreshCw class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </div>
      </section>

      <section class="app-card">
        <h2 class="text-base font-medium text-slate-900">Thông tin khách hàng</h2>

        <div class="mt-3 space-y-3">
          <div>
            <label class="mb-1 block text-sm text-slate-700">Khách hàng</label>
            <div class="app-segment">
              <button type="button" class="app-segment-item" :class="customerMode === 'existing' ? 'app-segment-item-active' : ''" @click="customerMode = 'existing'">Khách cũ</button>
              <button type="button" class="app-segment-item" :class="customerMode === 'new' ? 'app-segment-item-active' : ''" @click="customerMode = 'new'">Khách mới</button>
              <button type="button" class="app-segment-item" :class="customerMode === 'guest' ? 'app-segment-item-active' : ''" @click="customerMode = 'guest'">Khách lẻ</button>
            </div>
          </div>

          <div v-if="showExistingCustomerPicker" class="space-y-1">
            <span class="mb-1 block text-sm text-slate-700">Khách cũ</span>
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-lg border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-left text-sm text-amber-800 hover:border-amber-400 hover:bg-amber-100"
              @click="openCustomerModal"
            >
              <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                  <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M10 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
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

          <div v-if="showNewCustomerFields" class="grid gap-2 md:grid-cols-2">
            <label class="space-y-1">
              <span class="mb-1 block text-sm text-slate-700">Tên khách hàng</span>
              <input v-model="customerName" type="text" class="app-input" />
            </label>
            <label class="space-y-1">
              <span class="mb-1 block text-sm text-slate-700">Số điện thoại</span>
              <input v-model="customerPhone" type="text" class="app-input" />
            </label>
            <label class="space-y-1 md:col-span-2">
              <span class="mb-1 block text-sm text-slate-700">Địa chỉ</span>
              <input v-model="customerAddress" type="text" class="app-input" />
            </label>
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-700">Thanh toán</label>
            <div class="app-segment">
              <button type="button" class="app-segment-item" :class="paymentStatus === 'pay' ? 'app-segment-item-active' : ''" @click="paymentStatus = 'pay'">Thanh toán</button>
              <button type="button" class="app-segment-item" :class="paymentStatus === 'debt' ? 'app-segment-item-active' : ''" @click="paymentStatus = 'debt'">Ghi nợ</button>
            </div>
          </div>

          <div v-if="isPayNow">
            <label class="mb-1 block text-sm text-slate-700">Hình thức thanh toán</label>
            <div class="app-segment">
              <button type="button" class="app-segment-item" :class="paymentMethod === 'cash' ? 'app-segment-item-active' : ''" @click="paymentMethod = 'cash'">Tiền mặt</button>
              <button type="button" class="app-segment-item" :class="paymentMethod === 'bank' ? 'app-segment-item-active' : ''" @click="paymentMethod = 'bank'">Chuyển khoản</button>
            </div>
          </div>

          <div v-if="isPayNow" class="relative">
            <label class="mb-1 block text-sm text-slate-700">Số tiền thanh toán</label>
            <input v-model="paymentAmount" type="text" inputmode="numeric" class="app-input pr-8 text-right" />
            <span class="pointer-events-none absolute inset-y-0 right-3 mt-6 flex items-center text-sm text-slate-500">đ</span>
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-700">Ghi chú</label>
            <textarea v-model="note" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
          </div>

          <button type="submit" class="app-btn-primary w-full" :disabled="submitting">Nhập đơn</button>
        </div>
      </section>

      <transition name="app-modal-fade-up">
        <div v-if="showPriceModal" class="app-modal-overlay app-modal-open" @click.self="closePriceModal">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <div class="app-modal-title">Chỉnh đơn giá</div>
              <button type="button" class="app-modal-close" @click="closePriceModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body">
              <div class="mb-3 text-sm text-slate-600">{{ editingPriceProductLabel }}</div>
              <div class="mb-4 space-y-1 rounded-lg bg-slate-50 p-2 text-sm text-slate-600">
                <div class="flex items-center justify-between">
                  <span>Giá gốc</span>
                  <span class="font-medium text-slate-900">{{ formatMoney(getCartItemBasePrice(editingPriceItem)) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Đơn giá hiện tại</span>
                  <span class="font-medium text-slate-900">{{ formatMoney(editingPriceItem?.price || 0) }}</span>
                </div>
              </div>
              <label class="space-y-1">
                <span class="mb-1 block text-sm text-slate-700">Đơn giá mới</span>
                <div class="relative">
                  <input v-model="priceDraftValue" type="text" inputmode="numeric" class="app-input pr-8 text-right font-medium text-slate-900" />
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
        <div v-if="showProductSelector" class="app-modal-overlay app-modal-open" @click.self="closeProductSelector">
          <div class="app-modal-sheet">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Chọn sản phẩm</h2>
              <button type="button" class="app-modal-close" @click="closeProductSelector">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body pt-2 pb-3">
              <div class="mb-2">
                <input
                  v-model="productSelectorKeyword"
                  type="search"
                  placeholder="Tìm sản phẩm..."
                  class="app-input"
                />
              </div>
              <div class="max-h-[60vh] overflow-y-auto rounded-lg border border-slate-200">
                <button
                  v-for="product in filteredProducts"
                  :key="product.id"
                  type="button"
                  class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-left last:border-b-0"
                  @click="toggleProductSelection(product.id)"
                >
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-slate-900">{{ product.name }}<span v-if="getDefaultUnit(product.id)" class="text-slate-500"> - {{ getDefaultUnit(product.id).unit_name }}</span></div>
                    <div class="mt-0.5 text-sm font-medium text-brand-700">{{ formatMoney(getDefaultUnit(product.id)?.price_sell || 0) }}</div>
                  </div>
                  <span class="inline-flex h-5 w-5 items-center justify-center rounded-md border text-xs font-semibold"
                    :class="isProductSelected(product.id) ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-transparent'">
                    ✓
                  </span>
                </button>
                <div v-if="!filteredProducts.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">
                  Không tìm thấy sản phẩm phù hợp.
                </div>
              </div>
            </div>
            <div class="app-modal-footer">
              <button type="button" class="app-btn-secondary" @click="closeProductSelector">Hủy</button>
              <button type="button" class="app-btn-primary" :disabled="!selectedProductIds.length" @click="applySelectedProducts">
                Áp dụng{{ selectedProductIds.length ? ` (${selectedProductIds.length})` : '' }}
              </button>
            </div>
          </div>
        </div>
      </transition>

      <transition name="app-modal-fade-up">
        <div v-if="showCustomerModal" class="app-modal-overlay app-modal-open" @click.self="closeCustomerModal">
          <div class="app-modal-sheet">
            <div class="app-modal-header">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                  <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M10 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
                <h2 class="app-modal-title">Chọn khách hàng</h2>
              </div>
              <button type="button" class="app-modal-close" @click="closeCustomerModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body flex min-h-0 flex-col pt-2 pb-3">
              <div class="mb-2">
                <input v-model="customerKeyword" type="search" placeholder="Tìm theo tên, SĐT, địa chỉ..." class="app-input" />
              </div>
              <div class="flex-1 overflow-y-auto rounded-lg border border-slate-200">
                <button
                  v-for="customer in filteredCustomers"
                  :key="customer.id"
                  type="button"
                  class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-left last:border-b-0"
                  @click="pendingCustomerId = String(customer.id)"
                >
                  <div class="flex min-w-0 items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                      <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M10 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 7a6 6 0 0 1 12 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                    </span>
                    <div class="min-w-0">
                      <div class="truncate font-medium text-slate-900">{{ customer.name }}<span v-if="customer.phone"> - {{ customer.phone }}</span></div>
                      <div v-if="customer.address" class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ customer.address }}</div>
                    </div>
                  </div>
                  <span v-if="isPendingCustomer(customer.id)" class="inline-flex items-center gap-1 text-xs font-medium text-brand-700">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-lg bg-brand-100 text-brand-700">✓</span>
                    <span>Đã chọn</span>
                  </span>
                </button>
                <div v-if="!filteredCustomers.length" class="px-3 py-4 text-center text-sm text-slate-500">Chưa có khách hàng phù hợp.</div>
              </div>
            </div>
            <div class="app-modal-footer">
              <button type="button" class="app-btn-secondary" @click="closeCustomerModal">Hủy</button>
              <button type="button" class="app-btn-primary" @click="applySelectedCustomer">Chọn</button>
            </div>
          </div>
        </div>
      </transition>

      <transition name="app-modal-fade-up">
        <div v-if="showDiscountModal" class="app-modal-overlay app-modal-open" @click.self="closeDiscountModal">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Giảm giá</h2>
              <button type="button" class="app-modal-close" @click="closeDiscountModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-3">
              <div>
                <label class="mb-1 block text-sm text-slate-700">Loại giảm giá</label>
                <div class="app-segment">
                  <button type="button" class="app-segment-item" :class="discountDraftType === 'none' ? 'app-segment-item-active' : ''" @click="discountDraftType = 'none'">Không giảm</button>
                  <button type="button" class="app-segment-item" :class="discountDraftType === 'fixed' ? 'app-segment-item-active' : ''" @click="discountDraftType = 'fixed'">Cố định</button>
                  <button type="button" class="app-segment-item" :class="discountDraftType === 'percent' ? 'app-segment-item-active' : ''" @click="discountDraftType = 'percent'">%</button>
                </div>
              </div>
              <div class="relative" v-if="discountDraftType !== 'none'">
                <label class="mb-1 block text-sm text-slate-700">Giá trị giảm giá</label>
                <input v-model="discountDraftValue" type="text" inputmode="numeric" data-money-format="off" class="app-input pr-8 text-right" @input="onDiscountDraftValueInput" />
                <span class="pointer-events-none absolute inset-y-0 right-3 mt-6 flex items-center text-sm text-slate-500">{{ discountDraftSuffix }}</span>
              </div>
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
              <h2 class="app-modal-title">Phụ thu</h2>
              <button type="button" class="app-modal-close" @click="closeSurchargeModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-3">
              <label>
                <span class="mb-1 block text-sm text-slate-700">Số tiền phụ thu</span>
                <div class="relative">
                  <input v-model="surchargeDraftValue" type="text" inputmode="numeric" class="app-input pr-8 text-right" />
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

      <transition name="app-modal-fade-up">
        <div v-if="showManualItemModal" class="app-modal-overlay app-modal-open" @click.self="closeManualItemModal">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">{{ editingManualIndex === null ? 'Thêm sản phẩm khác' : 'Sửa sản phẩm khác' }}</h2>
              <button type="button" class="app-modal-close" @click="closeManualItemModal">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-3">
              <label class="space-y-1">
                <span class="mb-1 block text-sm text-slate-700">Tên hàng</span>
                <input v-model="manualItemDraft.item_name" type="text" class="app-input" />
              </label>
              <div class="grid gap-2 md:grid-cols-2">
                <label class="space-y-1">
                  <span class="mb-1 block text-sm text-slate-700">Đơn vị</span>
                  <input v-model="manualItemDraft.unit_name" type="text" class="app-input" />
                </label>
                <label class="space-y-1">
                  <span class="mb-1 block text-sm text-slate-700">Số lượng</span>
                  <input v-model="manualItemDraft.qty" type="number" min="0" step="0.01" class="app-input" />
                </label>
                <label class="space-y-1">
                  <span class="mb-1 block text-sm text-slate-700">Giá vốn</span>
                  <div class="relative">
                    <input v-model="manualItemDraft.price_buy" type="text" inputmode="numeric" class="app-input pr-8 text-right" />
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                  </div>
                </label>
                <label class="space-y-1">
                  <span class="mb-1 block text-sm text-slate-700">Giá bán</span>
                  <div class="relative">
                    <input v-model="manualItemDraft.price_sell" type="text" inputmode="numeric" class="app-input pr-8 text-right" />
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
    </form>
  </section>
</template>