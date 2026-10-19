<script setup lang="ts">
const config = useRuntimeConfig()
const name = ref('')
const email = ref('')
const termsAccepted = ref(false)
const pending = ref(false)
const sent = ref(false)
const errorMessage = ref('')

async function register() {
  errorMessage.value = ''
  pending.value = true
  try {
    await $fetch(`${config.public.apiBaseUrl}/auth/register`, {
      method: 'POST',
      body: { name: name.value, email: email.value, termsAccepted: termsAccepted.value },
      credentials: 'include'
    })
    sent.value = true
  } catch (error: any) {
    errorMessage.value = error.data?.message || 'Nem sikerült elküldeni a jóváhagyó emailt.'
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <main class="page-shell">
    <section class="auth-card">
      <NuxtLink to="/" class="eyebrow">BASE PROJECT</NuxtLink>
      <template v-if="sent">
        <div class="status-icon mt-8" aria-hidden="true">✉</div>
        <h1 class="mt-6">Erősítsd meg az email címed</h1>
        <p class="mt-3 text-slate-600">Elküldtük a jóváhagyó linket. Ez 10 percig érvényes.</p>
        <NuxtLink class="button button-primary mt-8 w-full" to="/login">Tovább a belépéshez</NuxtLink>
      </template>
      <template v-else>
        <h1 class="mt-8">Regisztráció</h1>
        <p class="mt-3 text-slate-600">A fiók létrehozásához add meg ezeket az adatokat.</p>
        <form class="mt-8 space-y-5" @submit.prevent="register">
          <div>
            <label class="field-label" for="name">Név</label>
            <input id="name" v-model="name" class="field-input mt-2" autocomplete="name" maxlength="180" required>
          </div>
          <div>
            <label class="field-label" for="email">Email cím</label>
            <input id="email" v-model="email" class="field-input mt-2" type="email" autocomplete="email" required>
          </div>
          <label class="checkbox-row">
            <input v-model="termsAccepted" type="checkbox" required>
            <span>Elfogadom az <NuxtLink class="text-link" to="/terms" target="_blank">Általános Szerződési Feltételeket</NuxtLink>.</span>
          </label>
          <p v-if="errorMessage" class="form-error" role="alert">{{ errorMessage }}</p>
          <button class="button button-primary w-full" :disabled="pending">
            {{ pending ? 'Küldés…' : 'Regisztráció' }}
          </button>
        </form>
        <p class="mt-7 text-center text-sm text-slate-600">
          Van már fiókod? <NuxtLink class="text-link" to="/login">Bejelentkezés</NuxtLink>
        </p>
      </template>
    </section>
  </main>
</template>
