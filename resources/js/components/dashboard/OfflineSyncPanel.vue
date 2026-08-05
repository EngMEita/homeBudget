<template>
  <section class="panel">
    <h2>Offline sync</h2>
    <div class="filters-grid">
      <label class="field"><span>Account ID</span><input v-model="model.account_id" type="number" min="1" /></label>
      <label class="field"><span>Currency ID</span><input v-model="model.currency_id" type="number" min="1" /></label>
      <label class="field"><span>Amount</span><input v-model="model.amount_minor" type="number" min="1" /></label>
      <label class="field"><span>Description</span><input v-model="model.description" type="text" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('queue-transaction')">Queue offline expense</button>
      <button class="button button-secondary" type="button" @click="$emit('sync-queue')">Sync queue</button>
    </div>
    <p class="lead">Pending operations: {{ operations.length }}</p>
    <div class="history-list" v-if="operations.length">
      <article v-for="operation in operations" :key="operation.client_uuid" class="history-row">
        <div>
          <strong>{{ operation.operation_type }}</strong>
          <div class="token-meta">attempts {{ operation.attempts ?? 0 }} · next {{ operation.next_attempt_at ?? 'ready' }}</div>
        </div>
      </article>
    </div>
    <div class="history-list" v-if="conflicts.length">
      <article v-for="conflict in conflicts" :key="conflict.client_uuid" class="history-row">
        <div>
          <strong>{{ conflict.client_uuid }}</strong>
          <div class="token-meta">{{ conflict.conflict_reason }}</div>
          <details class="conflict-details">
            <summary>Compare client and server payloads</summary>
            <pre>Client: {{ formatPayload(conflict.client_payload) }}</pre>
            <pre>Server: {{ formatPayload(conflict.server_payload ?? conflict.server_result) }}</pre>
          </details>
        </div>
        <div class="history-metrics">
          <button class="button button-secondary" type="button" @click="$emit('discard-conflict', conflict.client_uuid)">Discard</button>
          <button class="button" type="button" @click="$emit('retry-conflict', conflict.client_uuid)">Retry as new</button>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { QueuedOperation, SyncConflict } from '../../stores/offlineQueue'

defineProps<{ operations: QueuedOperation[]; conflicts: SyncConflict[] }>()
defineEmits<{
  'queue-transaction': []
  'sync-queue': []
  'discard-conflict': [clientUuid: string]
  'retry-conflict': [clientUuid: string]
}>()
const model = defineModel<{ account_id: string; currency_id: string; amount_minor: string; description: string; transaction_date: string }>({ required: true })

function formatPayload(payload: unknown) {
  if (!payload) return 'No payload returned'
  return JSON.stringify(payload, null, 2)
}
</script>
