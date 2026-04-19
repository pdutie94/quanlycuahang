<template>
  <!-- Category list skeleton - 4 items -->
  <div class="space-y-3">
    <div v-for="i in 4" :key="i" class="app-list-card flex items-center justify-between gap-2">
      <div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div>
      <div class="flex gap-2">
        <div class="h-8 w-8 animate-pulse rounded bg-slate-200"></div>
        <div class="h-8 w-8 animate-pulse rounded bg-slate-200"></div>
      </div>
    </div>
  </div>
</template>
