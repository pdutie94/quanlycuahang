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
  updateCartUnit,
  removeCartItem,
  addManualItem,
  removeManualItem,
  subtotal,
  submitOrder,
  submitting,
  submitError
} = usePos();

const productKeyword = ref('');
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
const customerMode = ref('guest');
const showDiscountEditor = ref(false);
const showSurchargeEditor = ref(false);

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

const roundDownThousand = (value) => {
  const amount = parseAmount(value);
  if (amount <= 0) {
    return 0;
  }
  return Math.floor(amount / 1000) * 1000;
};

const filteredProducts = computed(() => {
  const keyword = productKeyword.value.trim().toLowerCase();
  if (!keyword) {
    return products.value.slice(0, 30);
  }

  return products.value
    .filter((product) => [product.name, product.code, product.category_name].some((field) => String(field || '').toLowerCase().includes(keyword)))
    .slice(0, 30);
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
    syncCustomerFields();
  }
});

const getUnits = (productId) => productUnitsByProduct.value[String(productId)] || productUnitsByProduct.value[productId] || [];

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
          <button type="button" class="app-btn-secondary !min-h-0 px-3 py-1 text-sm" @click="productKeyword = ''">Thêm SP</button>
        </div>

        <div class="mt-2">
          <input
            v-model="productKeyword"
            type="search"
            placeholder="Tìm sản phẩm để thêm..."
            class="app-input"
          />
        </div>

        <div v-if="cartItems.length" class="mt-3 space-y-2">
          <div v-for="item in cartItems" :key="item.id" class="rounded-xl border border-slate-200 bg-white px-3 py-2">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="text-sm font-medium text-slate-900">{{ item.product_name }}</div>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                  <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-300 text-slate-600" @click="item.quantity = Math.max(0, Number(item.quantity || 0) - 1)">
                    <Minus class="h-3.5 w-3.5" />
                  </button>
                  <input v-model="item.quantity" type="number" min="0" step="0.01" class="h-6 w-14 rounded-md border border-slate-300 text-center text-sm outline-none" />
                  <button type="button" class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-300 text-slate-600" @click="item.quantity = Number(item.quantity || 0) + 1">
                    <Plus class="h-3.5 w-3.5" />
                  </button>
                  <select :value="item.unit_id" class="h-6 rounded-md border border-slate-300 bg-white px-2 text-xs outline-none" @change="updateCartUnit(item, Number($event.target.value))">
                    <option v-for="unit in getUnits(item.product_id)" :key="unit.unit_id" :value="unit.unit_id">{{ unit.unit_name }}</option>
                  </select>
                  <button type="button" class="inline-flex items-center gap-1 text-slate-700" @click="showDiscountEditor = false">
                    <span class="font-medium">{{ formatMoney(item.price) }}</span>
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
          <button type="button" class="app-btn-secondary !min-h-0 px-3 py-1 text-sm" @click="addManualItem">Thêm SP</button>
        </div>

        <div v-if="manualItems.length" class="mt-3 space-y-2">
          <div v-for="(item, index) in manualItems" :key="`manual-${index}`" class="rounded-xl border border-amber-200 bg-amber-50/40 p-3">
            <div class="grid gap-2 md:grid-cols-2">
              <input v-model="item.item_name" type="text" placeholder="Tên hàng" class="app-input" />
              <input v-model="item.unit_name" type="text" placeholder="Đơn vị" class="app-input" />
              <input v-model="item.qty" type="number" min="0" step="0.01" placeholder="Số lượng" class="app-input" />
              <input v-model="item.price_sell" type="text" inputmode="numeric" placeholder="Giá bán" class="app-input" />
            </div>
            <div class="mt-2 flex justify-end">
              <button type="button" class="text-sm font-medium text-rose-600" @click="removeManualItem(index)">Xóa</button>
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
            <button type="button" class="inline-flex items-center gap-1 font-medium text-rose-600" @click="showDiscountEditor = !showDiscountEditor">
              <span>-{{ formatMoney(discountAmount) }}</span>
              <Pencil class="h-3 w-3" />
            </button>
          </div>
          <div v-if="showDiscountEditor" class="grid gap-2 md:grid-cols-2">
            <select v-model="discountType" class="app-input">
              <option value="none">Không giảm giá</option>
              <option value="fixed">Giảm giá cố định</option>
              <option value="percent">Giảm giá %</option>
            </select>
            <input v-model="discountValue" type="text" inputmode="numeric" placeholder="Giá trị giảm giá" class="app-input" />
          </div>

          <div class="flex items-center justify-between">
            <span class="text-slate-700">Phụ thu</span>
            <button type="button" class="inline-flex items-center gap-1 font-medium text-amber-600" @click="showSurchargeEditor = !showSurchargeEditor">
              <span>+{{ formatMoney(surchargeValue) }}</span>
              <CirclePlus class="h-3 w-3" />
            </button>
          </div>
          <div v-if="showSurchargeEditor">
            <input v-model="surchargeAmount" type="text" inputmode="numeric" placeholder="Phụ thu" class="app-input" />
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

        <div class="mt-3 grid gap-2 md:grid-cols-2">
          <button
            v-for="product in filteredProducts"
            :key="product.id"
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-sm hover:border-brand-300 hover:bg-brand-50"
            @click="addProduct(product) || toast.error('Sản phẩm chưa có đơn vị bán.')"
          >
            <div class="font-medium text-slate-900">{{ product.name }}</div>
            <div class="text-xs text-slate-500">{{ product.code || 'Không có mã' }}</div>
          </button>
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

          <div v-if="showExistingCustomerPicker">
            <select v-model="selectedCustomerId" class="app-input" @change="syncCustomerFields">
              <option value="">Chọn khách cũ</option>
              <option v-for="customer in customers" :key="customer.id" :value="String(customer.id)">{{ customer.name }}{{ customer.phone ? ` - ${customer.phone}` : '' }}</option>
            </select>
          </div>

          <div v-if="showNewCustomerFields" class="grid gap-2 md:grid-cols-2">
            <input v-model="customerName" type="text" placeholder="Tên khách hàng" class="app-input" />
            <input v-model="customerPhone" type="text" placeholder="Số điện thoại" class="app-input" />
            <input v-model="customerAddress" type="text" placeholder="Địa chỉ" class="app-input md:col-span-2" />
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
            <label class="app-label">Số tiền thanh toán</label>
            <input v-model="paymentAmount" type="text" inputmode="numeric" class="app-input pr-8 text-right" />
            <span class="pointer-events-none absolute inset-y-0 right-3 mt-6 flex items-center text-sm text-slate-500">đ</span>
          </div>

          <div>
            <label class="app-label">Ghi chú</label>
            <textarea v-model="note" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
          </div>

          <button type="submit" class="app-btn-primary w-full" :disabled="submitting">Nhập đơn</button>
        </div>
      </section>
    </form>
  </section>
</template>