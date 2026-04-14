<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useToast } from '../../../shared/composables/useToast';
import { useCategoryForm } from '../composables/useCategoryForm';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { form, loading, loadEdit, submitCreate, createLoading, createError, submitUpdate, updateLoading, updateError, submitDelete, deleteLoading, deleteError } = useCategoryForm();

const pendingDelete = ref(false);
let pendingDeleteTimer: number | null = null;

const isEdit = computed(() => !!route.params.id);
const isFormDisabled = computed(() => loading.value || createLoading.value || updateLoading.value || deleteLoading.value);

const loadPage = async () => {
  if (!isEdit.value) {
    return;
  }

  try {
    await loadEdit(Number(route.params.id || 0));
  } catch (_err: any) {
    toast.error('Không thể tải danh mục.');
  }
};

const handleSubmit = async () => {
  try {
    const result = isEdit.value ? await submitUpdate(Number(route.params.id || 0)) : await submitCreate();

    if (result?.success) {
      toast.success(result?.message || (isEdit.value ? 'Đã cập nhật danh mục sản phẩm.' : 'Đã thêm danh mục sản phẩm.'));
      router.push('/categories');
    } else {
      toast.error(result?.message || createError.value || updateError.value || 'Không thể lưu danh mục.');
    }
  } catch (_err: any) {
    toast.error(createError.value || updateError.value || 'Không thể lưu danh mục.');
  }
};

const resetPendingDelete = () => {
  pendingDelete.value = false;
  if (pendingDeleteTimer) {
    window.clearTimeout(pendingDeleteTimer);
    pendingDeleteTimer = null;
  }
};

const handleDelete = async () => {
  if (!isEdit.value) {
    return;
  }

  if (!pendingDelete.value) {
    pendingDelete.value = true;
    if (pendingDeleteTimer) {
      window.clearTimeout(pendingDeleteTimer);
    }
    pendingDeleteTimer = window.setTimeout(() => {
      pendingDelete.value = false;
      pendingDeleteTimer = null;
    }, 4000);
    toast.info('Nhấn Xóa lần nữa để xác nhận.');
    return;
  }

  resetPendingDelete();

  try {
    const result = await submitDelete(Number(route.params.id || 0));
    if (result?.success) {
      toast.success(result?.message || 'Đã xóa danh mục sản phẩm.');
      router.push('/categories');
      return;
    }
    toast.error(result?.message || deleteError.value || 'Không thể xóa danh mục.');
  } catch (_err: any) {
    toast.error(deleteError.value || 'Không thể xóa danh mục.');
  }
};

onMounted(async () => {
  await loadPage();
});

onBeforeUnmount(() => {
  resetPendingDelete();
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="isEdit ? 'Sửa danh mục' : 'Thêm danh mục'" back-to="/categories" />

    <form class="space-y-3" @submit.prevent="handleSubmit">
      <div class="app-card">
        <label class="block text-sm font-medium text-slate-700">Tên danh mục<span class="text-rose-500">*</span></label>
        <input
          v-model="form.name"
          type="text"
          placeholder="VD: Nước giải khát, Đồ khô..."
          class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"
          :disabled="isFormDisabled"
          required
        />
      </div>

      <div class="flex gap-2">
        <button type="submit" class="h-10 flex-1 rounded-xl bg-brand-600 text-sm font-medium text-white disabled:opacity-50" :disabled="isFormDisabled">{{ isEdit ? 'Cập nhật' : 'Thêm' }}</button>
        <button v-if="isEdit" type="button" class="h-10 rounded-xl border border-rose-300 px-4 text-sm font-medium text-rose-600 disabled:opacity-50" :disabled="isFormDisabled" @click="handleDelete">Xóa</button>
        <RouterLink to="/categories" class="h-10 flex-1 rounded-xl border border-slate-300 text-center text-sm font-medium text-slate-700">Hủy</RouterLink>
      </div>
    </form>
  </section>
</template>
