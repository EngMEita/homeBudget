<template>
  <main class="shell">
    <ReceiptPanel
      v-if="activeHousehold"
      v-model="receiptForm"
      :active-receipt="activeReceipt"
      :accounts="accounts"
      :currencies="currencies"
      @create-receipt="createReceipt"
      @queue-offline-receipt="enqueueOfflineReceipt"
      @categorize-receipt="categorizeReceipt"
      @select-attachment="selectReceiptAttachment"
      @upload-attachment="uploadReceiptAttachment"
      @queue-attachment-offline="enqueueOfflineReceiptAttachment"
      @complete-receipt="completeReceipt"
    />
    <section v-else class="panel">
      <h2>{{ t('receipts') }}</h2>
      <p class="lead">{{ t('select_household') }}</p>
    </section>
  </main>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import ReceiptPanel from '../components/dashboard/ReceiptPanel.vue'
import { useAccounts } from '../composables/useAccounts'
import { useHouseholds } from '../composables/useHouseholds'
import { useReceipts } from '../composables/useReceipts'
import { translate } from '../i18n'
import { useLocaleStore } from '../stores/locale'

const locale = useLocaleStore()
const { activeHousehold, members, loadHouseholds, loadMembers } = useHouseholds()
const { accounts, currencies, loadAccounts } = useAccounts()
const {
  activeReceipt,
  receiptForm,
  createReceipt,
  categorizeReceipt,
  selectReceiptAttachment,
  uploadReceiptAttachment,
  enqueueOfflineReceipt,
  enqueueOfflineReceiptAttachment,
  completeReceipt
} = useReceipts(activeHousehold, members)

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

onMounted(async () => {
  await loadHouseholds()
  await loadMembers()
  await loadAccounts()
})
</script>
