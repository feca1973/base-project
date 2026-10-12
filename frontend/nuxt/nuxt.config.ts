import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  ssr: true,
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || '/api'
    }
  },
  routeRules: {
    // SSR-side /api calls run inside the container, where localhost is not the proxy;
    // docker-compose points this at the backend service (http://backend:80).
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
