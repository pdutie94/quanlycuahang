<script setup>
import { computed, onMounted } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useSupplierForm } from '../composables/useSupplierForm';
import { useToast } from '../../../shared/composables/useToast';

const router = useRouter();
const route = useRoute();
const toast = useToast();
const { form, supplier, loading, loadEdit, submitCreate, createLoading, createError, submitUpdate, updateLoading, updateError } = useSupplierForm();

const isEdit = computed(() => !!route.params.id);
const isFormDisabled = computed(() => loading.value || createLoading.value || updateLoading.value);

const loadPage = async () => {
  if (isEdit.value) {
    try {
      await loadEdit(Number(route.params.id || 0));
    } catch (_err) {
      toast.error('Không thể tải thông tin nhà cung cấp.');
    }
  }
};

const handleSubmit = async () => {
  try {
    const result = isEdit.value ? await submitUpdate(Number(route.params.id || 0)) : await submitCreate();

    if (result?.success) {
      toast.success(result?.message || (isEdit.value ? 'Đã cập nhật nhà cung cấp.' : 'Đã thêm nhà cung cấp.'));
      router.push('/suppliers');
    } else {
      toast.error(result?.message || createError.value || updateError.value || 'Lỗi khi lưu nhà cung cấp.');
    }
  } catch (_err) {
    toast.error(createError.value || updateError.value || 'Lỗi khi lưu nhà cung cấp.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <RouterLink to="/suppliers" class="text-sm font-medium text-slate-500 hover:text-slate-700">Quay lại danh sách</RouterLink>
          <h1 class="mt-1 text-lg font-semibold text-slate-900">{{ isEdit ? 'Chỉnh sửa' : 'Thêm' }} nhà cung cấp</h1>
        </div>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>

    <form v-else class="space-y-3" @submit.prevent="handleSubmit">
      <div class="app-card">
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-slate-700">Tên nhà cung cấp<span class="text-rose-500">*</span></label>
            <input
              v-model="form.name"
              type="text"
              placeholder="VD: Công ty ABC, Công ty XYZ..."
              class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"
              :disabled="isFormDisabled"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Số điện thoại</label>
            <input
              v-model="form.phone"
              type="text"
              placeholder="VD: 0912345678"
              class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"
              :disabled="isFormDisabled"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Địa chỉ</label>
            <input
              v-model="form.address"
              type="text"
              placeholder="VD: 123 Đường X, Hà Nội"
              class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"
              :disabled="isFormDisabled"
            />
          </div>
        </div>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="h-10 flex-1 rounded-xl bg-brand-600 text-sm font-medium text-white disabled:opacity-50" :disabled="isFormDisabled">
          {{ isEdit ? 'Cập nhật' : 'Thêm' }}
        </button>
        <RouterLink to="/suppliers" class="h-10 flex-1 rounded-xl border border-slate-300 text-center text-sm font-medium text-slate-700">Hủy</RouterLink>
      </div>
    </form>
  </section>
</template>
