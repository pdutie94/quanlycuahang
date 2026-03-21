<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useCategoriesStore } from '../../stores/categories'

const categories = useCategoriesStore()
const name = ref('')
const editingId = ref<number | null>(null)

onMounted(async () => {
  await categories.fetchList()
})

function startEdit(id: number, currentName: string): void {
  editingId.value = id
  name.value = currentName
}

function resetForm(): void {
  editingId.value = null
  name.value = ''
}

async function submit(): Promise<void> {
  if (!name.value.trim()) return

  if (editingId.value) {
    await categories.update(editingId.value, name.value.trim())
  } else {
    await categories.create(name.value.trim())
  }

  resetForm()
}

async function remove(id: number): Promise<void> {
  if (!window.confirm('Bạn chắc chắn muốn xóa danh mục này?')) return
  await categories.remove(id)
}
</script>

<template>
  <section class="space-y-4">
    <header>
      <h2 class="text-xl font-semibold">Danh mục</h2>
      <p class="text-sm text-ink/60">Quản lý danh mục sản phẩm.</p>
    </header>

    <form class="flex flex-col gap-2 rounded-2xl border border-black/10 bg-white p-4 sm:flex-row" @submit.prevent="submit">
      <input
        v-model="name"
        type="text"
        class="w-full rounded-xl border border-gray-300 px-3 py-2"
        placeholder="Nhập tên danh mục"
      />
      <button type="submit" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="categories.saving">
        {{ editingId ? 'Cập nhật' : 'Thêm mới' }}
      </button>
      <button v-if="editingId" type="button" class="rounded-xl border border-black/15 px-4 py-2 text-sm" @click="resetForm">Hủy</button>
    </form>

    <p v-if="categories.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ categories.error }}</p>

    <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
          <tr>
            <th class="px-3 py-2">ID</th>
            <th class="px-3 py-2">Tên danh mục</th>
            <th class="px-3 py-2">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="categories.loading" v-for="n in 4" :key="n" class="border-t border-black/5">
            <td class="px-3 py-2" colspan="3"><div class="h-6 animate-pulse rounded bg-black/10" /></td>
          </tr>
          <tr v-else-if="categories.items.length === 0" class="border-t border-black/5">
            <td class="px-3 py-4 text-center text-ink/60" colspan="3">Chưa có danh mục nào.</td>
          </tr>
          <tr v-else v-for="item in categories.items" :key="item.id" class="border-t border-black/5">
            <td class="px-3 py-2">{{ item.id }}</td>
            <td class="px-3 py-2 font-medium">{{ item.name }}</td>
            <td class="px-3 py-2">
              <div class="flex gap-2">
                <button type="button" class="rounded-lg border border-black/15 px-2 py-1 text-xs" @click="startEdit(item.id, item.name)">Sửa</button>
                <button type="button" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="remove(item.id)">Xóa</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
