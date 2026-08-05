<template>
  <section class="panel">
    <h2>{{ t('settings') }}</h2>
    <div class="filters-grid">
      <label class="field">
        <span>{{ t('language') }}</span>
        <select v-model="locale.locale" @change="locale.setLocale(locale.locale)">
          <option value="en">{{ t('english') }}</option>
          <option value="ar">{{ t('arabic') }}</option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('current_device_label') }}</span>
        <input v-model="tokenLabel" type="text" maxlength="100" :placeholder="t('device_placeholder')" />
      </label>
      <label class="field">
        <span>{{ t('current_token') }}</span>
        <input :value="auth.token ? `${auth.token.slice(0, 10)}...` : t('not_available')" type="text" readonly />
      </label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="saveSettings">{{ t('save_settings') }}</button>
      <RouterLink class="button button-secondary" to="/security">{{ t('security_sessions') }}</RouterLink>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { translate } from '../i18n'
import { useAuthStore } from '../stores/auth'
import { useLocaleStore } from '../stores/locale'

const auth = useAuthStore()
const locale = useLocaleStore()
const tokenLabel = ref(auth.tokenLabel)

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

function saveSettings() {
  auth.setTokenLabel(tokenLabel.value)
  locale.setLocale(locale.locale)
}
</script>
