<template>
  <section class="panel">
    <h2>{{ t('accept_invitation') }}</h2>
    <p class="lead">{{ auth.token ? t('invitation_ready') : t('login_demo_hint') }}</p>
    <div class="actions-row">
      <button v-if="auth.token && !accepted" class="button" type="button" @click="acceptInvitation">{{ t('accept_invitation') }}</button>
      <RouterLink v-if="!auth.token" class="button" :to="`/login?redirect=${encodeURIComponent(route.fullPath)}`">{{ t('login') }}</RouterLink>
      <RouterLink v-if="accepted" class="button" to="/dashboard">{{ t('open_dashboard') }}</RouterLink>
    </div>
    <p v-if="accepted" class="lead">{{ t('invitation_accepted') }}</p>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { translate } from '../i18n'
import { useAuthStore } from '../stores/auth'
import { useLocaleStore } from '../stores/locale'

const route = useRoute()
const auth = useAuthStore()
const locale = useLocaleStore()
const accepted = ref(false)

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

async function acceptInvitation() {
  const token = String(route.params.token)
  const response = await fetch(`/api/invitations/${token}/accept`, {
    method: 'POST',
    headers: auth.authHeaders()
  })
  accepted.value = response.ok
}
</script>
