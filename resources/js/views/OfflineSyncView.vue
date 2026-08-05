<template>
  <main class="shell">
    <OfflineSyncPanel
      v-if="activeHousehold"
      v-model="offlineForm"
      :operations="offlineQueue.operations"
      :conflicts="offlineQueue.conflicts"
      @queue-transaction="enqueueOfflineTransaction"
      @sync-queue="syncOfflineQueue"
      @discard-conflict="offlineQueue.discard"
      @retry-conflict="retryConflict"
    />
    <section v-else class="panel">
      <h2>{{ t('offline_sync') }}</h2>
      <p class="lead">{{ t('select_household') }}</p>
    </section>
  </main>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import OfflineSyncPanel from '../components/dashboard/OfflineSyncPanel.vue'
import { useHouseholds } from '../composables/useHouseholds'
import { useOfflineSync } from '../composables/useOfflineSync'
import { translate } from '../i18n'
import { useLocaleStore } from '../stores/locale'

const locale = useLocaleStore()
const { activeHousehold, loadHouseholds } = useHouseholds()
const { offlineQueue, offlineForm, enqueueOfflineTransaction, syncOfflineQueue, retryConflict, loadOfflineQueue } = useOfflineSync(activeHousehold)

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

onMounted(async () => {
  await loadOfflineQueue()
  await loadHouseholds()
})
</script>
