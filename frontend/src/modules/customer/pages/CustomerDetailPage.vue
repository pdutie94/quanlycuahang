<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Pencil, ReceiptText, Trash2 } from '@lucide/vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useCustomerDetail } from '../composables/useCustomerDetail';
import { useToast } from '../../../shared/composables/useToast';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { customer, orders, summary, paymentHistory, loading, error, load, remove, deleteLoading, deleteError } = useCustomerDetail();
const showDeleteModal = ref(false);

import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime } = useFormat();

const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
  } catch (_err: any) {
    toast.error(error.value || 'Không thể tải chi tiết khách hàng.');
  }
};

const deleteCurrentCustomer = async () => {
  if (!customer.value?.id) {
    return;
  }

  try {
    const payload = await remove(customer.value.id);
    showDeleteModal.value = false;
    toast.success(payload?.message || 'Đã xóa khách hàng.');
    router.push('/customers');
  } catch (_err: any) {
    toast.error(deleteError.value || 'Không thể xóa khách hàng.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="customer ? `Khách hàng ${customer.name}` : 'Chi tiết khách hàng'" back-to="/customers">
      <template #actions="{ closeMenu }">
        <template v-if="customer">
          <RouterLink v-if="Number(summary.total_debt || 0) > 0" :to="{ name: 'customers.debtPayment', params: { id: customer.id } }" class="detail-header-menu-item detail-header-menu-item-amber" @click="closeMenu"><ReceiptText class="h-4 w-4 shrink-0" /><span>Thu tiền</span></RouterLink>
          <RouterLink :to="{ name: 'customers.edit', params: { id: customer.id } }" class="detail-header-menu-item" @click="closeMenu"><Pencil class="h-4 w-4 shrink-0" /><span>Chỉnh sửa</span></RouterLink>
          <button type="button" class="detail-header-menu-item detail-header-menu-item-rose" @click="closeMenu(); showDeleteModal = true"><Trash2 class="h-4 w-4 shrink-0" /><span>Xóa khách hàng</span></button>
        </template>
      </template>
    </DetailHeaderBar>

    <ActionConfirmSheet
      :open="showDeleteModal"
      title="Xóa khách hàng"
      description="Xóa khách hàng sẽ chuyển các đơn hàng về khách lẻ. Bạn chắc chắn muốn xóa?"
      confirm-label="Xóa khách hàng"
      :loading="deleteLoading"
      @cancel="showDeleteModal = false"
      @confirm="deleteCurrentCustomer"
    />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!customer" class="app-empty-state">Không tìm thấy khách hàng.</div>
    <template v-else>
      <section class="app-card">
        <div class="text-sm font-medium text-slate-800">{{ customer.name }}</div>
        <div class="mt-1 text-sm text-slate-600">
          <span v-if="customer.phone" class="mr-4">SĐT: {{ customer.phone }}</span>
          <span v-if="customer.address">Địa chỉ: {{ customer.address }}</span>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Tổng tiền</div><div class="mt-1 font-medium text-slate-900">{{ formatMoney(summary.total_amount) }}</div></div>
          <div class="rounded-md bg-brand-50 px-3 py-2"><div class="text-brand-600">Đã thu</div><div class="mt-1 font-medium text-brand-700">{{ formatMoney(summary.total_paid) }}</div></div>
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Còn nợ</div><div class="mt-1 font-medium" :class="Number(summary.total_debt || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(summary.total_debt) }}</div></div>
        </div>
      </section>

      <section class="space-y-3">
        <div class="text-sm font-medium text-slate-600">Lịch sử thanh toán</div>
        <div v-if="!paymentHistory.length" class="app-empty-state">Chưa có lịch sử thanh toán.</div>
        <div v-else class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white">
          <div
            v-for="item in paymentHistory"
            :key="item.id"
            class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0"
          >
            <div class="min-w-0 flex-1 text-sm">
              <div class="text-slate-400">{{ formatDateTime(item.paid_at) }}</div>
              <div v-if="item.note" class="mt-0.5 truncate text-slate-500">{{ item.note }}</div>
            </div>
            <div class="shrink-0 text-sm font-semibold text-emerald-600">+{{ formatMoney(item.amount) }}</div>
          </div>
        </div>
      </section>

      <section v-if="!orders.length" class="app-empty-state">Khách hàng chưa có đơn hàng nào.</section>

      <section v-else class="space-y-3">
        <div class="text-sm font-medium text-slate-600">Đơn hàng</div>
        <OrderItemCard
          v-for="order in orders"
          :key="order.id"
          :order="order"
          :to="{ name: 'orders.detail', params: { id: order.id } }"
          customer-fallback="Khách lẻ"
        >
          <!-- Không còn nút Thu tiền -->
        </OrderItemCard>
      </section>
    </template>
  </section>
</template>