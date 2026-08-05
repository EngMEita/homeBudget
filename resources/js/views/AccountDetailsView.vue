<template>
  <section class="panel">
    <div class="history-header">
      <div>
        <h2>{{ t('account_details') }}</h2>
        <p class="lead">{{ account?.name ?? t('select_household') }}</p>
      </div>
      <button class="button button-secondary" type="button" @click="refresh">{{ t('refresh') }}</button>
    </div>

    <div class="stats-grid" v-if="account">
      <div class="stat-card"><span class="stat-label">{{ t('account_type') }}</span><strong>{{ account.account_type_name }}</strong></div>
      <div class="stat-card"><span class="stat-label">{{ t('currency') }}</span><strong>{{ account.currency_code }}</strong></div>
      <div class="stat-card"><span class="stat-label">{{ t('opening_balance') }}</span><strong>{{ account.opening_balance_minor }}</strong></div>
    </div>
  </section>

  <section class="panel" v-if="account">
    <h2>{{ t('reconcile_account') }}</h2>
    <div class="filters-grid">
      <label class="field"><span>{{ t('statement_balance') }}</span><input v-model="form.statement_balance_minor" type="number" /></label>
      <label class="field"><span>{{ t('date') }}</span><input v-model="form.reconciled_on" type="date" /></label>
      <label class="field"><span>{{ t('description') }}</span><input v-model="form.notes" type="text" /></label>
    </div>
    <button class="button" type="button" @click="reconcile">{{ t('reconcile_account') }}</button>
  </section>

  <section class="panel" v-if="account">
    <h2>{{ t('reconciliations') }}</h2>
    <div class="history-list" v-if="reconciliations.length">
      <article v-for="reconciliation in reconciliations" :key="reconciliation.id" class="history-row">
        <div>
          <strong>{{ reconciliation.reconciled_on }}</strong>
          <div class="token-meta">{{ reconciliation.notes }}</div>
        </div>
        <div class="history-metrics">
          <span>{{ t('statement_balance') }}: {{ reconciliation.statement_balance_minor }}</span>
          <span>{{ t('amount') }}: {{ reconciliation.difference_minor }}</span>
        </div>
      </article>
    </div>
    <p v-else class="lead">{{ t('not_available') }}</p>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import type { Account } from '../composables/useAccounts'
import { useAccounts } from '../composables/useAccounts'
import { translate } from '../i18n'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'
import { useLocaleStore } from '../stores/locale'

type Reconciliation = {
  id: number
  statement_balance_minor: number
  difference_minor: number
  reconciled_on: string
  notes: string | null
}

const route = useRoute()
const auth = useAuthStore()
const householdsStore = useHouseholdStore()
const locale = useLocaleStore()
const { accounts, loadAccounts } = useAccounts()
const reconciliations = ref<Reconciliation[]>([])
const form = reactive({
  statement_balance_minor: '',
  reconciled_on: new Date().toISOString().slice(0, 10),
  notes: ''
})

const accountId = computed(() => Number(route.params.id))
const account = computed<Account | null>(() => accounts.value.find((item) => item.id === accountId.value) ?? null)

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

async function loadReconciliations() {
  if (!auth.token || !householdsStore.activeHouseholdId || !accountId.value) return
  const response = await fetch(`/api/households/${householdsStore.activeHouseholdId}/accounts/${accountId.value}/reconciliations`, { headers: auth.authHeaders() })
  if (!response.ok) return
  const payload = await response.json()
  reconciliations.value = payload.data ?? []
}

async function refresh() {
  await loadAccounts()
  await loadReconciliations()
}

async function reconcile() {
  if (!auth.token || !householdsStore.activeHouseholdId || !account.value || !form.statement_balance_minor) return
  const response = await fetch(`/api/households/${householdsStore.activeHouseholdId}/account-reconciliations`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify({
      account_id: account.value.id,
      statement_balance_minor: Number(form.statement_balance_minor),
      reconciled_on: form.reconciled_on,
      notes: form.notes
    })
  })
  if (!response.ok) return
  form.statement_balance_minor = ''
  form.notes = ''
  await refresh()
}

onMounted(refresh)
watch(() => householdsStore.activeHouseholdId, refresh)
</script>
