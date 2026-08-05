<template>
  <section class="panel">
    <h2>{{ t('security_sessions') }}</h2>
    <p class="lead">Manage active tokens and rotate or revoke a device session.</p>
    <label class="field">
      <span>{{ t('current_device_label') }}</span>
      <input v-model="auth.tokenLabel" type="text" maxlength="100" placeholder="Office laptop" />
    </label>
    <div class="token-list">
      <div v-for="token in tokens" :key="token.id" class="token-row">
        <div>
          <strong>{{ token.name }}</strong>
          <div class="token-meta">{{ token.last_used_at ?? 'never' }}</div>
        </div>
        <button class="button button-secondary" type="button" @click="revokeToken(token.id)">Revoke</button>
      </div>
    </div>
    <button class="button" type="button" @click="loadTokens">Refresh tokens</button>
    <button class="button" type="button" @click="rotateToken">{{ t('rotate_current_token') }}</button>
    <button class="button button-secondary" type="button" @click="revokeCurrent">{{ t('revoke_current_device') }}</button>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useLocaleStore } from '../stores/locale'
import { translate } from '../i18n'

type Token = { id: number; name: string; last_used_at: string | null }

const auth = useAuthStore()
const locale = useLocaleStore()
const tokens = ref<Token[]>([])

function t(key: string) {
  return translate(locale.locale, key)
}

async function loadTokens() {
  const response = await fetch('/api/auth/tokens', { headers: auth.authHeaders() })
  const payload = await response.json()
  tokens.value = payload.tokens ?? []
}

async function rotateToken() {
  const response = await fetch('/api/auth/tokens/rotate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify({ label: auth.tokenLabel })
  })
  const payload = await response.json()
  if (payload.token) auth.setToken(payload.token, auth.tokenLabel)
  await loadTokens()
}

async function revokeCurrent() {
  await fetch('/api/auth/tokens/current', { method: 'DELETE', headers: auth.authHeaders() })
  auth.clearToken()
  await loadTokens()
}

async function revokeToken(id: number) {
  await fetch(`/api/auth/tokens/${id}`, { method: 'DELETE', headers: auth.authHeaders() })
  await loadTokens()
}

onMounted(loadTokens)
</script>
