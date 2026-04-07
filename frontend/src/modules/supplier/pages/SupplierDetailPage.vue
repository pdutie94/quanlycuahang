<script setup>
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime } = useFormat();
import { onMounted, ref, computed } from 'vue';
// Tổng hợp số liệu cho 3 box đầu trang
const summary = computed(() => {
  let total_amount = 0;
  let total_paid = 0;
  let total_debt = 0;
  for (const p of purchases.value) {
    total_amount += Number(p.total_amount || 0);
    total_paid += Number(p.paid_amount || 0);
  }
  total_debt = total_amount - total_paid;
  return { total_amount, total_paid, total_debt };
});
import PurchaseListItemCard from '../../purchase/components/PurchaseListItemCard.vue';
import { Pencil, Trash2, ReceiptText } from '@lucide/vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useSupplierDetail } from '../composables/useSupplierDetail';
import { useToast } from '../../../shared/composables/useToast';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { supplier, purchases, totalDebt, loading, error, load, remove, deleteLoading, deleteError } = useSupplierDetail();
const showDeleteModal = ref(false);

// Đã thay thế bằng useFormat

const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
  } catch (_err) {
    toast.error(error.value || 'Không thể tải chi tiết nhà cung cấp.');
  }
};

const deleteCurrentSupplier = async () => {
  if (!supplier.value?.id) {
    return;
  }

  try {
    const payload = await remove(supplier.value.id);
    showDeleteModal.value = false;
    toast.success(payload?.message || 'Đã xóa nhà cung cấp.');
    router.push('/suppliers');
  } catch (_err) {
    toast.error(deleteError.value || 'Không thể xóa nhà cung cấp.');
  }
};

onMounted(async () => {
  await loadPage();
});

// Map lại dữ liệu purchase để tương thích với component chung
function mapPurchase(purchase) {
  // Đảm bảo có các field: supplier_name, status, total_amount, paid_amount, purchase_code, purchase_date
  return {
    ...purchase,
    supplier_name: supplier.value?.name || purchase.supplier_name || 'Chưa có nhà cung cấp',
    status: Number(purchase.debt_amount || 0) > 0 ? 'unpaid' : 'paid',
    paid_amount: purchase.paid_amount,
    total_amount: purchase.total_amount,
    purchase_code: purchase.purchase_code,
    purchase_date: purchase.purchase_date,
  };
}
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="supplier ? `Nhà cung cấp ${supplier.name}` : 'Chi tiết nhà cung cấp'" back-to="/suppliers">
      <template #actions="{ closeMenu }">
        <template v-if="supplier">
          <RouterLink
            v-if="Number(summary.total_debt || 0) > 0"
            :to="{ name: 'suppliers.debtPayment', params: { id: supplier.id } }"
            class="detail-header-menu-item detail-header-menu-item-amber"
            @click="closeMenu"
          >
            <ReceiptText class="h-4 w-4 shrink-0" />
            <span>Thanh toán</span>
          </RouterLink>
          <RouterLink :to="{ name: 'suppliers.edit', params: { id: supplier.id } }" class="detail-header-menu-item" @click="closeMenu"><Pencil class="h-4 w-4 shrink-0" /><span>Chỉnh sửa</span></RouterLink>
          <button type="button" class="detail-header-menu-item detail-header-menu-item-rose" @click="closeMenu(); showDeleteModal = true"><Trash2 class="h-4 w-4 shrink-0" /><span>Xóa nhà cung cấp</span></button>
        </template>
      </template>
    </DetailHeaderBar>

    <ActionConfirmSheet
      :open="showDeleteModal"
      title="Xóa nhà cung cấp"
      description="Bạn chắc chắn muốn xóa nhà cung cấp này?"
      confirm-label="Xóa nhà cung cấp"
      :loading="deleteLoading"
      @cancel="showDeleteModal = false"
      @confirm="deleteCurrentSupplier"
    />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!supplier" class="app-empty-state">Không tìm thấy nhà cung cấp.</div>
    <template v-else>
      <section class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
        <div class="text-base font-semibold text-slate-800">{{ supplier.name }}</div>
        <div class="mt-1 text-sm text-slate-600">
          <span v-if="supplier.phone" class="mr-4">SĐT: {{ supplier.phone }}</span>
          <span v-if="supplier.address">Địa chỉ: {{ supplier.address }}</span>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
          <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="text-slate-500">Tổng nợ</div>
            <div class="mt-1 font-medium text-slate-900">{{ formatMoney(summary.total_amount) }}</div>
          </div>
          <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="text-slate-500">Thanh toán</div>
            <div class="mt-1 font-medium text-brand-700">{{ formatMoney(summary.total_paid) }}</div>
          </div>
          <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="text-slate-500">Còn nợ</div>
            <div class="mt-1 font-medium" :class="Number(summary.total_debt || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(summary.total_debt) }}</div>
          </div>
        </div>
      </section>

      <section v-if="!purchases.length" class="app-empty-state">Nhà cung cấp chưa có phiếu nhập nào.</section>

      <section v-else class="space-y-3">
        <div class="text-sm font-medium text-slate-600">Phiếu nhập</div>
        <div v-for="purchase in purchases" :key="purchase.id">
          <RouterLink :to="{ name: 'purchases.detail', params: { id: purchase.id } }" class="block">
            <PurchaseListItemCard
              :purchase="mapPurchase(purchase)"
              :show-supplier-name="false"
              :show-code-on-top="true"
              :format-money="formatMoney"
              :format-date-time="formatDateTime"
            />
          </RouterLink>
        </div>
      </section>
    </template>
  </section>
</template>
