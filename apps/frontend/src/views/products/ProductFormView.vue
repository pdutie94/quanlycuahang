<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'

import { MoreVertical, ChevronLeft, Trash2 } from 'lucide-vue-next'
const showMenu = ref(false)
const menuBtnRef = ref<HTMLElement | null>(null)
const menuDropdownRef = ref<HTMLElement | null>(null)


function handleMenuClick() {
  showMenu.value = !showMenu.value
}

function handleClickOutside(event: MouseEvent) {
  const target = event.target as Node
  if (
    showMenu.value &&
    !menuBtnRef.value?.contains(target) &&
    !menuDropdownRef.value?.contains(target)
  ) {
    showMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
  // ...existing code...
})
onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  // ...existing code...
})
async function handleDeleteProduct() {
  showMenu.value = false
  if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) return
  try {
    await products.remove(id.value)
    alert('Đã xóa sản phẩm thành công!')
    await router.push('/products')
  } catch (e) {
    alert('Không thể xóa sản phẩm.')
  }
}
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
const stickyTeleportTarget = ref<string | null>(null)

const isScrolled = ref(false)
let stickyHostEl: HTMLElement | null = null
function updateScrolledState(): void {
  if (stickyHostEl) {
    isScrolled.value = stickyHostEl.getBoundingClientRect().top <= 0
    return
  }

  isScrolled.value = window.scrollY > 0
}


const form = reactive({
  name: '',
  code: '',
  unit_id: null as number | null,
  category_id: null as number | null,
  price_sell: null as number | null,
  price_cost: null as number | null,
  allow_fraction: false,
  min_step: 1,
  qty: null as number | null,
  min_stock_qty: null as number | null,
})

onMounted(async () => {
  // Xác định target teleport tiêu đề sticky
  stickyTeleportTarget.value = document.getElementById('app-sticky-host') ? '#app-sticky-host' : null
  stickyHostEl = document.getElementById('app-sticky-host')
  updateScrolledState()
  window.addEventListener('scroll', updateScrolledState, { passive: true })
  window.addEventListener('resize', updateScrolledState, { passive: true })

  if (!isEdit.value) return
  loading.value = true
  formError.value = ''
  try {
    const data = await productService.getById(id.value)
    form.name = data.name
    form.code = data.code
    form.unit_id = data.base_unit_id
    form.category_id = data.category_id
    form.price_sell = data.price_sell || null
    form.price_cost = data.price_cost || null
    form.allow_fraction = !!data.allow_fraction
    form.min_step = data.min_step || 1
    form.qty = data.inventory_qty_base || null
    form.min_stock_qty = data.min_stock_qty || null
  } catch {
    formError.value = 'Không tải được dữ liệu sản phẩm.'
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  stickyTeleportTarget.value = null
  stickyHostEl = null
  window.removeEventListener('scroll', updateScrolledState)
  window.removeEventListener('resize', updateScrolledState)
})

async function handleSubmit(): Promise<void> {
  formError.value = ''
  try {
    if (!form.unit_id) {
      formError.value = 'Vui lòng chọn đơn vị tính.'
      return
    }
    const payload = {
      name: form.name.trim(),
      code: form.code.trim() || undefined,
      base_unit_id: Number(form.unit_id),
      category_id: form.category_id,
      price_sell: form.price_sell,
      price_cost: form.price_cost,
      allow_fraction: form.allow_fraction,
      min_step: form.allow_fraction ? form.min_step : 1,
      qty: form.qty,
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

    <Teleport v-if="stickyTeleportTarget" :to="stickyTeleportTarget">
     <div
      class="py-2 transition-colors duration-200 text-gray-700"
      :class="isScrolled ? 'border-b border-slate-200 bg-white/95 backdrop-blur-sm' : 'border-b border-transparent bg-transparent'"
    >
      <div class="app-content-wrap px-4">
        <div class="flex items-center gap-2">
          <button @click="router.back()" class="flex items-center justify-center">
            <ChevronLeft :size="22" />
          </button>
          <h2 class="flex-1 truncate text-xl font-semibold text-slate-900 text-left">{{ isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm' }}</h2>
          <div class="relative">
            <button ref="menuBtnRef" class="app-dropdown-menu flex items-center justify-center" @click="handleMenuClick">
              <MoreVertical :size="22" />
            </button>
            <div
              v-if="showMenu"
              ref="menuDropdownRef"
              class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white py-1 shadow-xl z-50 overflow-hidden"
            >
              <button @click="handleDeleteProduct" class="flex w-full items-center gap-2 px-3 py-1 text-left text-sm text-rose-600 hover:bg-rose-50">
                <Trash2 :size="16" />
                Xóa sản phẩm
              </button>
            </div>
          </div>
        </div>
      </div>
      </div>
    </Teleport>

    <form class="space-y-4 rounded-2xl border border-black/10 bg-white p-4 shadow-sm" @submit.prevent="handleSubmit">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Tên sản phẩm</label>
        <input v-model="form.name" type="text" class="w-full rounded-xl border border-gray-300 p-2" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Mã sản phẩm</label>
        <input v-model="form.code" type="text" class="w-full rounded-xl border border-gray-300 p-2" placeholder="Để trống để tự sinh" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Đơn vị tính</label>
        <select v-model="form.unit_id" class="w-full rounded-xl border border-gray-300 p-2 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white" required>
          <option value="">Chọn đơn vị</option>
          <option value="1">Cái</option>
          <option value="2">Kg</option>
          <option value="3">Lít</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Danh mục</label>
        <select v-model="form.category_id" class="w-full rounded-xl border border-gray-300 p-2 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white">
          <option value="">Chưa phân loại</option>
          <option value="1">Đồ uống</option>
          <option value="2">Thực phẩm</option>
        </select>
      </div>
      <hr class="my-4 border-gray-200" />
      <div>
        <div class="mb-2 font-semibold text-gray-800">Giá sản phẩm</div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Giá bán</label>
        <input v-model.number="form.price_sell" type="number" min="0" class="w-full rounded-xl border border-gray-300 p-2" required />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Giá nhập</label>
        <input v-model.number="form.price_cost" type="number" min="0" class="w-full rounded-xl border border-gray-300 p-2" required />
      </div>
      <div class="flex items-center gap-2">
        <input id="allow_fraction" v-model="form.allow_fraction" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
        <label for="allow_fraction" class="text-sm font-medium text-gray-700">Cho phép bán lẻ (số lượng thập phân)</label>
      </div>
      <div v-if="form.allow_fraction">
        <label class="mb-1 block text-sm font-medium text-gray-700">Bước lẻ nhỏ nhất</label>
        <input v-model.number="form.min_step" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-gray-300 p-2" />
      </div>
      <hr class="my-4 border-gray-200" />
      <div>
        <div class="mb-2 font-semibold text-gray-800">Tồn kho</div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Số lượng tồn kho</label>
        <input v-model.number="form.qty" type="number" min="0" class="w-full rounded-xl border border-gray-300 p-2" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Ngưỡng tồn kho thấp</label>
        <input v-model.number="form.min_stock_qty" type="number" min="0" class="w-full rounded-xl border border-gray-300 p-2" />
      </div>
      <div class="flex items-center gap-2">
        <button type="submit" class="rounded-xl bg-pine px-4 py-2 font-medium text-white" :disabled="products.saving || loading">
          {{ products.saving ? 'Đang lưu...' : 'Lưu' }}
        </button>
        <RouterLink to="/products" class="rounded-xl border border-black/15 px-4 py-2 text-sm">Hủy</RouterLink>
      </div>
    <!-- end form -->
    </form>
  </section>
</template>
