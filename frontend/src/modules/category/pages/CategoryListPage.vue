<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { useCategoryList } from '../composables/useCategoryList';
import { createCategory, updateCategory, deleteCategory, fetchCategoryDetail } from '../services/category.api';
import { Pencil, Trash2, Check, X as IconX, Plus } from '@lucide/vue';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';

interface Category {
  id: number | string;
  name: string;
  created_at?: string;
}

const { items, meta, loading, error, load } = useCategoryList();
const selectedIds = ref<number[]>([]);
const toast = useToast();
const hasLoadedOnce = ref(false);

const isInitialLoading = computed(() => loading.value && !hasLoadedOnce.value);
const isRefreshing = computed(() => loading.value && hasLoadedOnce.value);
const itemsTyped = computed(() => items.value as Category[]);

// Infinite scroll state
const page = ref(1);
const hasMore = computed(() => meta.value?.page < meta.value?.total_pages);
const loadingMore = ref(false);

const loadMore = async () => {
  if (loadingMore.value || !hasMore.value) return;
  loadingMore.value = true;
  try {
    const nextPage = page.value + 1;
    const payload = await load(nextPage);
    if (payload?.data?.items?.length) {
      items.value.push(...payload.data.items);
      page.value = nextPage;
      meta.value = payload.data.meta || meta.value;
    }
  } finally {
    loadingMore.value = false;
  }
};

const onScroll = (e: Event) => {
  const el = e.target as HTMLElement;
  if (el.scrollTop + el.clientHeight >= el.scrollHeight - 40) {
    loadMore();
  }
};

// Thêm mới dạng popup form
const showAddForm = ref(false);
const newName = ref('');
const addLoading = ref(false);
const handleAdd = async () => {
  if (!newName.value.trim()) {
    toast.error('Vui lòng nhập tên danh mục.');
    return;
  }
  addLoading.value = true;
  try {
    const res = await createCategory({ name: newName.value });
    const { id, name, created_at } = res?.data || {};
    if (!res?.success || !id || !name) {
      toast.error(res?.message || 'Không thể thêm danh mục.');
      return;
    }
    items.value.unshift({ id, name, created_at });
    toast.success('Đã thêm danh mục.');
    newName.value = '';
  } finally {
    addLoading.value = false;
  }
};

// Sửa inline
const editingId = ref<number | string | null>(null);
const editingName = ref('');
const editLoading = ref(false);
const startEdit = (item: Category) => {
  editingId.value = item.id;
  editingName.value = item.name;
};
const cancelEdit = () => {
  editingId.value = null;
  editingName.value = '';
};
const handleEdit = async (item: Category) => {
  if (!editingName.value.trim()) {
    toast.error('Vui lòng nhập tên danh mục.');
    return;
  }
  editLoading.value = true;
  try {
    const res = await updateCategory(item.id, { name: editingName.value });
    if (res?.success) {
      toast.success(res?.message || 'Đã cập nhật danh mục.');
      // Cập nhật lại tên trong danh sách, không reload
      const idx = items.value.findIndex(i => i.id === item.id);
      if (idx !== -1) items.value[idx].name = editingName.value;
      editingId.value = null;
      editingName.value = '';
    } else {
      toast.error(res?.message || 'Không thể cập nhật danh mục.');
    }
  } finally {
    editLoading.value = false;
  }
};

// Xóa
const showDeleteModal = ref(false);
const deletingItem = ref<Category | null>(null);
const deleteLoading = ref(false);

const handleDelete = (item: Category) => {
  if (item.id === 1) {
    toast.error('Không thể xóa danh mục mặc định.');
    return;
  }
  deletingItem.value = item;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  if (!deletingItem.value) return;
  deleteLoading.value = true;
  try {
    const res = await deleteCategory(deletingItem.value.id);
    if (res?.success) {
      toast.success(res?.message || 'Đã xóa danh mục.');
      items.value = items.value.filter(i => i.id !== deletingItem.value?.id);
      showDeleteModal.value = false;
      deletingItem.value = null;
    } else {
      toast.error(res?.message || 'Không thể xóa danh mục.');
    }
  } finally {
    deleteLoading.value = false;
  }
};
// Hủy form thêm mới
const cancelAddForm = () => {
  showAddForm.value = false;
  newName.value = '';
};

onMounted(async () => {
  await load(page.value);
});
</script>

<template>


  <section class="space-y-3">
    <div class="mb-2">
      <h1 class="font-display text-xl font-bold text-slate-900 md:text-2xl">Danh mục sản phẩm</h1>
    </div>
    <form class="mt-0 flex gap-2 mb-2" @submit.prevent="handleAdd">
      <input v-model="newName" type="text" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500" placeholder="Tên danh mục mới" :disabled="addLoading" />
      <button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-white disabled:opacity-50" :disabled="addLoading" title="Thêm mới"><Check class="w-4 h-4" /></button>
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-slate-700" @click="cancelAddForm" title="Hủy"><IconX class="w-4 h-4" /></button>
    </form>
    <div class="space-y-3">
      <div v-if="isRefreshing" class="px-1 text-xs font-medium text-slate-500">Đang cập nhật danh mục...</div>
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có danh mục nào.</div>
      <div v-else class="space-y-3" @scroll="onScroll" :class="isRefreshing ? 'opacity-70 transition-opacity' : 'transition-opacity'">
        <div v-for="item in itemsTyped" :key="item.id" class="app-list-card flex items-center justify-between gap-2">
          <div class="flex-1">
            <template v-if="editingId === item.id">
              <input v-model="editingName" type="text" class="rounded-lg border border-slate-300 px-2 py-1 text-sm outline-none focus:border-brand-500 w-48" :disabled="editLoading" />
            </template>
            <template v-else>
              <span class="text-sm font-medium text-slate-900">{{ item.name }}</span>
            </template>
          </div>
          <div class="flex gap-2">
            <template v-if="editingId === item.id">
              <button class="p-1.5 rounded hover:bg-brand-50 text-brand-700 disabled:opacity-50" :disabled="editLoading" @click="handleEdit(item)" title="Lưu"><Check class="w-4 h-4" /></button>
              <button class="p-1.5 rounded hover:bg-slate-100 text-slate-700" @click="cancelEdit" title="Hủy"><IconX class="w-4 h-4" /></button>
            </template>
            <template v-else>
              <button class="p-1.5 rounded hover:bg-slate-100" title="Sửa" @click="startEdit(item)"><Pencil class="w-4 h-4" /></button>
              <button class="p-1.5 rounded hover:bg-rose-50 disabled:opacity-50" title="Xóa" :disabled="item.id === 1 || deleteLoading" @click="handleDelete(item)">
                <Trash2 class="w-4 h-4" />
              </button>
              <ActionConfirmSheet
                :open="showDeleteModal"
                title="Xóa danh mục"
                :description="deletingItem && deletingItem.name ? `Bạn có chắc chắn muốn xóa danh mục '${deletingItem.name}'? Thao tác này không thể hoàn tác.` : ''"
                confirm-label="Xóa"
                cancel-label="Hủy"
                :loading="deleteLoading"
                @cancel="showDeleteModal = false"
                @confirm="confirmDelete"
              />
            </template>
          </div>
        </div>
        <InfiniteListStatus :visible="true" :loadingMore="loadingMore" :hasMore="hasMore" />
      </div>
    </div>
  </section>
</template>
