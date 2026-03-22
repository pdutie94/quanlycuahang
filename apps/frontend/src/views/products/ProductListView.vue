<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useProductsStore } from '../../stores/products'
import PullToRefresh from '../../components/PullToRefresh.vue'
import SkeletonBlock from '../../components/SkeletonBlock.vue'

const products = useProductsStore()
const keyword = ref('')

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
  if (!window.confirm('Bạn chắc chắn muốn xóa sản phẩm này?')) return
  await products.remove(id)
}

async function handleRefresh(): Promise<void> {
  await products.fetchList({ page: 1, q: keyword.value.trim() || undefined })
}
</script>

<template>
  <PullToRefresh @refresh="handleRefresh">
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

    <div class="space-y-2">
      <div v-if="products.loading" v-for="n in 6" :key="`s-${n}`" class="rounded-2xl border border-black/10 bg-white p-4">
        <SkeletonBlock height-class="h-6" rounded-class="rounded-lg" />
      </div>

      <div v-else-if="products.items.length === 0" class="rounded-2xl border border-black/10 bg-white px-3 py-4 text-center text-sm text-ink/60">
        Không có dữ liệu sản phẩm.
      </div>

      <article v-else v-for="item in products.items" :key="item.id" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="font-semibold">{{ item.name }}</h3>
            <p class="text-xs text-ink/60">{{ item.code }} · {{ item.base_unit_name }}</p>
          </div>
          <RouterLink :to="`/products/${item.id}/edit`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Sửa</RouterLink>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
          <p class="rounded-xl bg-black/5 px-3 py-2">Tồn kho: <strong>{{ item.inventory_qty_base }}</strong></p>
          <p class="rounded-xl bg-black/5 px-3 py-2">Tối thiểu: <strong>{{ item.min_stock_qty ?? 0 }}</strong></p>
        </div>
        <button type="button" class="mt-3 rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="handleDelete(item.id)">
          Xóa
        </button>
      </article>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ products.page }} / {{ products.totalPages }} · Tổng {{ products.total }} sản phẩm</p>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50"
          :disabled="products.page <= 1 || products.loading"
          @click="goToPage(products.page - 1)"
        >
          Trước
        </button>
        <button
          type="button"
          class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50"
          :disabled="products.page >= products.totalPages || products.loading"
          @click="goToPage(products.page + 1)"
        >
          Sau
        </button>
      </div>
    </div>
  </section>
  </PullToRefresh>
</template>
