<template>
  <section class="panel">
    <div class="panel-heading">
      <div>
        <p class="eyebrow">{{ t('workflow_sync') }}</p>
        <h2>{{ t('offline_sync') }}</h2>
        <p class="lead">{{ t('offline_sync_hint') }}</p>
      </div>
      <span class="status-pill" :class="{ ready: canQueue }">{{ canQueue ? t('ready') : t('needs_input') }}</span>
    </div>
    <div class="filters-grid">
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
      <label class="field"><span>{{ t('amount') }}</span><input v-model="model.amount_minor" type="number" min="0.01" step="0.01" placeholder="1500.25" /></label>
      <label class="field"><span>{{ t('description') }}</span><input v-model="model.description" type="text" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" :disabled="!canQueue" @click="$emit('queue-transaction')">{{ t('queue_offline_expense') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('sync-queue')">{{ t('sync_queue') }}</button>
      <strong>{{ syncStatus }}</strong>
    </div>
    <p class="lead">{{ t('pending_operations', { count: operations.length }) }}</p>
    <div class="history-list" v-if="operations.length">
      <article v-for="operation in operations" :key="operation.client_uuid" class="history-row">
        <div>
          <strong>{{ operation.operation_type }}</strong>
          <div class="token-meta">{{ t('operation_attempts', { count: operation.attempts ?? 0 }) }} · {{ t('next_attempt', { value: operation.next_attempt_at ?? t('ready') }) }}</div>
        </div>
      </article>
    </div>
    <div class="history-list" v-if="conflicts.length">
      <article v-for="conflict in conflicts" :key="conflict.client_uuid" class="history-row">
        <div>
          <strong>{{ conflict.client_uuid }}</strong>
          <div class="token-meta">{{ conflict.conflict_reason }}</div>
          <details class="conflict-details">
            <summary>{{ t('compare_payloads') }}</summary>
            <pre>{{ t('client_payload', { payload: formatPayload(conflict.client_payload) }) }}</pre>
            <pre>{{ t('server_payload', { payload: formatPayload(conflict.server_payload ?? conflict.server_result) }) }}</pre>
          </details>
        </div>
        <div class="history-metrics">
          <button class="button button-secondary" type="button" @click="$emit('discard-conflict', conflict.client_uuid)">{{ t('discard') }}</button>
          <button class="button" type="button" @click="$emit('retry-conflict', conflict.client_uuid)">{{ t('retry_as_new') }}</button>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Account, CurrencyOption } from '../../composables/useAccounts'
import type { QueuedOperation, SyncConflict } from '../../stores/offlineQueue'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'

const props = defineProps<{ operations: QueuedOperation[]; conflicts: SyncConflict[]; accounts: Account[]; currencies: CurrencyOption[] }>()
defineEmits<{
  'queue-transaction': []
  'sync-queue': []
  'discard-conflict': [clientUuid: string]
  'retry-conflict': [clientUuid: string]
}>()
const model = defineModel<{ account_id: string; currency_id: string; amount_minor: string; description: string; transaction_date: string }>({ required: true })
const locale = useLocaleStore()
const activeAccounts = computed(() => props.accounts.filter((account) => account.is_active))
const canQueue = computed(() => Boolean(model.value.account_id && model.value.currency_id && Number(model.value.amount_minor) > 0))
const syncStatus = computed(() => {
  if (!activeAccounts.value.length) return t('create_account_first_hint')
  if (!model.value.account_id) return t('select_all_payment_sources')
  if (!model.value.currency_id) return t('select_currency_first')
  if (Number(model.value.amount_minor) <= 0) return t('enter_total_first')
  return t('offline_ready_to_queue')
})

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

function formatPayload(payload: unknown) {
  if (!payload) return t('no_payload_returned')
  return JSON.stringify(payload, null, 2)
}

function currencyCode(id: number) {
  return props.currencies.find((currency) => currency.id === id)?.code ?? ''
}

function syncCurrencyFromAccount() {
  const account = props.accounts.find((item) => String(item.id) === model.value.account_id)
  if (account) model.value.currency_id = String(account.currency_id)
}
</script>
