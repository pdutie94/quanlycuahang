<script setup lang="ts">
import { RouterLink } from 'vue-router'

const links = [
  { to: '/reports/sales', title: 'Báo cáo doanh thu', desc: 'Theo dõi đơn hàng, doanh thu, lợi nhuận.' },
  { to: '/reports/customer-debt', title: 'Công nợ khách hàng', desc: 'Tổng hợp công nợ thu theo khách.' },
  { to: '/reports/supplier-debt', title: 'Công nợ nhà cung cấp', desc: 'Theo dõi số tiền còn phải trả NCC.' },
  { to: '/reports/missing-cost', title: 'Thiếu giá vốn', desc: 'Các dòng bán hàng chưa có giá vốn.' },
  { to: '/reports/inventory', title: 'Tồn kho', desc: 'Tồn kho hiện tại và cảnh báo thiếu hàng.' },
  { to: '/reports/inventory-adjust', title: 'Lịch sử điều chỉnh kho', desc: 'Dòng log điều chỉnh tồn kho sản phẩm.' },
]
</script>

<template>
  <section class="space-y-4">
    <header>
      <h2 class="text-xl font-semibold">Báo cáo</h2>
      <p class="text-sm text-ink/60">Chọn loại báo cáo để xem chi tiết.</p>
    </header>

    <div class="grid gap-3 md:grid-cols-2">
      <RouterLink v-for="item in links" :key="item.to" :to="item.to" class="rounded-2xl border border-black/10 bg-white p-4 transition hover:bg-black/5">
        <h3 class="text-base font-semibold">{{ item.title }}</h3>
        <p class="mt-1 text-sm text-ink/60">{{ item.desc }}</p>
      </RouterLink>
    </div>
  </section>
</template>
