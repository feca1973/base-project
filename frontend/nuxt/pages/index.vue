<script setup lang="ts">
const config = useRuntimeConfig()
const user = ref<{ name: string | null; email: string } | null>(null)
const loading = ref(true)
const loggingOut = ref(false)

onMounted(async () => {
  try {
    const result = await $fetch<{ authenticated: boolean; name: string | null; email: string }>(
      `${config.public.apiBaseUrl}/auth/me`,
      { credentials: 'include' }
    )
    if (result.authenticated) user.value = result
    else await navigateTo('/login')
  } catch {
    await navigateTo('/login')
  } finally {
    loading.value = false
  }
})

async function logout() {
  loggingOut.value = true
  try {
    await $fetch(`${config.public.apiBaseUrl}/auth/logout`, {
      method: 'POST', credentials: 'include'
    })
  } finally {
    await navigateTo('/login?loggedOut=1')
  }
}
</script>

<template>
  <main class="page-shell">
    <section v-if="loading" class="auth-card text-center" aria-live="polite">
      <span class="eyebrow">BASE PROJECT</span>
      <p class="mt-6 text-slate-600">Ellenőrzöm a belépést…</p>
    </section>
    <section v-else-if="user" class="auth-card text-center">
      <span class="eyebrow">SIKERES BELÉPÉS</span>
      <h1 class="mt-5">BELÉPTÉL</h1>
      <p class="mt-3 text-slate-600">{{ user.name || user.email }}</p>
      <button class="button button-secondary mt-8 w-full" :disabled="loggingOut" @click="logout">
        {{ loggingOut ? 'Kiléptetés…' : 'Kilépés' }}
      </button>
    </section>
  </main>
</template>
