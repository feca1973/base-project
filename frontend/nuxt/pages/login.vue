<script setup lang="ts">
const config = useRuntimeConfig()
const route = useRoute()
const email = ref('')
const pending = ref(false)
const sent = ref(false)
const errorMessage = ref('')
const noticeMessage = ref('')

onMounted(() => {
  if (route.query.registered) noticeMessage.value = 'Az email címedet megerősítettük. Most már beléphetsz.'
  if (route.query.loggedOut) noticeMessage.value = 'Sikeresen kiléptél.'
  if (route.query.error) errorMessage.value = 'A jóváhagyó link lejárt vagy már felhasználták.'
})

async function requestLogin() {
  errorMessage.value = ''
  pending.value = true
  try {
    await $fetch(`${config.public.apiBaseUrl}/auth/login`, {
      method: 'POST', body: { email: email.value }, credentials: 'include'
    })
    sent.value = true
  } catch (error: any) {
    errorMessage.value = error.data?.message || 'Nem sikerült elküldeni a belépési emailt.'
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
        <h1 class="mt-6">Nézd meg az emailed</h1>
        <p class="mt-3 text-slate-600">
          Ha aktív fiók tartozik a címhez, elküldtük a belépés jóváhagyásához szükséges linket.
        </p>
        <button class="text-link mt-6" @click="sent = false">Másik email címet adok meg</button>
      </template>
      <template v-else>
        <h1 class="mt-8">Bejelentkezés</h1>
        <p class="mt-3 text-slate-600">A belépési linket emailben küldjük el.</p>
        <p v-if="noticeMessage" class="mt-4 text-sm text-emerald-800" role="status">{{ noticeMessage }}</p>
        <form class="mt-8 space-y-5" @submit.prevent="requestLogin">
          <div>
            <label class="field-label" for="email">Email cím</label>
            <input id="email" v-model="email" class="field-input mt-2" type="email" autocomplete="email" required>
          </div>
          <p v-if="errorMessage" class="form-error" role="alert">{{ errorMessage }}</p>
          <button class="button button-primary w-full" :disabled="pending">
            {{ pending ? 'Küldés…' : 'Belépési link kérése' }}
          </button>
        </form>
        <p class="mt-7 text-center text-sm text-slate-600">
          Még nincs fiókod? <NuxtLink class="text-link" to="/register">Regisztráció</NuxtLink>
        </p>
      </template>
    </section>
    <p class="legal-note">A folytatással elfogadod az <NuxtLink to="/terms">Általános Szerződési Feltételeket</NuxtLink>.</p>
  </main>
</template>
