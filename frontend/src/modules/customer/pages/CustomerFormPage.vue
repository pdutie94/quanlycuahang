<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useCustomerForm } from '../composables/useCustomerForm';
import { useToast } from '../../../shared/composables/useToast';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { form, customer, loading, error, loadEdit, submitCreate, createLoading, createError, submitUpdate, updateLoading, updateError } = useCustomerForm();

const isEdit = computed(() => Boolean(route.params.id));
const pageTitle = computed(() => (isEdit.value ? 'Sửa khách hàng' : 'Thêm khách hàng'));

const submit = async () => {
  try {
    const payload = isEdit.value ? await submitUpdate(Number(route.params.id)) : await submitCreate();
    toast.success(payload?.message || (isEdit.value ? 'Đã cập nhật thông tin khách hàng.' : 'Đã thêm khách hàng mới.'));
    const nextId = Number(payload?.data?.id || route.params.id || 0);
    if (nextId > 0) {
      await router.push({ name: 'customers.detail', params: { id: nextId } });
      return;
    }
    await router.push('/customers');
  } catch (_err) {
    toast.error((isEdit.value ? updateError.value : createError.value) || 'Không thể lưu khách hàng.');
  }
};

onMounted(async () => {
  if (!isEdit.value) return;
  try {
    await loadEdit(Number(route.params.id));
  } catch (_err) {
    toast.error(error.value || 'Không thể tải thông tin khách hàng.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <RouterLink :to="isEdit ? { name: 'customers.detail', params: { id: route.params.id } } : '/customers'" class="text-sm font-medium text-slate-500 hover:text-slate-700">
        {{ isEdit ? 'Quay lại chi tiết' : 'Quay lại danh sách' }}
      </RouterLink>
      <h1 class="mt-1 text-lg font-semibold text-slate-900">{{ pageTitle }}</h1>
    </header>

    <section class="app-card space-y-4">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Tên khách hàng</label>
        <input v-model="form.name" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Số điện thoại</label>
        <input v-model="form.phone" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Địa chỉ</label>
        <input v-model="form.address" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
      </div>
      <button type="button" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="loading || createLoading || updateLoading" @click="submit">
        {{ isEdit ? 'Lưu thay đổi' : 'Lưu' }}
      </button>
    </section>
  </section>
</template>