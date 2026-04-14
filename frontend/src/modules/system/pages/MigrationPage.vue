<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { applyMigrations, fetchMigrationInfo, runMigrationVersion } from '../services/migration.api';

const toast = useToast();
const state = reactive({ loading: false, submitting: false });
const info = reactive<{
  current_version: string;
  latest_version: string;
  pending_versions: string[];
  all_versions: string[];
}>({ current_version: '1.0.0', latest_version: '1.0.0', pending_versions: [], all_versions: [] });
const pendingCount = ref(0);

const applyInfoData = (data: any) => {
  info.current_version = data?.current_version || '1.0.0';
  info.latest_version = data?.latest_version || '1.0.0';
  info.pending_versions = data?.pending_versions || [];
  info.all_versions = data?.all_versions || [];
  pendingCount.value = info.pending_versions.length;
};

const loadPage = async () => {
  state.loading = true;
  try {
    const result = await fetchMigrationInfo();
    if (result?.success && result?.data) {
      applyInfoData(result.data);
      return;
    }
    toast.error(result?.message || 'Không thể tải thông tin migration.');
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Không thể tải thông tin migration.');
  } finally {
    state.loading = false;
  }
};

const refreshPage = async () => {
  try {
    const result = await fetchMigrationInfo();
    if (result?.success && result?.data) {
      applyInfoData(result.data);
      return;
    }
    toast.error(result?.message || 'Không thể tải thông tin migration.');
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Không thể tải thông tin migration.');
  }
};

const runPending = async () => {
  if (state.submitting) return;
  state.submitting = true;
  try {
    const result = await applyMigrations();
    if (result?.success) {
      toast.success(result?.message || 'Đã chạy migration thành công.');
      await refreshPage();
      return;
    }
    toast.error(result?.message || 'Không thể chạy migration.');
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Không thể chạy migration.');
  } finally {
    state.submitting = false;
  }
};

const runVersion = async (version: string) => {
  if (state.submitting) return;
  state.submitting = true;
  try {
    const result = await runMigrationVersion({ version });
    if (result?.success) {
      toast.success(result?.message || `Đã chạy version ${version}.`);
      await refreshPage();
      return;
    }
    toast.error(result?.message || `Không thể chạy version ${version}.`);
  } catch (err: any) {
    toast.error(err?.response?.data?.message || `Không thể chạy version ${version}.`);
  } finally {
    state.submitting = false;
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header class="rounded-xl border border-slate-200 bg-white p-4">
      <h1 class="text-lg font-semibold text-slate-900">Migration SQL</h1>
      <p class="mt-1 text-sm text-slate-600">Công cụ migration dữ liệu hệ thống.</p>
    </header>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Phiên bản hiện tại</div><div class="mt-1 font-semibold">{{ info.current_version }}</div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Phiên bản mới nhất</div><div class="mt-1 font-semibold">{{ info.latest_version }}</div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Số migration chờ</div><div class="mt-1 font-semibold">{{ pendingCount }}</div></div>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
      <div class="flex items-center justify-between gap-2">
        <div class="text-sm font-medium text-slate-700">Danh sách migration</div>
        <button type="button" class="rounded-lg bg-brand-600 px-3 py-1 text-sm font-medium text-white disabled:opacity-50" :disabled="state.submitting || !pendingCount" @click="runPending">Chạy tất cả migration chờ</button>
      </div>

      <div v-if="state.loading" class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!info.all_versions.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">Chưa có file migration nào.</div>
      <div v-else class="space-y-2">
        <article v-for="version in info.all_versions" :key="version" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
          <div class="flex items-center justify-between gap-2">
            <div>
              <div class="font-mono font-medium text-slate-900">{{ version }}</div>
              <div class="text-xs text-slate-500">{{ info.pending_versions.includes(version) ? 'Chưa chạy' : 'Đã chạy hoặc cũ hơn' }}</div>
            </div>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 disabled:opacity-50" :disabled="state.submitting" @click="runVersion(version)">Chạy lại</button>
          </div>
        </article>
      </div>
    </section>
  </section>
</template>
