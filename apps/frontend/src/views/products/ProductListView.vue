<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useProductsStore } from '../../stores/products'
import { usePullToRefresh } from '../../lib/pullToRefresh'
import ConfirmSheet from '../../components/ConfirmSheet.vue'

const products = useProductsStore()
const keyword = ref('')
const pendingDeleteId = ref<number | null>(null)

usePullToRefresh(() => products.fetchList({ page: products.page || 1, q: products.query }), 'Kéo để làm mới sản phẩm')

onMounted(async () => {
  await products.fetchList({ page: 1 })
  keyword.value = products.query
})

async function handleSearch(): Promise<void> {
  await products.fetchList({ page: 1, q: keyword.value.trim() })
}

async function goToPage(nextPage: number): Promise<void> {
  await products.fetchList({ page: nextPage })
}

async function handleDelete(id: number): Promise<void> {
  pendingDeleteId.value = id
}

async function confirmDelete(): Promise<void> {
  if (pendingDeleteId.value === null) return
  await products.remove(pendingDeleteId.value)
  pendingDeleteId.value = null
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">Sản phẩm</h2>
        <p class="text-sm text-ink/60">Quản lý danh sách sản phẩm đang bán.</p>
      </div>
      <RouterLink to="/products/new" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white">
        Thêm sản phẩm
      </RouterLink>
    </header>

    <form class="flex gap-2" @submit.prevent="handleSearch">
      <input
        v-model="keyword"
        type="text"
        class="w-full rounded-xl border border-gray-300 px-3 py-2"
        placeholder="Tìm theo tên hoặc mã sản phẩm"
      />
      <button type="submit" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Tìm</button>
    </form>

    <p v-if="products.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ products.error }}</p>

    <div class="grid gap-3 md:hidden">
      <article v-if="products.loading" v-for="n in 6" :key="`mobile-${n}`" class="rounded-2xl border border-black/10 bg-white p-4 shadow-sm">
        <div class="h-5 w-2/3 animate-pulse rounded bg-black/10" />
        <div class="mt-3 h-4 w-1/2 animate-pulse rounded bg-black/10" />
        <div class="mt-2 h-4 w-1/3 animate-pulse rounded bg-black/10" />
        <div class="mt-4 h-10 animate-pulse rounded-2xl bg-black/10" />
      </article>

      <article v-else-if="products.items.length === 0" class="rounded-2xl border border-dashed border-black/10 bg-white px-4 py-8 text-center text-sm text-ink/60">
        Không có dữ liệu sản phẩm.
      </article>

      <article v-else v-for="item in products.items" :key="item.id" class="rounded-2xl border border-black/10 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold">{{ item.name }}</h3>
            <p class="mt-1 text-xs uppercase tracking-[0.18em] text-ink/45">{{ item.code }}</p>
          </div>
          <span class="rounded-full bg-pine/10 px-2.5 py-1 text-xs font-semibold text-pine">{{ item.base_unit_name }}</span>
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-2xl bg-black/5 px-3 py-2">
            <dt class="text-xs uppercase tracking-wide text-ink/50">Tồn kho</dt>
            <dd class="mt-1 font-semibold">{{ item.inventory_qty_base }}</dd>
          </div>
          <div class="rounded-2xl bg-black/5 px-3 py-2">
            <dt class="text-xs uppercase tracking-wide text-ink/50">Tối thiểu</dt>
            <dd class="mt-1 font-semibold">{{ item.min_stock_qty ?? 0 }}</dd>
          </div>
        </dl>

        <div class="mt-4 flex gap-2">
          <RouterLink :to="`/products/${item.id}/edit`" class="flex-1 rounded-xl border border-black/15 px-3 py-2 text-center text-sm font-medium">
            Sửa
          </RouterLink>
          <button type="button" class="flex-1 rounded-xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600" @click="handleDelete(item.id)">
            Xóa
          </button>
        </div>
      </article>
    </div>

    <div class="hidden overflow-hidden rounded-2xl border border-black/10 bg-white md:block">
      <table class="min-w-full text-sm">
        <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
          <tr>
            <th class="px-3 py-2">Tên</th>
            <th class="px-3 py-2">Mã</th>
            <th class="px-3 py-2">Đơn vị</th>
            <th class="px-3 py-2">Tồn kho</th>
            <th class="px-3 py-2">Tồn tối thiểu</th>
            <th class="px-3 py-2">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="products.loading" v-for="n in 6" :key="`s-${n}`" class="border-t border-black/5">
            <td class="px-3 py-2" colspan="6"><div class="h-6 animate-pulse rounded bg-black/10" /></td>
          </tr>

          <tr v-else-if="products.items.length === 0" class="border-t border-black/5">
            <td class="px-3 py-4 text-center text-ink/60" colspan="6">Không có dữ liệu sản phẩm.</td>
          </tr>

          <tr v-else v-for="item in products.items" :key="item.id" class="border-t border-black/5">
            <td class="px-3 py-2 font-medium">{{ item.name }}</td>
            <td class="px-3 py-2">{{ item.code }}</td>
            <td class="px-3 py-2">{{ item.base_unit_name }}</td>
            <td class="px-3 py-2">{{ item.inventory_qty_base }}</td>
            <td class="px-3 py-2">{{ item.min_stock_qty ?? 0 }}</td>
            <td class="px-3 py-2">
              <div class="flex items-center gap-2">
                <RouterLink :to="`/products/${item.id}/edit`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Sửa</RouterLink>
                <button type="button" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="handleDelete(item.id)">
                  Xóa
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex flex-col gap-3 text-sm text-ink/70 sm:flex-row sm:items-center sm:justify-between">
      <p>Trang {{ products.page }} / {{ products.totalPages }} · Tổng {{ products.total }} sản phẩm</p>
      <div class="grid grid-cols-2 gap-2 sm:flex">
        <button
          type="button"
          class="rounded-xl border border-black/15 px-3 py-2 disabled:opacity-50"
          :disabled="products.page <= 1 || products.loading"
          @click="goToPage(products.page - 1)"
        >
          Trước
        </button>
        <button
          type="button"
          class="rounded-xl border border-black/15 px-3 py-2 disabled:opacity-50"
          :disabled="products.page >= products.totalPages || products.loading"
          @click="goToPage(products.page + 1)"
        >
          Sau
        </button>
      </div>
    </div>
  </section>

  <ConfirmSheet
    :model-value="pendingDeleteId !== null"
    title="Xóa sản phẩm"
    message="Sản phẩm này sẽ bị xóa khỏi danh sách hiện tại."
    confirm-text="Xóa ngay"
    @update:modelValue="(value) => { if (!value) pendingDeleteId = null }"
    @confirm="confirmDelete"
  />
</template>
