<template>
  <section class="panel">
    <div class="history-header">
      <div>
        <h2>{{ isRegistering ? t('registration') : t('login') }}</h2>
        <p class="lead">{{ t('login_demo_hint') }}</p>
      </div>
      <label class="field compact">
        <span>{{ t('language') }}</span>
        <select v-model="locale.locale" @change="locale.setLocale(locale.locale)">
          <option value="en">{{ t('english') }}</option>
          <option value="ar">{{ t('arabic') }}</option>
        </select>
      </label>
    </div>

    <div class="filters-grid">
      <label v-if="isRegistering" class="field">
        <span>{{ t('name') }}</span>
        <input v-model="form.name" type="text" />
      </label>
      <label class="field">
        <span>{{ t('email') }}</span>
        <input v-model="form.email" type="email" autocomplete="email" />
      </label>
      <label class="field">
        <span>{{ t('password') }}</span>
        <input v-model="form.password" type="password" autocomplete="current-password" />
      </label>
      <label v-if="isRegistering" class="field">
        <span>{{ t('password_confirmation') }}</span>
        <input v-model="form.password_confirmation" type="password" autocomplete="new-password" />
      </label>
      <label class="field">
        <span>{{ t('current_device_label') }}</span>
        <input v-model="form.device_name" type="text" :placeholder="t('device_placeholder')" />
      </label>
    </div>

    <div class="actions-row">
      <button class="button" type="button" @click="submit">{{ isRegistering ? t('register') : t('login') }}</button>
      <button class="button button-secondary" type="button" @click="isRegistering = !isRegistering">
        {{ isRegistering ? t('login') : t('registration') }}
      </button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { translate } from '../i18n'
import { useAuthStore } from '../stores/auth'
import { useLocaleStore } from '../stores/locale'

const auth = useAuthStore()
const locale = useLocaleStore()
const router = useRouter()
const route = useRoute()
const isRegistering = ref(false)
const form = reactive({
  name: '',
  email: 'owner@example.com',
  password: 'password',
  password_confirmation: 'password',
  device_name: auth.tokenLabel
})

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

async function submit() {
  const ok = isRegistering.value
    ? await auth.register(form.name, form.email, form.password, form.password_confirmation, form.device_name)
    : await auth.login(form.email, form.password, form.device_name)

  if (ok) await router.push(String(route.query.redirect || '/dashboard'))
}
</script>
