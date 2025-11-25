import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'


// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
    tailwindcss()
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },

  // --- ESTA É A PARTE QUE TEM DE ADICIONAR ---
  server: {
      proxy: {
        '/api': {
          target: 'http://localhost:8000', // Porta onde corre o php artisan serve
          changeOrigin: true,
          headers: {
            Accept: 'application/json',
            "X-Requested-With": "XMLHttpRequest"
          }
        }
      }
    }
  // --- FIM DO BLOCO ADICIONADO ---
})