<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { Pencil, Trash2, Check, X as IconX, Plus } from '@lucide/vue';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import { useMaterialPriceList } from '../composables/useMaterialPriceList';

const toast = useToast();
const { items, meta, page, load, loadMore, loading, submitCreate, createLoading, submitUpdate, updateLoading, submitDelete, deleteLoading } = useMaterialPriceList();

interface MaterialPrice {
  id: number | string;
  material_name: string;
}

const newMaterialName = ref('');
const newUnitPrice = ref('');
const addLoading = ref(false);
const editingId = ref<number | string | null>(null);
const editingMaterialName = ref('');
const editingUnitPrice = ref('');
const showDeleteModal = ref(false);
const deletingItem = ref<MaterialPrice | null>(null);
const itemsTyped = computed(() => items.value as MaterialPrice[]);

const formatPrice = (price: number | string) => {
  const num = typeof price === 'string' ? parseFloat(price) : price;
  return new Intl.NumberFormat('vi-VN').format(num);
};

const handleAdd = async () => {
  if (!newMaterialName.value.trim()) {
    toast.error('Vui lòng nhập tên nguyên vật liệu.');
    return;
  }
  if (!newUnitPrice.value.toString().trim()) {
    toast.error('Vui lòng nhập giá.');
    return;
  }
  addLoading.value = true;
  try {
    // Clean price value by removing dots and converting to number
    const cleanPrice = newUnitPrice.value.toString().replace(/\./g, '');
    const res = await submitCreate({
      material_name: newMaterialName.value,
      unit_price: parseFloat(cleanPrice) || 0,
    });
    if (res?.success) {
      const { id, material_name, unit_price } = res.data || {};
      toast.success('Đã thêm mới giá nguyên vật liệu.');
      items.value.unshift({ id, material_name, unit_price });
      newMaterialName.value = '';
      newUnitPrice.value = '';
    } else {
      toast.error(res?.message || 'Đã có lỗi xảy ra!');
    }
  } finally {
    addLoading.value = false;
  }
};

const startEdit = (item: MaterialPrice) => {
  editingId.value = item.id;
  editingMaterialName.value = item.material_name;
  editingUnitPrice.value = item.unit_price.toString();
};
const cancelEdit = () => {
  editingId.value = null;
  editingMaterialName.value = '';
  editingUnitPrice.value = '';
};
const handleEdit = async (item: MaterialPrice) => {
  if (!editingMaterialName.value.trim()) {
    toast.error('Vui lòng nhập tên nguyên vật liệu.');
    return;
  }
  if (!editingUnitPrice.value.toString().trim()) {
    toast.error('Vui lòng nhập giá.');
    return;
  }
  updateLoading.value = true;
  try {
    // Clean price value by removing dots and converting to number
    const cleanPrice = editingUnitPrice.value.toString().replace(/\./g, '');
    const res = await submitUpdate(item.id, {
      material_name: editingMaterialName.value,
      unit_price: parseFloat(cleanPrice) || 0,
    });
    if (res?.success) {
      toast.success('Đã cập nhật giá nguyên vật liệu.');
      const idx = items.value.findIndex(i => i.id === item.id);
      if (idx !== -1) {
        items.value[idx] = {
          ...items.value[idx],
          material_name: editingMaterialName.value,
          unit_price: parseFloat(cleanPrice) || 0,
        };
      }
      editingId.value = null;
      editingMaterialName.value = '';
      editingUnitPrice.value = '';
    } else {
      toast.error(res?.message || 'Đã có lỗi xảy ra.');
    }
  } finally {
    updateLoading.value = false;
  }
};

const handleDelete = (item: MaterialPrice) => {
  deletingItem.value = item;
  showDeleteModal.value = true;
};
const confirmDelete = async () => {
  if (!deletingItem.value) return;
  deleteLoading.value = true;
  try {
    const res = await submitDelete(deletingItem.value.id);
    if (res?.success) {
      toast.success('Đã xóa thành công.');
      items.value = items.value.filter(i => i.id !== deletingItem.value?.id);
      showDeleteModal.value = false;
      deletingItem.value = null;
    } else {
      toast.error(res?.message || 'Đã có lỗi xảy ra.');
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
      <h1 class="font-display text-xl font-bold text-slate-900 md:text-2xl">Giá nguyên vật liệu</h1>
    </div>
    <form class="mt-0 flex gap-2 mb-2" @submit.prevent="handleAdd">
      <input v-model="newMaterialName" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500" placeholder="VD: Sắt, Inox, Nhôm..." :disabled="addLoading" />
      <div class="relative">
        <input v-model="newUnitPrice" type="text" v-money-input class="w-[8rem] rounded-lg border border-slate-300 px-3 py-2 pr-8 text-sm outline-none focus:border-brand-500" placeholder="Giá" :disabled="addLoading" />
        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">đ</span>
      </div>
      <button type="submit" class="rounded-lg bg-brand-600 px-3 py-2 text-white disabled:opacity-50" :disabled="addLoading" title="Thêm mới"><Plus class="w-4 h-4" /></button>
    </form>
    <div class="space-y-3" @scroll="onScroll">
      <div v-if="loading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có giá nguyên vật liệu nào.</div>
      <div v-else class="space-y-3" @scroll="onScroll">
        <div v-for="item in itemsTyped" :key="item.id" class="app-list-card flex items-center justify-between gap-2" :class="{ 'bg-brand-50 p-2': editingId === item.id }">
          <div class="flex-1">
            <template v-if="editingId === item.id">
              <div class="flex gap-2">
                <input v-model="editingMaterialName" type="text" class="w-full rounded-lg border border-slate-300 px-2 py-1 text-sm outline-none focus:border-brand-500" :disabled="updateLoading" />
                <div class="relative">
                  <input v-model="editingUnitPrice" type="text" v-money-input class="w-24 rounded-lg border border-slate-300 px-2 py-1 pr-7 text-sm outline-none focus:border-brand-500" :disabled="updateLoading" />
                  <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">đ</span>
                </div>
              </div>
            </template>
            <template v-else>
              <span class="text-sm font-medium text-slate-900">{{ item.material_name }} - {{ formatPrice(item.unit_price) }} đ/kg</span>
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
          title="Xóa giá nguyên vật liệu"
          :description="deletingItem && deletingItem.material_name ? `Bạn có chắc chắn muốn xóa giá nguyên vật liệu '${deletingItem.material_name}'? Thao tác này không thể hoàn tác.` : ''"
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
