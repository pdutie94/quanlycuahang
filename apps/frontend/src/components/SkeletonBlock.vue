<script setup lang="ts">
withDefaults(
  defineProps<{
    lines?: number
    heightClass?: string
    widthClass?: string
    roundedClass?: string
  }>(),
  {
    lines: 1,
    heightClass: 'h-4',
    widthClass: 'w-full',
    roundedClass: 'rounded-xl',
  },
)
</script>

<template>
  <div class="animate-pulse space-y-2">
    <div
      v-for="line in lines"
      :key="line"
      class="bg-black/10"
      :class="[heightClass, roundedClass, line === lines ? 'w-2/3' : widthClass]"
    />
  </div>
</template>
