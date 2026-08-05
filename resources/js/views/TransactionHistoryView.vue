<template>
  <section class="panel">
    <div class="history-header">
      <div>
        <h2>{{ t('transaction_history') }}</h2>
        <p class="lead">{{ t('filter_transactions_hint') }}</p>
      </div>
      <div class="actions-row">
        <label class="field compact">
          <span>{{ t('language') }}</span>
          <select v-model="locale.locale" @change="locale.setLocale(locale.locale)">
            <option value="en">{{ t('english') }}</option>
            <option value="ar">{{ t('arabic') }}</option>
          </select>
        </label>
        <button class="button button-secondary" type="button" @click="refresh">{{ t('refresh') }}</button>
        <button class="button button-secondary" type="button" @click="downloadCsv" :disabled="!household">{{ t('download_csv') }}</button>
      </div>
    </div>

    <div class="filters-grid">
      <label class="field">
        <span>{{ t('from') }}</span>
        <input v-model="filters.date_from" type="date" />
      </label>
      <label class="field">
        <span>{{ t('to') }}</span>
        <input v-model="filters.date_to" type="date" />
      </label>
      <label class="field">
        <span>{{ t('type') }}</span>
        <select v-model="filters.type">
          <option value="">{{ t('all_types') }}</option>
          <option value="expense">{{ t('expense') }}</option>
          <option value="income">{{ t('income') }}</option>
          <option value="transfer">{{ t('transfer') }}</option>
          <option value="refund">{{ t('refund') }}</option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('currency') }}</span>
        <select v-model="filters.currency_id">
          <option value="">{{ t('all_currencies') }}</option>
          <option v-for="currency in currencies" :key="currency.id" :value="String(currency.id)">
            {{ currency.code }} - {{ currency.name }}
          </option>
        </select>
      </label>
    </div>

    <div class="actions-row">
      <button class="button" type="button" @click="applyFilters">{{ t('apply_filters') }}</button>
      <button class="button button-secondary" type="button" @click="resetFilters">{{ t('reset') }}</button>
    </div>
  </section>

  <section class="panel" v-if="household">
    <h2>{{ t('active_household') }}</h2>
    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-label">{{ t('household') }}</span>
        <strong>{{ household.name }}</strong>
      </div>
      <div class="stat-card">
        <span class="stat-label">{{ t('base_currency') }}</span>
        <strong>{{ household.base_currency_code }}</strong>
      </div>
      <div class="stat-card">
        <span class="stat-label">{{ t('results') }}</span>
        <strong>{{ meta.total ?? transactions.length }}</strong>
      </div>
    </div>
  </section>

  <section class="panel" v-if="transactions.length">
    <div class="history-list">
      <article v-for="transaction in transactions" :key="transaction.id" class="history-row">
        <div>
          <strong>{{ transaction.description ?? transaction.type }}</strong>
          <div class="token-meta">
            {{ transaction.transaction_date }} · {{ transaction.type }} · {{ transaction.status }}
          </div>
          <div class="token-meta" v-if="transaction.exchange_rate_source || transaction.exchange_rate_date">
            {{ transaction.exchange_rate_source ?? t('manual') }} · {{ transaction.exchange_rate_date ?? t('not_available') }}
          </div>
        </div>
        <div class="history-metrics">
          <span>{{ t('minor_units', { amount: transaction.amount_minor }) }}</span>
          <span v-if="transaction.transfer_fee_minor">{{ t('fee_minor', { amount: transaction.transfer_fee_minor }) }}</span>
          <span v-if="transaction.exchange_rate">{{ t('rate_value', { value: transaction.exchange_rate }) }}</span>
          <span v-if="transaction.base_amount_minor">{{ t('base_minor', { amount: transaction.base_amount_minor }) }}</span>
        </div>
      </article>
    </div>

    <div class="pagination-row" v-if="meta.last_page > 1">
      <button class="button button-secondary" type="button" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">
        {{ t('previous') }}
      </button>
      <span class="token-meta">{{ t('page_of', { current: meta.current_page, total: meta.last_page }) }}</span>
      <button class="button button-secondary" type="button" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">
        {{ t('next') }}
      </button>
    </div>
  </section>

  <section class="panel" v-else>
    <p class="lead">{{ t('no_transactions') }}</p>
  </section>

  <SecurityView />
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import SecurityView from './SecurityView.vue'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'
import { useLocaleStore } from '../stores/locale'
import { translate } from '../i18n'

type Household = {
  id: number
  name: string
  base_currency_code: string
  accounts: Array<{ id: number; currency_id: number; currency_code: string | null; name: string }>
}

type Transaction = {
  id: number
  description: string | null
  type: string
  status: string
  amount_minor: number
  base_amount_minor: number | null
  transfer_fee_minor: number | null
  exchange_rate: number | null
  exchange_rate_source: string | null
  exchange_rate_date: string | null
  transaction_date: string | null
}

type Meta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const auth = useAuthStore()
const householdsStore = useHouseholdStore()
const locale = useLocaleStore()
const household = ref<Household | null>(null)
const transactions = ref<Transaction[]>([])
const meta = ref<Meta>({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const filters = reactive({
  date_from: '',
  date_to: '',
  type: '',
  currency_id: '',
  page: 1
})

const currencies = computed(() => {
  const map = new Map<number, { id: number; code: string; name: string }>()
  household.value?.accounts.forEach((account) => {
    if (!map.has(account.currency_id)) {
      map.set(account.currency_id, {
        id: account.currency_id,
        code: account.currency_code ?? `Currency ${account.currency_id}`,
        name: account.name
      })
    }
  })
  return [...map.values()]
})

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

function queryParams() {
  const query = new URLSearchParams()
  query.set('per_page', '10')
  query.set('page', String(filters.page))
  if (filters.date_from) query.set('date_from', filters.date_from)
  if (filters.date_to) query.set('date_to', filters.date_to)
  if (filters.type) query.set('type', filters.type)
  if (filters.currency_id) query.set('currency_id', filters.currency_id)
  return query
}

async function loadHousehold() {
  if (!householdsStore.activeHouseholdId) return false

  const response = await fetch('/api/households', { headers: auth.authHeaders() })
  if (!response.ok) return false

  const payload = await response.json()
  household.value = (payload.data ?? []).find((item: Household) => item.id === householdsStore.activeHouseholdId) ?? null
  return Boolean(household.value)
}

async function loadTransactions() {
  if (!household.value) return

  const response = await fetch(`/api/households/${household.value.id}/transactions?${queryParams().toString()}`, {
    headers: auth.authHeaders()
  })

  if (!response.ok) return

  const payload = await response.json()
  transactions.value = payload.data ?? []
  meta.value = payload.meta
    ? {
        current_page: payload.meta.current_page ?? 1,
        last_page: payload.meta.last_page ?? 1,
        per_page: payload.meta.per_page ?? 10,
        total: payload.meta.total ?? transactions.value.length
      }
    : { current_page: 1, last_page: 1, per_page: 10, total: transactions.value.length }
}

async function refresh() {
  if (!auth.token) return
  const hasHousehold = await loadHousehold()
  if (!hasHousehold) return
  await loadTransactions()
}

async function applyFilters() {
  filters.page = 1
  await loadTransactions()
}

async function goToPage(page: number) {
  filters.page = page
  await loadTransactions()
}

function resetFilters() {
  filters.date_from = ''
  filters.date_to = ''
  filters.type = ''
  filters.currency_id = ''
  filters.page = 1
  void loadTransactions()
}

async function downloadCsv() {
  if (!household.value) return

  const response = await fetch(`/api/households/${household.value.id}/transactions/export?${queryParams().toString()}`, {
    headers: auth.authHeaders()
  })

  if (!response.ok) return

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `household-${household.value.id}-transactions.csv`
  link.click()
  URL.revokeObjectURL(url)
}

onMounted(refresh)

watch(
  () => householdsStore.activeHouseholdId,
  async () => {
    if (!auth.token) return
    const hasHousehold = await loadHousehold()
    if (!hasHousehold) return
    await loadTransactions()
  }
)
</script>
