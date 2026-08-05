<template>
  <section class="panel">
    <h2>{{ t('recurring_bills_goals_debts') }}</h2>
    <div class="filters-grid">
      <label class="field"><span>{{ t('name') }}</span><input v-model="model.name" type="text" :placeholder="t('internet_bill_placeholder')" /></label>
      <label class="field"><span>{{ t('account_id') }}</span><input v-model="model.account_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('currency_id') }}</span><input v-model="model.currency_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('amount') }}</span><input v-model="model.amount_minor" type="number" min="1" /></label>
      <label class="field"><span>{{ t('date') }}</span><input v-model="model.date" type="date" /></label>
      <label class="field"><span>{{ t('counterparty') }}</span><input v-model="model.counterparty_name" type="text" :placeholder="t('relative_or_bank_placeholder')" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-recurring-rule')">{{ t('create_recurring_rule') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('create-upcoming-bill')">{{ t('create_bill') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('create-savings-goal')">{{ t('create_goal') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('create-debt')">{{ t('create_debt') }}</button>
      <button class="button" type="button" @click="$emit('refresh-planning')">{{ t('refresh_planning') }}</button>
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
          <div class="token-meta">{{ goal.current_minor_amount }}/{{ goal.target_minor_amount }} · {{ goal.status }}</div>
        </div>
        <button class="button button-secondary" type="button" @click="$emit('contribute-to-goal', goal.id)">{{ t('contribute') }}</button>
      </article>
      <article v-for="debt in debts" :key="`debt-${debt.id}`" class="history-row">
        <div>
          <strong>{{ debt.name }}</strong>
          <div class="token-meta">{{ debt.counterparty_name }} · {{ t('remaining_amount', { amount: debt.remaining_minor_amount }) }}</div>
        </div>
        <button class="button button-secondary" type="button" @click="$emit('pay-debt-installment', debt.id)">{{ t('pay_installment') }}</button>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Debt, RecurringRule, SavingsGoal, UpcomingBill } from '../../types/dashboard'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'

defineProps<{ recurringRules: RecurringRule[]; upcomingBills: UpcomingBill[]; savingsGoals: SavingsGoal[]; debts: Debt[] }>()
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

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}
</script>
