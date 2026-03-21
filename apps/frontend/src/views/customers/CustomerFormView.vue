<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCustomersStore } from '../../stores/customers'

const route = useRoute()
const router = useRouter()
const customers = useCustomersStore()
const loadError = ref('')

const form = reactive({
  name: '',
  phone: '',
  email: '',
  address: '',
})

const customerId = computed(() => {
  const raw = route.params.id
  const value = Number(raw)
  return Number.isFinite(value) ? value : null
})

const isEdit = computed(() => customerId.value !== null)

onMounted(async () => {
  if (!isEdit.value || customerId.value === null) return

  try {
    await customers.fetchById(customerId.value)
    const customer = customers.detail?.customer
    if (customer) {
      form.name = customer.name
      form.phone = customer.phone ?? ''
      form.email = customer.email ?? ''
      form.address = customer.address ?? ''
    }
  } catch {
    loadError.value = 'Không tải được dữ liệu khách hàng để chỉnh sửa.'
  }
})

async function submit(): Promise<void> {
  const payload = {
    name: form.name.trim(),
    phone: form.phone.trim(),
    email: form.email.trim(),
    address: form.address.trim(),
  }

  if (!payload.name) return

  if (isEdit.value && customerId.value !== null) {
    await customers.update(customerId.value, payload)
  } else {
    await customers.create(payload)
  }

  await router.push('/customers')
}
</script>

<template>
  <section class="space-y-4">
    <header>
      <h2 class="text-xl font-semibold">{{ isEdit ? 'Cập nhật khách hàng' : 'Thêm khách hàng' }}</h2>
      <p class="text-sm text-ink/60">Nhập thông tin liên hệ khách hàng.</p>
    </header>

    <p v-if="loadError || customers.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ loadError || customers.error }}</p>

    <form class="space-y-3 rounded-2xl border border-black/10 bg-white p-4" @submit.prevent="submit">
      <div class="space-y-1">
        <label class="text-sm font-medium">Tên khách hàng</label>
        <input v-model="form.name" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="Ví dụ: Nguyễn Văn A" />
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <div class="space-y-1">
          <label class="text-sm font-medium">Điện thoại</label>
          <input v-model="form.phone" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" />
        </div>
        <div class="space-y-1">
          <label class="text-sm font-medium">Email</label>
          <input v-model="form.email" type="email" class="w-full rounded-xl border border-gray-300 px-3 py-2" />
        </div>
      </div>

      <div class="space-y-1">
        <label class="text-sm font-medium">Địa chỉ</label>
        <textarea v-model="form.address" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2" />
      </div>

      <div class="flex gap-2">
        <button type="submit" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="customers.saving">
          {{ customers.saving ? 'Đang lưu...' : 'Lưu' }}
        </button>
        <button type="button" class="rounded-xl border border-black/15 px-4 py-2 text-sm" @click="router.push('/customers')">Hủy</button>
      </div>
    </form>
  </section>
</template>
