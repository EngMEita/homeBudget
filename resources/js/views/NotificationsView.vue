<template>
  <section class="panel">
    <div class="history-header">
      <div>
        <h2>{{ t('notifications') }}</h2>
        <p class="lead">{{ t('active_household') }} #{{ householdsStore.activeHouseholdId || '-' }}</p>
      </div>
      <button class="button button-secondary" type="button" @click="loadNotifications">{{ t('refresh') }}</button>
    </div>

    <div class="history-list" v-if="notifications.length">
      <article v-for="notification in notifications" :key="notification.id" class="history-row">
        <div>
          <strong>{{ notification.title ?? t('notifications') }}</strong>
          <div class="token-meta">{{ notification.message }}</div>
          <div class="token-meta">{{ notification.created_at }}</div>
        </div>
        <div class="history-metrics">
          <span>{{ notification.read_at ?? t('pending') }}</span>
          <button v-if="!notification.read_at" class="button button-secondary" type="button" @click="markRead(notification.id)">
            {{ t('mark_read') }}
          </button>
        </div>
      </article>
    </div>
    <p v-else class="lead">{{ t('no_notifications') }}</p>
  </section>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useNotifications } from '../composables/useNotifications'
import { translate } from '../i18n'
import { useHouseholdStore } from '../stores/household'
import { useLocaleStore } from '../stores/locale'

const householdsStore = useHouseholdStore()
const locale = useLocaleStore()
const { notifications, loadNotifications, markRead } = useNotifications()

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

onMounted(loadNotifications)
watch(() => householdsStore.activeHouseholdId, loadNotifications)
</script>
