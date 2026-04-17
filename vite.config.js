import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'node:path';

export default defineConfig({
  plugins: [vue()],
  publicDir: false,
  base: '/assets/spa/',
  build: {
    outDir: resolve(__dirname, 'public/assets/spa'),
    emptyOutDir: false,
    sourcemap: true,
    rollupOptions: {
      input: resolve(__dirname, 'frontend/src/main.ts'),
      output: {
        entryFileNames: 'main.js',
        chunkFileNames: 'chunks/[name].js',
        assetFileNames: 'assets/[name][extname]',

        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('@lucide')) return 'icons'
            return 'vendor'
          }

          if (id.includes('/modules/auth/')) return 'auth'
          if (id.includes('/modules/category/')) return 'category'
          if (id.includes('/modules/customer/')) return 'customer'
          if (id.includes('/modules/dashboard/')) return 'dashboard'
          if (id.includes('/modules/material-price/')) return 'material-price'
          if (id.includes('/modules/order/')) return 'order'
          if (id.includes('/modules/pos/')) return 'pos'
          if (id.includes('/modules/product/')) return 'product'
          if (id.includes('/modules/purchase/')) return 'purchase'
          if (id.includes('/modules/report/')) return 'report'
          if (id.includes('/modules/supplier/')) return 'supplier'
          if (id.includes('/modules/system/')) return 'system'
          if (id.includes('/modules/unit/')) return 'unit'
        }
      }
    }
  }
});
