import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  // The admin panel is authenticated and behind a non-root baseURL; SPA mode keeps the
  // client fetches to /api/** simple (same-origin via nginx).
  ssr: false,
  app: {
    baseURL: process.env.NUXT_APP_BASE_URL || '/'
  },
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || '/api'
    }
  },
  routeRules: {
    '/api/**': {
      proxy: `${process.env.NUXT_INTERNAL_API_BASE_URL || 'http://localhost:8080'}/api/**`
    }
  },
  vite: {
    plugins: [
      tailwindcss()
    ]
  }
})
