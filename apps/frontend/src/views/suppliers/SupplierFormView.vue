<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSuppliersStore } from '../../stores/suppliers'

const route = useRoute()
const router = useRouter()
const suppliers = useSuppliersStore()
const loadError = ref('')

const form = reactive({
  name: '',
  phone: '',
  address: '',
})

const supplierId = computed(() => {
  const raw = route.params.id
  const value = Number(raw)
  return Number.isFinite(value) ? value : null
})

const isEdit = computed(() => supplierId.value !== null)

onMounted(async () => {
  if (!isEdit.value || supplierId.value === null) return

  try {
    await suppliers.fetchById(supplierId.value)
    const supplier = suppliers.detail?.supplier
    if (supplier) {
      form.name = supplier.name
      form.phone = supplier.phone ?? ''
      form.address = supplier.address ?? ''
    }
  } catch {
    loadError.value = 'Không tải được dữ liệu nhà cung cấp để chỉnh sửa.'
  }
})

async function submit(): Promise<void> {
  const payload = {
    name: form.name.trim(),
    phone: form.phone.trim(),
    address: form.address.trim(),
  }

  if (!payload.name) return

  if (isEdit.value && supplierId.value !== null) {
    await suppliers.update(supplierId.value, payload)
  } else {
    await suppliers.create(payload)
  }

  await router.push('/suppliers')
}
</script>

<template>
  <section class="space-y-4">
    <header>
      <h2 class="text-xl font-semibold">{{ isEdit ? 'Cập nhật nhà cung cấp' : 'Thêm nhà cung cấp' }}</h2>
      <p class="text-sm text-ink/60">Nhập thông tin liên hệ nhà cung cấp.</p>
    </header>

    <p v-if="loadError || suppliers.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ loadError || suppliers.error }}</p>

    <form class="space-y-3 rounded-2xl border border-black/10 bg-white p-4" @submit.prevent="submit">
      <div class="space-y-1">
        <label class="text-sm font-medium">Tên nhà cung cấp</label>
        <input v-model="form.name" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="Ví dụ: NCC Hoàng Minh" />
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <div class="space-y-1">
          <label class="text-sm font-medium">Điện thoại</label>
          <input v-model="form.phone" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" />
        </div>
      </div>

      <div class="space-y-1">
        <label class="text-sm font-medium">Địa chỉ</label>
        <textarea v-model="form.address" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2" />
      </div>

      <div class="flex gap-2">
        <button type="submit" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="suppliers.saving">
          {{ suppliers.saving ? 'Đang lưu...' : 'Lưu' }}
        </button>
        <button type="button" class="rounded-xl border border-black/15 px-4 py-2 text-sm" @click="router.push('/suppliers')">Hủy</button>
      </div>
    </form>
  </section>
</template>
