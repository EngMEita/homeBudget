<template>
  <main class="shell">
    <section class="hero">
      <p class="eyebrow">{{ t('app_brand') }}</p>
      <h1>{{ t('app_title') }}</h1>
      <p class="lead">{{ t('app_subtitle') }}</p>
      <nav class="app-nav" :aria-label="t('primary_navigation')">
        <RouterLink to="/dashboard">{{ t('dashboard') }}</RouterLink>
        <RouterLink to="/accounts">{{ t('accounts') }}</RouterLink>
        <RouterLink to="/categories">{{ t('categories') }}</RouterLink>
        <RouterLink to="/receipts">{{ t('receipts') }}</RouterLink>
        <RouterLink to="/reports">{{ t('reports') }}</RouterLink>
        <RouterLink to="/notifications">{{ t('notifications') }}</RouterLink>
        <RouterLink to="/offline-sync">{{ t('offline_sync') }}</RouterLink>
        <RouterLink to="/transactions">{{ t('transaction_history') }}</RouterLink>
        <RouterLink to="/settings">{{ t('settings') }}</RouterLink>
        <RouterLink to="/security">{{ t('security_sessions') }}</RouterLink>
      </nav>
    </section>

    <RouterView />
  </main>
</template>

<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { useLocaleStore } from './stores/locale'
import { translate } from './i18n'

const locale = useLocaleStore()

function t(key: string) {
  return translate(locale.locale, key)
}
</script>
