import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: ['resources/js/main.ts'],
      refresh: true
    }),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'robots.txt'],
      manifest: {
        name: 'HomeBudget',
        short_name: 'HomeBudget',
        description: 'A multilingual household budgeting PWA for families.',
        theme_color: '#17453b',
        background_color: '#f6efe2',
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/dashboard',
        scope: '/',
        lang: 'en',
        dir: 'ltr',
        categories: ['finance', 'productivity'],
        icons: [
          {
            src: '/pwa-icon.svg',
            sizes: 'any',
            type: 'image/svg+xml',
            purpose: 'any maskable'
          }
        ]
      },
      workbox: {
        navigateFallback: '/',
        cleanupOutdatedCaches: true,
        globPatterns: ['**/*.{js,css,html,ico,svg,png,webmanifest}'],
        runtimeCaching: [
          {
            urlPattern: ({ request }) => request.mode === 'navigate',
            handler: 'NetworkFirst',
            options: {
              cacheName: 'homebudget-pages',
              networkTimeoutSeconds: 5
            }
          },
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/build/'),
            handler: 'CacheFirst',
            options: {
              cacheName: 'homebudget-assets'
            }
          },
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/api/'),
            handler: 'NetworkOnly',
            options: {
              backgroundSync: {
                name: 'homebudget-api-retry',
                options: {
                  maxRetentionTime: 24 * 60
                }
              }
            }
          }
        ]
      },
      devOptions: {
        enabled: true
      }
    })
  ],
  resolve: {
    alias: {
      '@': '/resources/js'
    }
  },
  build: {}
})
