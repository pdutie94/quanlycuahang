<script setup lang="ts">
import { onMounted, ref } from 'vue'

const loading = ref(true)

onMounted(() => {
  setTimeout(() => {
    loading.value = false
  }, 650)
})
</script>

<template>
  <section class="space-y-4">
    <header>
      <h2 class="text-xl font-semibold">Tổng quan</h2>
      <p class="text-sm text-ink/60">Bản chuyển đổi Vue đã sẵn sàng cho Phase 2.</p>
    </header>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
      <article v-for="n in 3" :key="n" class="rounded-2xl border border-black/10 bg-white p-4 shadow-sm">
        <template v-if="loading">
          <div class="h-4 w-20 animate-pulse rounded bg-black/10" />
          <div class="mt-3 h-8 w-28 animate-pulse rounded bg-black/10" />
        </template>
        <template v-else>
          <p class="text-xs uppercase tracking-wider text-ink/60">Metric {{ n }}</p>
          <p class="mt-2 text-2xl font-semibold">{{ n * 12 }}</p>
        </template>
      </article>
    </div>
  </section>
</template>
