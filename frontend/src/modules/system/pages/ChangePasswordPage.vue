<script setup>
import { reactive } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { changePassword } from '../../auth/services/auth.api';

const toast = useToast();
const form = reactive({ current_password: '', new_password: '', confirm_password: '' });
const state = reactive({ loading: false });

const submit = async () => {
  if (state.loading) return;
  state.loading = true;
  try {
    const result = await changePassword({ ...form });
    if (result?.success) {
      toast.success(result?.message || 'Đã đổi mật khẩu thành công.');
      form.current_password = '';
      form.new_password = '';
      form.confirm_password = '';
      return;
    }
    toast.error(result?.message || 'Không thể đổi mật khẩu.');
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Không thể đổi mật khẩu.');
  } finally {
    state.loading = false;
  }
};
</script>

<template>
  <section class="mx-auto w-full max-w-md space-y-4">
    <header>
      <h1 class="text-lg font-semibold text-slate-900">Đổi mật khẩu</h1>
    </header>
    <form class="rounded-xl border border-slate-200 bg-white p-4 space-y-3" @submit.prevent="submit">
      <div>
        <label class="block text-sm text-slate-700">Mật khẩu hiện tại</label>
        <input v-model="form.current_password" type="password" required class="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm" />
      </div>
      <div>
        <label class="block text-sm text-slate-700">Mật khẩu mới</label>
        <input v-model="form.new_password" type="password" required class="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm" />
      </div>
      <div>
        <label class="block text-sm text-slate-700">Xác nhận mật khẩu mới</label>
        <input v-model="form.confirm_password" type="password" required class="mt-1 h-10 w-full rounded-xl border border-slate-300 px-3 text-sm" />
      </div>
      <button type="submit" class="h-10 rounded-xl bg-brand-600 px-4 text-sm font-medium text-white" :disabled="state.loading">
        {{ state.loading ? 'Đang lưu...' : 'Lưu mật khẩu mới' }}
      </button>
    </form>
  </section>
</template>
