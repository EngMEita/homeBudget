<template>
  <section class="panel">
    <h2>{{ t('receipts') }}</h2>
    <div class="filters-grid">
      <label class="field"><span>{{ t('account_id') }}</span><input v-model="model.account_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('currency_id') }}</span><input v-model="model.currency_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('total') }}</span><input v-model="model.total_minor_amount" type="number" min="0.01" step="0.01" placeholder="1500.25" /></label>
      <label class="field"><span>{{ t('date') }}</span><input v-model="model.transaction_date" type="date" /></label>
      <label class="field"><span>{{ t('category_id') }}</span><input v-model="model.category_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('allocation') }}</span><input v-model="model.allocation_minor_amount" type="number" min="0.01" step="0.01" placeholder="1500.25" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-receipt')">{{ t('create_receipt') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('queue-offline-receipt')">{{ t('queue_offline_receipt') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('categorize-receipt')">{{ t('save_categorization') }}</button>
    </div>
    <div class="filters-grid" v-if="activeReceipt">
      <label class="field">
        <span>{{ t('receipt_attachment') }}</span>
        <input type="file" accept="image/*,.pdf" @change="$emit('select-attachment', $event)" />
      </label>
    </div>
    <div class="actions-row" v-if="activeReceipt">
      <button class="button button-secondary" type="button" @click="$emit('upload-attachment')">{{ t('upload_attachment') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('queue-attachment-offline')">{{ t('queue_attachment_offline') }}</button>
      <button class="button" type="button" @click="$emit('complete-receipt')">{{ t('complete_receipt') }}</button>
    </div>
    <article class="history-row" v-if="activeReceipt">
      <div>
        <strong>{{ t('receipt_number', { id: activeReceipt.id }) }}</strong>
        <div class="token-meta">{{ activeReceipt.categorization_status }}</div>
      </div>
      <div class="history-metrics">
        <span>{{ t('categorized_amount', { amount: minorToDecimal(activeReceipt.categorized_minor_amount) }) }}</span>
        <span>{{ t('remaining_amount', { amount: minorToDecimal(activeReceipt.remaining_uncategorized_minor_amount) }) }}</span>
        <span>{{ t('attachments_count', { count: activeReceipt.attachments.length }) }}</span>
      </div>
    </article>
  </section>
</template>

<script setup lang="ts">
import type { Receipt } from '../../types/dashboard'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'
import { minorToDecimal } from '../../money'

defineProps<{ activeReceipt: Receipt | null }>()
defineEmits<{
  'create-receipt': []
  'queue-offline-receipt': []
  'categorize-receipt': []
  'select-attachment': [event: Event]
  'upload-attachment': []
  'queue-attachment-offline': []
  'complete-receipt': []
}>()
const model = defineModel<{ account_id: string; currency_id: string; total_minor_amount: string; transaction_date: string; category_id: string; allocation_minor_amount: string }>({ required: true })
const locale = useLocaleStore()

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}
</script>
