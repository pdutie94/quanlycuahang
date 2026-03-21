<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const username = ref('')
const password = ref('')
const errorText = ref('')
const submitting = ref(false)

async function handleSubmit(): Promise<void> {
  errorText.value = ''
  submitting.value = true
  try {
    await auth.login({ username: username.value, password: password.value })
    await router.push({ name: 'dashboard' })
  } catch {
    errorText.value = 'Đăng nhập thất bại. Vui lòng kiểm tra tài khoản hoặc mật khẩu.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top,_#f4efe5,_#efe7d6_45%,_#e6dcc7)] px-4">
    <form class="w-full max-w-md space-y-4 rounded-3xl border border-black/10 bg-white p-6 shadow-card" @submit.prevent="handleSubmit">
      <header class="space-y-1">
        <p class="text-xs uppercase tracking-[0.25em] text-pine/70">Admin Login</p>
        <h1 class="text-2xl font-semibold text-ink">Đăng nhập hệ thống</h1>
      </header>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Tài khoản</label>
        <input
          v-model="username"
          type="text"
          class="w-full rounded-xl border border-gray-300 p-3 outline-none transition focus:border-pine"
          placeholder="Nhập tài khoản"
          autocomplete="username"
          required
        />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Mật khẩu</label>
        <input
          v-model="password"
          type="password"
          class="w-full rounded-xl border border-gray-300 p-3 outline-none transition focus:border-pine"
          placeholder="Nhập mật khẩu"
          autocomplete="current-password"
          required
        />
      </div>

      <p v-if="errorText" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ errorText }}</p>

      <button
        type="submit"
        class="w-full rounded-xl bg-pine px-4 py-3 font-medium text-white transition hover:brightness-110 disabled:opacity-60"
        :disabled="submitting"
      >
        {{ submitting ? 'Đang đăng nhập...' : 'Đăng nhập' }}
      </button>
    </form>
  </div>
</template>
