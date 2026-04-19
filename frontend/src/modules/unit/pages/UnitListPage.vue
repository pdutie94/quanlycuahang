<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { Pencil, Trash2, Check, X as IconX, Plus } from '@lucide/vue';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import { useUnitList } from '../composables/useUnitList';
import UnitListSkeleton from '../components/UnitListSkeleton.vue';

const toast = useToast();
const { items, meta, page, load, loadMore, loading, submitCreate, createLoading, submitUpdate, updateLoading, submitDelete, deleteLoading } = useUnitList();

interface Unit {
  id: number | string;
  name: string;
}

const newName = ref('');
const addLoading = ref(false);
const editingId = ref<number | string | null>(null);
const editingName = ref('');
const showDeleteModal = ref(false);
const deletingItem = ref<Unit | null>(null);
const itemsTyped = computed(() => items.value as Unit[]);

const handleAdd = async () => {
  if (!newName.value.trim()) {
    toast.error('Vui lòng nhập tên đơn vị.');
    return;
  }
  addLoading.value = true;
  try {
    const res = await submitCreate({ name: newName.value });
    const { id, name } = res?.data || {};
    if (res?.success && id && name) {
      items.value.unshift({ id, name });
      toast.success('Đã thêm đơn vị tính.');
      newName.value = '';
    } else {
      toast.error(res?.message || 'Không thể thêm đơn vị tính.');
    }
  } finally {
    addLoading.value = false;
  }
};

const startEdit = (item: Unit) => {
  editingId.value = item.id;
  editingName.value = item.name;
};
const cancelEdit = () => {
  editingId.value = null;
  editingName.value = '';
};
const handleEdit = async (item: Unit) => {
  if (!editingName.value.trim()) {
    toast.error('Vui lòng nhập tên đơn vị.');
    return;
  }
  updateLoading.value = true;
  try {
    const res = await submitUpdate(item.id, { name: editingName.value });
    if (res?.success) {
      toast.success('Đã cập nhật đơn vị tính.');
      const idx = items.value.findIndex(i => i.id === item.id);
      if (idx !== -1) items.value[idx].name = editingName.value;
      editingId.value = null;
      editingName.value = '';
    } else {
      toast.error(res?.message || 'Không thể cập nhật đơn vị tính.');
    }
  } finally {
    updateLoading.value = false;
  }
};

const handleDelete = (item: Unit) => {
  deletingItem.value = item;
  showDeleteModal.value = true;
};
const confirmDelete = async () => {
  if (!deletingItem.value) return;
  deleteLoading.value = true;
  try {
    const res = await submitDelete(deletingItem.value.id);
    if (res?.success) {
      toast.success('Đã xóa đơn vị tính.');
      items.value = items.value.filter(i => i.id !== deletingItem.value?.id);
      showDeleteModal.value = false;
      deletingItem.value = null;
    } else {
      toast.error(res?.message || 'Không thể xóa đơn vị tính.');
    }
  } finally {
    deleteLoading.value = false;
  }
};

const onScroll = (e: Event) => {
  const el = e.target as HTMLElement;
  if (el.scrollTop + el.clientHeight >= el.scrollHeight - 40) {
    loadMore();
  }
};

onMounted(async () => {
  await load(1);
});
</script>

<template>
  <section class="space-y-3">
    <div class="mb-2">
      <h1 class="font-display text-xl font-bold text-slate-900 md:text-2xl">Đơn vị tính</h1>
    </div>
    <form class="mt-0 flex gap-2 mb-2" @submit.prevent="handleAdd">
      <input v-model="newName" type="text" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500" placeholder="VD: Cái, Hộp, Kg..." :disabled="addLoading" />
      <button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-white disabled:opacity-50" :disabled="addLoading" title="Thêm mới"><Plus class="w-4 h-4" /></button>
    </form>
    <div class="space-y-3" @scroll="onScroll">
      <UnitListSkeleton v-if="loading" />

      <div v-else-if="!items.length" class="app-empty-state">Chưa có đơn vị tính nào.</div>

      <div v-else class="space-y-3" @scroll="onScroll">
        <div v-for="item in itemsTyped" :key="item.id" class="app-list-card flex items-center justify-between gap-2">
          <div class="flex-1">
            <template v-if="editingId === item.id">
              <input v-model="editingName" type="text" class="rounded-lg border border-slate-300 px-2 py-1 text-sm outline-none focus:border-brand-500 w-48" :disabled="updateLoading" />
            </template>
            <template v-else>
              <span class="text-sm font-medium text-slate-900">{{ item.name }}</span>
            </template>
          </div>
          <div class="flex gap-2">
            <template v-if="editingId === item.id">
              <button class="p-1.5 rounded hover:bg-brand-50 text-brand-700 disabled:opacity-50" :disabled="updateLoading" @click="handleEdit(item)" title="Lưu"><Check class="w-4 h-4" /></button>
              <button class="p-1.5 rounded hover:bg-slate-100 text-slate-700" @click="cancelEdit" title="Hủy"><IconX class="w-4 h-4" /></button>
            </template>
            <template v-else>
              <button class="p-1.5 rounded hover:bg-slate-100" title="Sửa" @click="startEdit(item)"><Pencil class="w-4 h-4" /></button>
              <button class="p-1.5 rounded hover:bg-rose-50 disabled:opacity-50" title="Xóa" :disabled="deleteLoading" @click="handleDelete(item)">
                <Trash2 class="w-4 h-4" />
              </button>
            </template>
          </div>
        </div>
        <InfiniteListStatus :visible="true" :loadingMore="loading" :hasMore="meta.page < meta.total_pages" />
        <ActionConfirmSheet
          :open="showDeleteModal"
          title="Xóa đơn vị tính"
          :description="deletingItem && deletingItem.name ? `Bạn có chắc chắn muốn xóa đơn vị tính '${deletingItem.name}'? Thao tác này không thể hoàn tác.` : ''"
          confirm-label="Xóa"
          cancel-label="Hủy"
          :loading="deleteLoading"
          @cancel="showDeleteModal = false"
          @confirm="confirmDelete"
        />
      </div>
    </div>
  </section>
</template>
