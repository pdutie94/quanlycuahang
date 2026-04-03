<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { useUnitList } from '../composables/useUnitList';

const toast = useToast();
const { items, load, loading, error, submitCreate, createLoading, createError, submitUpdate, updateLoading, updateError, submitDelete, deleteLoading, deleteError } = useUnitList();

const createName = ref('');
const editMap = ref({});
const pendingDeleteId = ref(null);
let pendingDeleteTimer = null;

const loadPage = async () => {
  try {
    await load();
    const nextMap = {};
    for (const unit of items.value) {
      nextMap[unit.id] = unit.name || '';
    }
    editMap.value = nextMap;
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách đơn vị tính.');
  }
};

const handleCreate = async () => {
  try {
    const result = await submitCreate({ name: createName.value });
    if (result?.success) {
      toast.success(result?.message || 'Đã thêm đơn vị tính.');
      createName.value = '';
      await loadPage();
      return;
    }
    toast.error(result?.message || createError.value || 'Không thể thêm đơn vị tính.');
  } catch (_err) {
    toast.error(createError.value || 'Không thể thêm đơn vị tính.');
  }
};

const handleUpdate = async (id) => {
  try {
    const result = await submitUpdate(id, { name: editMap.value[id] || '' });
    if (result?.success) {
      toast.success(result?.message || 'Đã cập nhật đơn vị tính.');
      await loadPage();
      return;
    }
    toast.error(result?.message || updateError.value || 'Không thể cập nhật đơn vị tính.');
  } catch (_err) {
    toast.error(updateError.value || 'Không thể cập nhật đơn vị tính.');
  }
};

const resetPendingDelete = () => {
  pendingDeleteId.value = null;
  if (pendingDeleteTimer) {
    window.clearTimeout(pendingDeleteTimer);
    pendingDeleteTimer = null;
  }
};

const handleDelete = async (id) => {
  if (pendingDeleteId.value !== id) {
    pendingDeleteId.value = id;
    if (pendingDeleteTimer) {
      window.clearTimeout(pendingDeleteTimer);
    }
    pendingDeleteTimer = window.setTimeout(() => {
      pendingDeleteId.value = null;
      pendingDeleteTimer = null;
    }, 4000);
    toast.info('Nhấn Xóa lần nữa để xác nhận.');
    return;
  }

  resetPendingDelete();

  try {
    const result = await submitDelete(id);
    if (result?.success) {
      toast.success(result?.message || 'Đã xóa đơn vị tính.');
      await loadPage();
      return;
    }
    toast.error(result?.message || deleteError.value || 'Không thể xóa đơn vị tính.');
  } catch (_err) {
    toast.error(deleteError.value || 'Không thể xóa đơn vị tính.');
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
  <section class="space-y-3">
    <header class="app-card">
      <h1 class="text-lg font-semibold text-slate-900">Đơn vị tính</h1>

      <form class="mt-3 flex gap-2" @submit.prevent="handleCreate">
        <input
          v-model="createName"
          type="text"
          placeholder="VD: Cái, Hộp, Kg..."
          class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
          :disabled="loading || createLoading || updateLoading || deleteLoading"
          required
        />
        <button type="submit" class="h-10 rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white" :disabled="loading || createLoading || updateLoading || deleteLoading">Thêm</button>
      </form>
    </header>

    <div class="space-y-3">
      <div v-if="loading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có đơn vị tính nào.</div>

      <div v-for="item in items" v-else :key="item.id" class="app-list-card">
        <div class="flex flex-wrap items-center gap-2">
          <input
            v-model="editMap[item.id]"
            type="text"
            class="h-10 min-w-0 flex-1 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
            :disabled="loading || createLoading || updateLoading || deleteLoading"
          />
          <button type="button" class="h-10 rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700" :disabled="loading || createLoading || updateLoading || deleteLoading" @click="handleUpdate(item.id)">Lưu</button>
          <button type="button" class="h-10 rounded-xl border border-rose-300 px-4 text-sm font-medium text-rose-600" :disabled="loading || createLoading || updateLoading || deleteLoading" @click="handleDelete(item.id)">Xóa</button>
        </div>
      </div>
    </div>
  </section>
</template>
