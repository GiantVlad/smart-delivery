import { fileURLToPath, URL } from "node:url";
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin';

// https://vitejs.dev/config/
export default defineConfig({
  define: {
    'process.env': process.env
  },
  plugins: [
    vue(),
    laravel({
      input: ['resources/css/style.css', 'resources/js/main.js'],
      refresh: true,
    }),
  ],
  resolve: {
    alias: {
      // '@tailwindConfig': path.resolve(__dirname, 'tailwind.config.js'),
      "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
    },
  },
  optimizeDeps: {
    include: [
      '@tailwindConfig',
    ]
  },
  build: {
    commonjsOptions: {
      transformMixedEsModules: true,
    }
  }
})
