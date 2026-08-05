<template>
  <section class="panel">
    <h2>Receipts</h2>
    <div class="filters-grid">
      <label class="field"><span>Account ID</span><input v-model="model.account_id" type="number" min="1" /></label>
      <label class="field"><span>Currency ID</span><input v-model="model.currency_id" type="number" min="1" /></label>
      <label class="field"><span>Total</span><input v-model="model.total_minor_amount" type="number" min="1" /></label>
      <label class="field"><span>Date</span><input v-model="model.transaction_date" type="date" /></label>
      <label class="field"><span>Category ID</span><input v-model="model.category_id" type="number" min="1" /></label>
      <label class="field"><span>Allocation</span><input v-model="model.allocation_minor_amount" type="number" min="1" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-receipt')">Create receipt</button>
      <button class="button button-secondary" type="button" @click="$emit('queue-offline-receipt')">Queue offline receipt</button>
      <button class="button button-secondary" type="button" @click="$emit('categorize-receipt')">Save categorization</button>
    </div>
    <div class="filters-grid" v-if="activeReceipt">
      <label class="field">
        <span>Receipt attachment</span>
        <input type="file" accept="image/*,.pdf" @change="$emit('select-attachment', $event)" />
      </label>
    </div>
    <div class="actions-row" v-if="activeReceipt">
      <button class="button button-secondary" type="button" @click="$emit('upload-attachment')">Upload attachment</button>
      <button class="button button-secondary" type="button" @click="$emit('queue-attachment-offline')">Queue attachment offline</button>
      <button class="button" type="button" @click="$emit('complete-receipt')">Complete receipt</button>
    </div>
    <article class="history-row" v-if="activeReceipt">
      <div>
        <strong>Receipt #{{ activeReceipt.id }}</strong>
        <div class="token-meta">{{ activeReceipt.categorization_status }}</div>
      </div>
      <div class="history-metrics">
        <span>categorized {{ activeReceipt.categorized_minor_amount }}</span>
        <span>remaining {{ activeReceipt.remaining_uncategorized_minor_amount }}</span>
        <span>attachments {{ activeReceipt.attachments.length }}</span>
      </div>
    </article>
  </section>
</template>

<script setup lang="ts">
import type { Receipt } from '../../types/dashboard'

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
</script>
