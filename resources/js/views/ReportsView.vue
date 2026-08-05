<template>
  <section class="panel">
    <div class="history-header">
      <div>
        <h2>{{ t('reports') }}</h2>
        <p class="lead">{{ report?.household_name ?? t('select_household') }}</p>
      </div>
      <button class="button button-secondary" type="button" @click="loadReport">{{ t('refresh') }}</button>
    </div>

    <div class="stats-grid" v-if="report">
      <div class="stat-card"><span class="stat-label">{{ t('accounts') }}</span><strong>{{ report.total_accounts }}</strong></div>
      <div class="stat-card"><span class="stat-label">{{ t('transaction_history') }}</span><strong>{{ report.total_transactions }}</strong></div>
      <div class="stat-card"><span class="stat-label">{{ t('recurring_rules') }}</span><strong>{{ report.total_recurring_rules }}</strong></div>
      <div class="stat-card"><span class="stat-label">{{ t('upcoming_bills') }}</span><strong>{{ report.total_upcoming_bills }}</strong></div>
      <div class="stat-card"><span class="stat-label">{{ t('savings_goals') }}</span><strong>{{ report.total_savings_goals }}</strong></div>
      <div class="stat-card"><span class="stat-label">{{ t('debts') }}</span><strong>{{ report.total_debts }}</strong></div>
    </div>
  </section>

  <section class="panel" v-if="report">
    <h2>{{ t('recent_transactions') }}</h2>
    <div class="history-list" v-if="report.recent_transactions.length">
      <article v-for="transaction in report.recent_transactions" :key="transaction.id" class="history-row">
        <div>
          <strong>{{ transaction.description ?? transaction.type }}</strong>
          <div class="token-meta">{{ transaction.transaction_date }} · {{ transaction.type }} · {{ transaction.status }}</div>
        </div>
        <div class="history-metrics">
          <span>{{ minorToDecimal(transaction.amount_minor) }}</span>
          <span>{{ report.base_currency_code }}</span>
        </div>
      </article>
    </div>
    <p v-else class="lead">{{ t('no_transactions') }}</p>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { translate } from '../i18n'
import { minorToDecimal } from '../money'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'
import { useLocaleStore } from '../stores/locale'

type Report = {
  household_name: string
  base_currency_code: string
  total_accounts: number
  total_transactions: number
  total_recurring_rules: number
  total_upcoming_bills: number
  total_savings_goals: number
  total_debts: number
  recent_transactions: Array<{ id: number; description: string | null; type: string; status: string; amount_minor: number; transaction_date: string | null }>
}

const auth = useAuthStore()
const householdsStore = useHouseholdStore()
const locale = useLocaleStore()
const report = ref<Report | null>(null)

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

async function loadReport() {
  if (!auth.token || !householdsStore.activeHouseholdId) return
  const response = await fetch(`/api/households/${householdsStore.activeHouseholdId}/reports`, { headers: auth.authHeaders() })
  if (!response.ok) return
  const payload = await response.json()
  report.value = payload.data ?? null
}

onMounted(loadReport)
watch(() => householdsStore.activeHouseholdId, loadReport)
</script>
