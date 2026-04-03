<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useOrderForm } from '../composables/useOrderForm';
import { useToast } from '../../../shared/composables/useToast';

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

const isEdit = computed(() => Boolean(route.params.id));
const pageTitle = computed(() => (isEdit.value ? 'Sửa đơn hàng' : 'Tạo đơn hàng'));
const loading = computed(() => bootstrapLoading.value || (isEdit.value && detailLoading.value));
const saving = computed(() => createLoading.value || updateLoading.value);

const showProductSelector = ref(false);
const productKeyword = ref('');
const activeRowIndex = ref(null);

const formatter = new Intl.NumberFormat('vi-VN');

const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;

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

const discountAmount = computed(() => {
  const gross = Number(summary.value.gross || 0);
  const value = parseAmount(form.value.discount_value);
  if (form.value.discount_type === 'fixed') {
    return Math.min(value, gross);
  }
  if (form.value.discount_type === 'percent') {
    const percent = Math.min(value, 100);
    return Math.round(gross * percent / 100);
  }
  return 0;
});

const surchargeValue = computed(() => parseAmount(form.value.surcharge_amount));

const finalTotal = computed(() => {
  const total = Number(summary.value.gross || 0) - discountAmount.value + surchargeValue.value;
  return roundDownThousand(total < 0 ? 0 : total);
});

const normalizeText = (value) => String(value || '').toLowerCase();

const filteredUnits = computed(() => {
  const keyword = normalizeText(productKeyword.value).trim();
  if (!keyword) {
    return productUnits.value.slice(0, 60);
  }

  return productUnits.value
    .filter((unit) => {
      const text = `${normalizeText(unit.product_name)} ${normalizeText(unit.unit_name)}`;
      return text.includes(keyword);
    })
    .slice(0, 60);
});

const getUnitDisplay = (row) => rowDisplayMap.value.get(String(row.product_unit_id)) || null;

const syncRowPrice = (row) => {
  const unit = rowDisplayMap.value.get(String(row.product_unit_id));
  if (!unit) {
    return;
  }
  if (!row.price) {
    row.price = String(Math.round(Number(unit.price_sell || 0)));
  }
};

const applyPriceX1000 = (obj, field) => {
  const num = Number(obj[field] || 0);
  if (num > 0 && num < 1000) {
    obj[field] = num * 1000;
  }
};

const openProductSelector = (rowIndex = null) => {
  activeRowIndex.value = rowIndex;
  productKeyword.value = '';
  showProductSelector.value = true;
};

const selectProductUnit = (unit) => {
  const nextUnitId = String(unit.id);
  const nextPrice = String(Math.round(Number(unit.price_sell || 0)));

  if (activeRowIndex.value === null || activeRowIndex.value < 0 || activeRowIndex.value >= rows.value.length) {
    addRow({
      product_unit_id: nextUnitId,
      qty: '1',
      price: nextPrice
    });
  } else {
    const row = rows.value[activeRowIndex.value];
    row.product_unit_id = nextUnitId;
    if (!row.qty) {
      row.qty = '1';
    }
    row.price = nextPrice;
  }

  showProductSelector.value = false;
  activeRowIndex.value = null;
};

const submit = async () => {
  try {
    const payload = isEdit.value
      ? await submitUpdate(Number(route.params.id))
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

onMounted(async () => {
  try {
    await loadBootstrap();
    if (isEdit.value) {
      await loadEdit(Number(route.params.id));
    } else if (!rows.length) {
      openProductSelector();
    }
  } catch (_err) {
    toast.error(bootstrapError.value || detailError.value || 'Không thể tải dữ liệu đơn hàng.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <div>
        <RouterLink :to="isEdit ? { name: 'orders.detail', params: { id: route.params.id } } : '/orders'" class="text-sm font-medium text-slate-500 hover:text-slate-700">
          {{ isEdit ? 'Quay lại chi tiết' : 'Quay lại danh sách' }}
        </RouterLink>
        <h1 class="mt-1 text-lg font-semibold text-slate-900">{{ pageTitle }}</h1>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải dữ liệu form...</div>

    <template v-else>
      <section class="space-y-4 app-card">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr),minmax(0,1fr)]">
          <div class="space-y-4">
            <div class="rounded-xl border border-slate-200">
              <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">
                <span>Sản phẩm</span>
                <button type="button" class="inline-flex h-9 items-center rounded-lg border border-brand-600 px-3 text-sm font-medium text-brand-700" @click="openProductSelector()">Thêm SP</button>
              </div>
              <div class="space-y-3 p-4">
                <div v-if="!rows.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-center text-sm text-slate-500">Chưa có sản phẩm nào.</div>
                <div v-for="(row, index) in rows" :key="`row-${index}`" class="rounded-xl border border-slate-200 bg-white p-3">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 space-y-3">
                      <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Sản phẩm / đơn vị</label>
                        <button type="button" class="flex h-10 w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-3 text-left text-sm outline-none hover:border-brand-400" @click="openProductSelector(index)">
                          <span class="truncate text-slate-700">
                            {{ getUnitDisplay(row) ? `${getUnitDisplay(row).product_name} / ${getUnitDisplay(row).unit_name}` : 'Chọn sản phẩm' }}
                          </span>
                          <span class="text-brand-700">Đổi</span>
                        </button>
                      </div>
                      <div class="grid gap-3 md:grid-cols-2">
                        <div>
                          <label class="mb-1 block text-sm font-medium text-slate-700">Số lượng</label>
                          <input v-model="row.qty" type="number" min="0" step="0.001" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
                        </div>
                        <div>
                          <label class="mb-1 block text-sm font-medium text-slate-700">Đơn giá</label>
                          <input v-model="row.price" type="number" min="0" step="1000" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" @focus="syncRowPrice(row)" @blur="applyPriceX1000(row, 'price')" />
                        </div>
                      </div>
                    </div>
                    <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-700" @click="removeRow(index)">Xóa</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-amber-50/40 p-4">
              <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium text-slate-800">Sản phẩm tự do</h2>
                <button type="button" class="text-sm font-medium text-brand-700 hover:text-brand-800" @click="addManualItem()">Thêm dòng</button>
              </div>

              <div v-if="!manualItems.length" class="mt-3 rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">Chưa có sản phẩm tự do.</div>

              <div v-else class="mt-3 space-y-3">
                <div v-for="(item, index) in manualItems" :key="`manual-${index}`" class="rounded-xl border border-amber-200 bg-white p-3">
                  <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-5">
                    <input v-model="item.item_name" type="text" placeholder="Tên hàng" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
                    <input v-model="item.unit_name" type="text" placeholder="Đơn vị" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
                    <input v-model="item.qty" type="number" min="0" step="0.01" placeholder="Số lượng" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
                    <input v-model="item.price_buy" type="number" min="0" step="1000" placeholder="Giá vốn" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" @blur="applyPriceX1000(item, 'price_buy')" />
                    <input v-model="item.price_sell" type="number" min="0" step="1000" placeholder="Giá bán" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" @blur="applyPriceX1000(item, 'price_sell')" />
                  </div>
                  <div class="mt-2 text-right">
                    <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-700" @click="removeManualItem(index)">Xóa</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <section class="rounded-xl border border-slate-200 p-4">
              <h2 class="text-sm font-medium text-slate-800">Khách hàng</h2>
              <div class="mt-3 space-y-3">
                <label class="space-y-1">
                  <span class="app-label">Khách hàng</span>
                  <div class="relative">
                    <select v-model="form.customer_id" class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500" @change="syncCustomerFields">
                      <option value="">Khách lẻ / nhập mới</option>
                      <option v-for="customer in customers" :key="customer.id" :value="String(customer.id)">{{ customer.name }}<span v-if="customer.phone"> - {{ customer.phone }}</span></option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                      <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                    </div>
                  </div>
                </label>
                <label class="space-y-1">
                  <span class="app-label">Tên khách hàng</span>
                  <input v-model="form.customer_name" type="text" placeholder="Tên khách hàng" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
                </label>
                <label class="space-y-1">
                  <span class="app-label">Số điện thoại</span>
                  <input v-model="form.customer_phone" type="text" placeholder="Số điện thoại" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
                </label>
                <label class="space-y-1">
                  <span class="app-label">Địa chỉ</span>
                  <textarea v-model="form.customer_address" rows="2" placeholder="Địa chỉ" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
                </label>
              </div>
            </section>

            <section class="rounded-xl border border-slate-200 p-4">
              <h2 class="text-sm font-medium text-slate-800">Thanh toán và tổng tiền</h2>
              <div class="mt-3 space-y-3 text-sm">
                <div class="flex items-center justify-between"><span class="text-slate-500">Tiền hàng</span><span class="font-medium text-slate-900">{{ formatMoney(summary.subtotal) }}</span></div>
                <div class="flex items-center justify-between"><span class="text-slate-500">Sản phẩm tự do</span><span class="font-medium text-slate-900">{{ formatMoney(summary.manualSellTotal) }}</span></div>
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="space-y-1">
                    <span class="app-label">Loại giảm giá</span>
                    <div class="relative">
                      <select v-model="form.discount_type" class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                        <option value="none">Không giảm giá</option>
                        <option value="fixed">Giảm giá cố định</option>
                        <option value="percent">Giảm giá %</option>
                      </select>
                      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                          <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </div>
                    </div>
                  </label>
                  <label class="space-y-1">
                    <span class="app-label">Giá trị giảm giá</span>
                    <div class="relative">
                      <input v-model="form.discount_value" type="text" inputmode="numeric" placeholder="Giá trị giảm giá" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
                      <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                    </div>
                  </label>
                </div>
                <div class="flex items-center justify-between"><span class="text-slate-500">Giảm giá</span><span class="font-medium text-rose-600">-{{ formatMoney(discountAmount) }}</span></div>
                <label class="space-y-1">
                  <span class="app-label">Phụ thu</span>
                  <div class="relative">
                    <input v-model="form.surcharge_amount" type="text" inputmode="numeric" placeholder="Phụ thu" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                  </div>
                </label>
                <div class="flex items-center justify-between"><span class="text-slate-500">Phụ thu</span><span class="font-medium text-amber-600">+{{ formatMoney(surchargeValue) }}</span></div>
                <div class="flex items-center justify-between border-t border-dashed border-slate-200 pt-3"><span class="font-medium text-slate-800">Tổng cộng</span><span class="text-lg font-semibold text-brand-700">{{ formatMoney(finalTotal) }}</span></div>

                <template v-if="!isEdit">
                  <div class="grid gap-2 md:grid-cols-2">
                    <label class="space-y-1">
                      <span class="app-label">Trạng thái thanh toán</span>
                      <div class="relative">
                        <select v-model="form.payment_status" class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                          <option value="pay">Thu tiền ngay</option>
                          <option value="debt">Ghi nợ</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </div>
                      </div>
                    </label>
                    <label class="space-y-1">
                      <span class="app-label">Hình thức thanh toán</span>
                      <div class="relative">
                        <select v-model="form.payment_method" class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                          <option value="cash">Tiền mặt</option>
                          <option value="bank">Chuyển khoản</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </div>
                      </div>
                    </label>
                  </div>
                  <label class="space-y-1">
                    <span class="app-label">Số tiền khách trả</span>
                    <div class="relative">
                      <input v-model="form.payment_amount" type="text" inputmode="numeric" placeholder="Số tiền khách trả" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
                      <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                    </div>
                  </label>
                </template>

                <label class="space-y-1">
                  <span class="app-label">Ghi chú đơn hàng</span>
                  <textarea v-model="form.note" rows="3" placeholder="Ghi chú đơn hàng" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
                </label>

                <button type="button" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="saving" @click="submit">
                  {{ isEdit ? 'Cập nhật đơn hàng' : 'Lưu đơn hàng' }}
                </button>
              </div>
            </section>

            <section v-if="isEdit && order" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
              Mã đơn: <span class="font-medium text-slate-900">#{{ order.order_code || order.id }}</span>
            </section>
          </div>
        </div>
      </section>

      <div v-if="showProductSelector" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/40 p-4 sm:items-center" @click.self="showProductSelector = false">
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Chọn sản phẩm</h2>
            <button type="button" class="text-sm font-medium text-slate-500 hover:text-slate-700" @click="showProductSelector = false">Đóng</button>
          </div>
          <div class="space-y-3 p-4">
            <input v-model="productKeyword" type="search" placeholder="Tìm theo tên sản phẩm / đơn vị" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
            <div class="max-h-80 space-y-2 overflow-y-auto">
              <button
                v-for="unit in filteredUnits"
                :key="unit.id"
                type="button"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-left transition hover:border-brand-300 hover:bg-brand-50"
                @click="selectProductUnit(unit)"
              >
                <div class="font-medium text-slate-900">{{ unit.product_name }}</div>
                <div class="mt-1 text-sm text-slate-600">{{ unit.unit_name }} • Giá bán {{ formatMoney(unit.price_sell || 0) }}</div>
              </button>
              <div v-if="!filteredUnits.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">Không tìm thấy sản phẩm phù hợp.</div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>
