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
    '/api/**': {
      proxy: 'http://localhost:8080/api/**'
    }
  },
  vite: {
    plugins: [
      tailwindcss()
    ]
  }
})
