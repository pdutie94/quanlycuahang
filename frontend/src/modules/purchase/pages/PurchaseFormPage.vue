<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { usePurchaseForm } from '../composables/usePurchaseForm';
import { useToast } from '../../../shared/composables/useToast';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const {
  suppliers,
  productUnits,
  form,
  rows,
  purchase,
  rowDisplayMap,
  summary,
  bootstrapLoading,
  bootstrapError,
  loadBootstrap,
  loadEdit,
  addRow,
  removeRow,
  submitCreate,
  createLoading,
  createError,
  submitUpdate,
  updateLoading,
  updateError
} = usePurchaseForm();

const isEdit = computed(() => Boolean(route.params.id));
const pageTitle = computed(() => (isEdit.value ? 'Chỉnh sửa phiếu nhập' : 'Tạo phiếu nhập hàng'));
const loading = computed(() => bootstrapLoading.value);
const saving = computed(() => createLoading.value || updateLoading.value);
const showProductSelector = ref(false);
const productKeyword = ref('');
const activeRowIndex = ref(null);

const formatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;

const normalizeText = (value) => String(value || '').toLowerCase();

const filteredUnits = computed(() => {
  const keyword = normalizeText(productKeyword.value).trim();
  if (!keyword) {
    return productUnits.value.slice(0, 60);
  }

  return productUnits.value.filter((unit) => {
    const text = `${normalizeText(unit.product_name)} ${normalizeText(unit.unit_name)}`;
    return text.includes(keyword);
  }).slice(0, 60);
});

const getUnitDisplay = (row) => rowDisplayMap.value.get(String(row.product_unit_id)) || null;

const fillRowFromUnit = (row) => {
  const unit = rowDisplayMap.value.get(String(row.product_unit_id));
  if (!unit) {
    return;
  }
  if (!row.price_cost) {
    row.price_cost = String(Math.round(Number(unit.price_cost || 0)));
  }
};

const syncAmount = (row) => {
  const qty = Number(row.qty || 0);
  const price = Number(row.price_cost || 0);
  const amount = qty * price;
  row.amount = amount > 0 ? String(Math.round(amount)) : '';
};

const openProductSelector = (rowIndex = null) => {
  activeRowIndex.value = rowIndex;
  productKeyword.value = '';
  showProductSelector.value = true;
};

const selectProductUnit = (unit) => {
  const unitId = String(unit.id);
  const priceCost = String(Math.round(Number(unit.price_cost || 0)));

  if (activeRowIndex.value === null || activeRowIndex.value < 0 || activeRowIndex.value >= rows.value.length) {
    addRow({
      product_unit_id: unitId,
      qty: '1',
      price_cost: priceCost,
      amount: priceCost,
      update_cost: false
    });
  } else {
    const row = rows.value[activeRowIndex.value];
    row.product_unit_id = unitId;
    if (!row.qty) {
      row.qty = '1';
    }
    row.price_cost = priceCost;
    syncAmount(row);
  }

  showProductSelector.value = false;
  activeRowIndex.value = null;
};

const submit = async () => {
  try {
    const payload = isEdit.value
      ? await submitUpdate(Number(route.params.id))
      : await submitCreate();

    toast.success(payload?.message || (isEdit.value ? 'Đã cập nhật phiếu nhập hàng.' : 'Đã tạo phiếu nhập hàng.'));
    const nextId = Number(payload?.data?.id || route.params.id || 0);
    if (nextId > 0) {
      await router.push({ name: 'purchases.detail', params: { id: nextId } });
      return;
    }
    await router.push('/purchases');
  } catch (_err) {
    toast.error((isEdit.value ? updateError.value : createError.value) || 'Không thể lưu phiếu nhập.');
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
    toast.error(bootstrapError.value || 'Không thể tải dữ liệu form phiếu nhập.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <div>
        <RouterLink :to="isEdit ? { name: 'purchases.detail', params: { id: route.params.id } } : '/purchases'" class="text-sm font-medium text-slate-500 hover:text-slate-700">
          {{ isEdit ? 'Quay lại chi tiết' : 'Quay lại danh sách' }}
        </RouterLink>
        <h1 class="mt-1 text-lg font-semibold text-slate-900">{{ pageTitle }}</h1>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải dữ liệu form...</div>

    <template v-else>
      <section class="app-card space-y-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Nhà cung cấp</label>
          <select v-model="form.supplier_id" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500">
            <option value="">Chọn nhà cung cấp</option>
            <option v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">{{ supplier.name }}</option>
          </select>
        </div>

        <div class="rounded-xl border border-slate-200">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">
            <span>Danh sách sản phẩm</span>
            <button type="button" class="inline-flex h-9 items-center rounded-lg border border-brand-600 px-3 text-sm font-medium text-brand-700" @click="openProductSelector()">Thêm SP</button>
          </div>
          <div class="space-y-3 p-4">
            <div v-if="!rows.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-center text-sm text-slate-500">Chưa có sản phẩm nào.</div>
            <div v-for="(row, index) in rows" :key="index" class="rounded-xl border border-slate-200 bg-white p-3">
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
                  <div class="grid gap-3 md:grid-cols-4">
                    <div>
                      <label class="mb-1 block text-sm font-medium text-slate-700">Số lượng</label>
                      <input v-model="row.qty" type="number" min="0" step="0.001" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" @input="syncAmount(row)" />
                    </div>
                    <div>
                      <label class="mb-1 block text-sm font-medium text-slate-700">Giá nhập</label>
                      <input v-model="row.price_cost" type="number" min="0" step="1000" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" @input="syncAmount(row)" @focus="fillRowFromUnit(row)" />
                    </div>
                    <div>
                      <label class="mb-1 block text-sm font-medium text-slate-700">Thành tiền</label>
                      <input v-model="row.amount" type="number" min="0" step="1000" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
                    </div>
                    <label class="flex items-end gap-2 pb-2 text-sm text-slate-700">
                      <input v-model="row.update_cost" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600" />
                      <span>Cập nhật giá vốn</span>
                    </label>
                  </div>
                </div>
                <button type="button" class="text-sm font-medium text-rose-600 hover:text-rose-700" @click="removeRow(index)">Xóa</button>
              </div>
            </div>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <div class="flex items-center justify-between"><span class="text-slate-600">Tổng số lượng</span><span class="font-medium text-slate-900">{{ summary.totalQty }}</span></div>
            <div class="mt-2 flex items-center justify-between"><span class="text-slate-600">Tổng tiền hàng</span><span class="font-medium text-brand-700">{{ formatMoney(summary.totalAmount) }}</span></div>
          </div>
          <div class="space-y-3">
            <template v-if="!isEdit">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Trạng thái thanh toán</label>
                <select v-model="form.payment_status" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500">
                  <option value="pay">Thanh toán</option>
                  <option value="debt">Ghi nợ</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Hình thức thanh toán</label>
                <select v-model="form.payment_method" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500">
                  <option value="cash">Tiền mặt</option>
                  <option value="bank">Chuyển khoản</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Số tiền thanh toán</label>
                <input v-model="form.paid_amount" type="number" min="0" step="1000" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
              </div>
            </template>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Ghi chú</label>
              <textarea v-model="form.note" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500" placeholder="Nhập ghi chú cho phiếu nhập này..."></textarea>
            </div>
          </div>
        </div>

        <button type="button" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="saving" @click="submit">
          {{ isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu' }}
        </button>
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
                <div class="mt-1 text-sm text-slate-600">{{ unit.unit_name }} • Giá vốn {{ formatMoney(unit.price_cost || 0) }}</div>
              </button>
              <div v-if="!filteredUnits.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">Không tìm thấy sản phẩm phù hợp.</div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>