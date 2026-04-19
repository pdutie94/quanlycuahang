<template>
  <!-- Product list skeleton - 4 items only -->
  <div class="space-y-3">
    <div v-for="i in 4" :key="i" class="app-list-card space-y-2">
      <div class="flex items-center justify-between gap-2">
        <div class="h-4 w-3/4 animate-pulse rounded bg-slate-200"></div>
        <div class="h-5 w-16 animate-pulse rounded bg-slate-200"></div>
      </div>
      <div class="flex items-center gap-1">
        <div class="h-3 w-12 animate-pulse rounded bg-slate-200"></div>
        <div class="h-3 w-1 animate-pulse rounded bg-slate-300"></div>
        <div class="h-3 w-24 animate-pulse rounded bg-slate-200"></div>
        <div class="h-3 w-1 animate-pulse rounded bg-slate-300"></div>
        <div class="h-3 w-32 animate-pulse rounded bg-slate-200"></div>
      </div>
      <div class="h-4 w-28 animate-pulse rounded bg-slate-200"></div>
    </div>
  </div>
</template>
