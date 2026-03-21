<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductsStore } from '../../stores/products'
import { productService } from '../../services/productService'

const route = useRoute()
const router = useRouter()
const products = useProductsStore()

const id = computed(() => Number(route.params.id || 0))
const isEdit = computed(() => id.value > 0)
const loading = ref(false)
const formError = ref('')

const form = reactive({
  name: '',
  code: '',
  base_unit_id: 1,
  category_id: null as number | null,
  min_stock_qty: null as number | null,
})

onMounted(async () => {
  if (!isEdit.value) return
  loading.value = true
  formError.value = ''
  try {
    const data = await productService.getById(id.value)
    form.name = data.name
    form.code = data.code
    form.base_unit_id = data.base_unit_id
    form.category_id = data.category_id
    form.min_stock_qty = data.min_stock_qty
  } catch {
    formError.value = 'Không tải được dữ liệu sản phẩm.'
  } finally {
    loading.value = false
  }
})

async function handleSubmit(): Promise<void> {
  formError.value = ''
  try {
    const payload = {
      name: form.name.trim(),
      code: form.code.trim() || undefined,
      base_unit_id: Number(form.base_unit_id),
      category_id: form.category_id,
      min_stock_qty: form.min_stock_qty,
    }

    if (isEdit.value) {
      await products.update(id.value, payload)
    } else {
      await products.create(payload)
    }

    await router.push('/products')
  } catch {
    formError.value = products.error || 'Không thể lưu sản phẩm.'
  }
}
</script>

<template>
  <section class="space-y-4">
    <header>
      <h2 class="text-xl font-semibold">{{ isEdit ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm' }}</h2>
      <p class="text-sm text-ink/60">Điền thông tin cơ bản cho sản phẩm.</p>
    </header>

    <form class="space-y-4 rounded-2xl border border-black/10 bg-white p-4 shadow-sm" @submit.prevent="handleSubmit">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Tên sản phẩm</label>
        <input v-model="form.name" type="text" class="w-full rounded-xl border border-gray-300 p-3" required />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Mã sản phẩm</label>
        <input v-model="form.code" type="text" class="w-full rounded-xl border border-gray-300 p-3" placeholder="Để trống để tự sinh" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">ID đơn vị cơ bản</label>
        <input v-model.number="form.base_unit_id" type="number" min="1" class="w-full rounded-xl border border-gray-300 p-3" required />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">ID danh mục (tùy chọn)</label>
        <input v-model.number="form.category_id" type="number" min="1" class="w-full rounded-xl border border-gray-300 p-3" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Tồn tối thiểu</label>
        <input v-model.number="form.min_stock_qty" type="number" step="0.0001" min="0" class="w-full rounded-xl border border-gray-300 p-3" />
      </div>

      <p v-if="formError" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ formError }}</p>

      <div class="flex items-center gap-2">
        <button type="submit" class="rounded-xl bg-pine px-4 py-2 font-medium text-white" :disabled="products.saving || loading">
          {{ products.saving ? 'Đang lưu...' : 'Lưu' }}
        </button>
        <RouterLink to="/products" class="rounded-xl border border-black/15 px-4 py-2 text-sm">Hủy</RouterLink>
      </div>
    </form>
  </section>
</template>
