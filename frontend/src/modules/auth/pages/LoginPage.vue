<script setup lang="ts">
import { reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from '../../../shared/composables/useToast';
import { login } from '../services/auth.api';

const router = useRouter();
const route = useRoute();
const toast = useToast();
const form = reactive({ username: 'admin', password: '' });
const state = reactive({ loading: false });

const submit = async () => {
  if (state.loading) return;
  state.loading = true;
  try {
    const result = await login({ ...form });
    if (result?.success) {
      toast.success(result?.message || 'Đăng nhập thành công.');
      const redirectPath = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard';
      router.push(redirectPath);
      return;
    }
    toast.error(result?.message || 'Đăng nhập thất bại.');
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Đăng nhập thất bại.');
  } finally {
    state.loading = false;
  }
};
</script>

<template>
  <section class="mx-auto w-full max-w-sm space-y-4">
    <header class="text-center">
      <h1 class="text-xl font-semibold text-slate-900">Đăng nhập</h1>
      <p class="mt-1 text-sm text-slate-600">Đăng nhập hệ thống quản lý cửa hàng.</p>
    </header>

    <form class="rounded-xl border border-slate-200 bg-white p-4 space-y-3" @submit.prevent="submit">
      <div>
        <label class="block text-sm text-slate-700">Tài khoản</label>
        <input v-model="form.username" type="text" required class="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm" />
      </div>
      <div>
        <label class="block text-sm text-slate-700">Mật khẩu</label>
        <input v-model="form.password" type="password" required autofocus class="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm" />
      </div>
      <button type="submit" class="h-10 w-full rounded-xl bg-brand-600 text-sm font-medium text-white" :disabled="state.loading">
        {{ state.loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
      </button>
    </form>
  </section>
</template>
