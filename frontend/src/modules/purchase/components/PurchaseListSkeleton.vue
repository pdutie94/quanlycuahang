<template>
  <!-- Purchase list skeleton - 4 items -->
  <div class="space-y-3">
    <div v-for="i in 4" :key="i" class="app-list-card space-y-2">
      <div class="flex items-center justify-between gap-2">
        <div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div>
        <div class="h-5 w-20 animate-pulse rounded bg-slate-200"></div>
      </div>
      <div class="flex items-center gap-1 text-sm">
        <div class="h-3 w-16 animate-pulse rounded bg-slate-200"></div>
        <div class="h-3 w-1 animate-pulse rounded bg-slate-300"></div>
        <div class="h-3 w-24 animate-pulse rounded bg-slate-200"></div>
      </div>
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
        <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
        <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
      </div>
    </div>
  </div>
</template>
