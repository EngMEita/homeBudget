<template>
  <section class="panel">
    <div class="panel-heading">
      <div>
        <p class="eyebrow">{{ t('workflow_receipts') }}</p>
        <h2>{{ t('receipts') }}</h2>
        <p class="lead">{{ activeReceipt ? t('receipt_continue_hint') : t('receipt_start_hint') }}</p>
      </div>
      <span class="status-pill" :class="{ ready: Boolean(activeReceipt) }">{{ activeReceipt ? t('ready') : t('needs_input') }}</span>
    </div>

    <div v-if="!hasAccounts" class="empty-state">
      <strong>{{ t('no_payment_sources_ready') }}</strong>
      <p>{{ t('create_account_first_hint') }}</p>
    </div>

    <div v-else class="filters-grid">
      <label class="field">
        <span>{{ t('payment_source') }}</span>
        <select v-model="model.account_id" @change="syncCurrencyFromAccount">
          <option value="">{{ t('select_account') }}</option>
          <option v-for="account in activeAccounts" :key="account.id" :value="String(account.id)">
            {{ account.name }} · {{ account.currency_code ?? currencyCode(account.currency_id) }}
          </option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('currency') }}</span>
        <select v-model="model.currency_id">
          <option v-for="currency in currencies" :key="currency.id" :value="String(currency.id)">
            {{ currency.code }}
          </option>
        </select>
      </label>
      <label class="field"><span>{{ t('total') }}</span><input v-model="model.total_minor_amount" type="number" min="0.01" step="0.01" placeholder="1500.25" /></label>
      <label class="field"><span>{{ t('date') }}</span><input v-model="model.transaction_date" type="date" /></label>
      <label class="field"><span>{{ t('category_id') }}</span><input v-model="model.category_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('allocation') }}</span><input v-model="model.allocation_minor_amount" type="number" min="0.01" step="0.01" placeholder="1500.25" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" :disabled="!canStartReceipt" @click="$emit('create-receipt')">{{ t('create_receipt') }}</button>
      <button class="button button-secondary" type="button" :disabled="!canStartReceipt" @click="$emit('queue-offline-receipt')">{{ t('queue_offline_receipt') }}</button>
      <button class="button button-secondary" type="button" :disabled="!activeReceipt" @click="$emit('categorize-receipt')">{{ t('save_categorization') }}</button>
      <strong>{{ receiptStatus }}</strong>
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
import { computed } from 'vue'
import type { Receipt } from '../../types/dashboard'
import type { Account, CurrencyOption } from '../../composables/useAccounts'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'
import { minorToDecimal } from '../../money'

const props = defineProps<{ activeReceipt: Receipt | null; accounts: Account[]; currencies: CurrencyOption[] }>()
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
const activeAccounts = computed(() => props.accounts.filter((account) => account.is_active))
const hasAccounts = computed(() => activeAccounts.value.length > 0)
const canStartReceipt = computed(() => Boolean(model.value.account_id && model.value.currency_id && Number(model.value.total_minor_amount) > 0))
const receiptStatus = computed(() => {
  if (!hasAccounts.value) return t('create_account_first_hint')
  if (!model.value.account_id) return t('select_all_payment_sources')
  if (!model.value.currency_id) return t('select_currency_first')
  if (Number(model.value.total_minor_amount) <= 0) return t('enter_total_first')
  return props.activeReceipt ? t('receipt_ready_for_details') : t('receipt_ready_to_create')
})

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

function currencyCode(id: number) {
  return props.currencies.find((currency) => currency.id === id)?.code ?? ''
}

function syncCurrencyFromAccount() {
  const account = props.accounts.find((item) => String(item.id) === model.value.account_id)
  if (account) model.value.currency_id = String(account.currency_id)
}
</script>
