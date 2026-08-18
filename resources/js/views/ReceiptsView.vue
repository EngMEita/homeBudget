<template>
  <main class="shell">
    <ReceiptPanel
      v-if="activeHousehold"
      v-model="receiptForm"
      :active-receipt="activeReceipt"
      :accounts="accounts"
      :currencies="currencies"
      :categories="categories"
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
  <section class="panel" v-if="receipts.length">
    <h2>{{ t('receipt_history') }}</h2>
    <article v-for="receipt in receipts" :key="receipt.id" class="history-row">
      <div><strong>{{ minorToDecimal(receipt.total_minor_amount) }}</strong><div class="token-meta">{{ receipt.receipt_status }} · {{ receipt.categorization_status }}</div></div>
      <button class="button button-danger" type="button" @click="removeReceipt(receipt.id)">{{ t('delete') }}</button>
    </article>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import ReceiptPanel from '../components/dashboard/ReceiptPanel.vue'
import { useAccounts } from '../composables/useAccounts'
import { useCategories } from '../composables/useCategories'
import { useHouseholds } from '../composables/useHouseholds'
import { useReceipts } from '../composables/useReceipts'
import { translate } from '../i18n'
import { useLocaleStore } from '../stores/locale'
import { useAuthStore } from '../stores/auth'
import { minorToDecimal } from '../money'
const auth = useAuthStore()
const receipts = ref<Array<{ id: number; total_minor_amount: number; receipt_status: string; categorization_status: string }>>([])

const locale = useLocaleStore()
const { activeHousehold, members, loadHouseholds, loadMembers } = useHouseholds()
const { accounts, currencies, loadAccounts } = useAccounts()
const { categories, loadCategories } = useCategories()
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
  await loadCategories()
  await loadReceipts()
})

async function loadReceipts() {
  if (!activeHousehold.value) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/receipts`, { headers: auth.authHeaders() })
  if (response.ok) receipts.value = (await response.json()).data ?? []
}

async function removeReceipt(id: number) {
  if (!activeHousehold.value || !window.confirm(t('confirm_delete'))) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/receipts/${id}`, { method: 'DELETE', headers: auth.authHeaders() })
  if (response.ok) await loadReceipts()
}
</script>
