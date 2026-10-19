import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  ssr: true,
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],
  app: {
    head: {
      title: 'Base Project',
      meta: [
        { name: 'theme-color', content: '#f4f5ef' },
        { name: 'description', content: 'Biztonságos belépés emailes jóváhagyással.' },
        { name: 'apple-mobile-web-app-capable', content: 'yes' }
      ],
      link: [
        { rel: 'manifest', href: '/manifest.webmanifest' },
        { rel: 'icon', type: 'image/svg+xml', href: '/icon.svg' },
        { rel: 'apple-touch-icon', href: '/icon.svg' }
      ]
    }
  },
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
