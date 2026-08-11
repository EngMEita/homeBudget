<template>
  <section class="panel">
    <div class="panel-heading">
      <div>
        <p class="eyebrow">{{ t('workflow_planning') }}</p>
        <h2>{{ t('recurring_bills_goals_debts') }}</h2>
        <p class="lead">{{ t('planning_flow_hint') }}</p>
      </div>
      <span class="status-pill" :class="{ ready: canCreate }">{{ canCreate ? t('ready') : t('needs_input') }}</span>
    </div>

    <div v-if="!hasAccounts" class="empty-state">
      <strong>{{ t('no_payment_sources_ready') }}</strong>
      <p>{{ t('create_account_first_hint') }}</p>
    </div>

    <div v-else class="filters-grid">
      <label class="field"><span>{{ t('name') }}</span><input v-model="model.name" type="text" :placeholder="t('internet_bill_placeholder')" /></label>
      <label class="field">
        <span>{{ t('payment_source') }}</span>
        <select v-model="model.account_id" @change="syncCurrencyFromAccount">
          <option value="">{{ t('not_available') }}</option>
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
      <label class="field"><span>{{ t('date') }}</span><input v-model="model.date" type="date" /></label>
      <label class="field"><span>{{ t('counterparty') }}</span><input v-model="model.counterparty_name" type="text" :placeholder="t('relative_or_bank_placeholder')" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" :disabled="!canCreateWithAccount" @click="$emit('create-recurring-rule')">{{ t('create_recurring_rule') }}</button>
      <button class="button button-secondary" type="button" :disabled="!canCreate" @click="$emit('create-upcoming-bill')">{{ t('create_bill') }}</button>
      <button class="button button-secondary" type="button" :disabled="!canCreate" @click="$emit('create-savings-goal')">{{ t('create_goal') }}</button>
      <button class="button button-secondary" type="button" :disabled="!canCreate" @click="$emit('create-debt')">{{ t('create_debt') }}</button>
      <button class="button" type="button" @click="$emit('refresh-planning')">{{ t('refresh_planning') }}</button>
      <strong>{{ planningStatus }}</strong>
    </div>
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-label">{{ t('recurring_rules') }}</div><strong>{{ recurringRules.length }}</strong></div>
      <div class="stat-card"><div class="stat-label">{{ t('upcoming_bills') }}</div><strong>{{ upcomingBills.length }}</strong></div>
      <div class="stat-card"><div class="stat-label">{{ t('savings_goals') }}</div><strong>{{ savingsGoals.length }}</strong></div>
      <div class="stat-card"><div class="stat-label">{{ t('debts') }}</div><strong>{{ debts.length }}</strong></div>
    </div>
    <div class="history-list" v-if="savingsGoals.length || debts.length">
      <article v-for="goal in savingsGoals" :key="`goal-${goal.id}`" class="history-row">
        <div>
          <strong>{{ goal.name }}</strong>
          <div class="token-meta">{{ minorToDecimal(goal.current_minor_amount) }}/{{ minorToDecimal(goal.target_minor_amount) }} · {{ goal.status }}</div>
        </div>
        <button class="button button-secondary" type="button" @click="$emit('contribute-to-goal', goal.id)">{{ t('contribute') }}</button>
      </article>
      <article v-for="debt in debts" :key="`debt-${debt.id}`" class="history-row">
        <div>
          <strong>{{ debt.name }}</strong>
          <div class="token-meta">{{ debt.counterparty_name }} · {{ t('remaining_amount', { amount: minorToDecimal(debt.remaining_minor_amount) }) }}</div>
        </div>
        <button class="button button-secondary" type="button" @click="$emit('pay-debt-installment', debt.id)">{{ t('pay_installment') }}</button>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Account, CurrencyOption } from '../../composables/useAccounts'
import type { Debt, RecurringRule, SavingsGoal, UpcomingBill } from '../../types/dashboard'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'
import { minorToDecimal } from '../../money'

const props = defineProps<{ recurringRules: RecurringRule[]; upcomingBills: UpcomingBill[]; savingsGoals: SavingsGoal[]; debts: Debt[]; accounts: Account[]; currencies: CurrencyOption[] }>()
defineEmits<{
  'create-recurring-rule': []
  'create-upcoming-bill': []
  'create-savings-goal': []
  'create-debt': []
  'refresh-planning': []
  'contribute-to-goal': [id: number]
  'pay-debt-installment': [id: number]
}>()
const model = defineModel<{ name: string; account_id: string; currency_id: string; amount_minor: string; date: string; counterparty_name: string }>({ required: true })
const locale = useLocaleStore()
const activeAccounts = computed(() => props.accounts.filter((account) => account.is_active))
const hasAccounts = computed(() => activeAccounts.value.length > 0)
const canCreate = computed(() => Boolean(model.value.currency_id && model.value.amount_minor && Number(model.value.amount_minor) > 0))
const canCreateWithAccount = computed(() => canCreate.value && Boolean(model.value.account_id))
const planningStatus = computed(() => {
  if (!hasAccounts.value) return t('create_account_first_hint')
  if (!model.value.currency_id) return t('select_currency_first')
  if (!model.value.amount_minor || Number(model.value.amount_minor) <= 0) return t('enter_total_first')
  return model.value.account_id ? t('planning_ready_with_account') : t('planning_ready_without_account')
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
