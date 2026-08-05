<template>
  <section class="panel">
    <h2>{{ t('offline_sync') }}</h2>
    <div class="filters-grid">
      <label class="field"><span>{{ t('account_id') }}</span><input v-model="model.account_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('currency_id') }}</span><input v-model="model.currency_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('amount') }}</span><input v-model="model.amount_minor" type="number" min="0.01" step="0.01" placeholder="1500.25" /></label>
      <label class="field"><span>{{ t('description') }}</span><input v-model="model.description" type="text" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('queue-transaction')">{{ t('queue_offline_expense') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('sync-queue')">{{ t('sync_queue') }}</button>
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
import type { QueuedOperation, SyncConflict } from '../../stores/offlineQueue'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'

defineProps<{ operations: QueuedOperation[]; conflicts: SyncConflict[] }>()
defineEmits<{
  'queue-transaction': []
  'sync-queue': []
  'discard-conflict': [clientUuid: string]
  'retry-conflict': [clientUuid: string]
}>()
const model = defineModel<{ account_id: string; currency_id: string; amount_minor: string; description: string; transaction_date: string }>({ required: true })
const locale = useLocaleStore()

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

function formatPayload(payload: unknown) {
  if (!payload) return t('no_payload_returned')
  return JSON.stringify(payload, null, 2)
}
</script>
