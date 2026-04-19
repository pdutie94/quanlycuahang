<template>
  <section class="space-y-4">

    <!-- Products Section Skeleton -->
    <section class="app-card space-y-3">
      <div class="flex items-center justify-between">
        <div class="h-5 w-20 animate-pulse rounded bg-slate-200"></div>
        <div class="h-8 w-20 animate-pulse rounded-lg bg-slate-200"></div>
      </div>

      <!-- Cart items - 3 items -->
      <div class="space-y-2">
        <div v-for="i in 3" :key="i" class="rounded-xl border border-slate-200 bg-white px-3 py-2 space-y-2">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1 space-y-2">
              <div class="h-4 w-3/4 animate-pulse rounded bg-slate-200"></div>
              <div class="flex items-center gap-1">
                <div class="h-6 w-6 animate-pulse rounded border border-slate-300 bg-slate-100"></div>
                <div class="h-6 w-10 animate-pulse rounded border border-slate-300 bg-slate-100"></div>
                <div class="h-6 w-6 animate-pulse rounded border border-slate-300 bg-slate-100"></div>
                <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <div class="h-5 w-5 animate-pulse rounded bg-slate-200"></div>
              <div class="h-4 w-16 animate-pulse rounded bg-slate-200"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Manual items header -->
      <div class="flex items-center justify-between pt-2">
        <div class="h-5 w-24 animate-pulse rounded bg-slate-200"></div>
        <div class="h-8 w-20 animate-pulse rounded-lg bg-slate-200"></div>
      </div>

      <!-- Summary -->
      <div class="space-y-2 pt-2">
        <div class="flex items-center justify-between">
          <div class="h-4 w-16 animate-pulse rounded bg-slate-200"></div>
          <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
        </div>
        <div class="flex items-center justify-between">
          <div class="h-4 w-16 animate-pulse rounded bg-slate-200"></div>
          <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
        </div>
        <div class="flex items-center justify-between pt-1">
          <div class="h-6 w-20 animate-pulse rounded bg-slate-200"></div>
          <div class="h-6 w-24 animate-pulse rounded bg-slate-200"></div>
        </div>
      </div>
    </section>

    <!-- Customer Section Skeleton -->
    <section class="app-card space-y-3">
      <div class="h-5 w-32 animate-pulse rounded bg-slate-200"></div>
      <div class="h-10 w-full animate-pulse rounded-lg bg-slate-200"></div>
      <div class="h-10 w-full animate-pulse rounded-lg bg-slate-200"></div>
    </section>
  </section>
</template>
