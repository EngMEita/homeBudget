<template>
  <section class="panel">
    <h2>Recurring, bills, goals, and debts</h2>
    <div class="filters-grid">
      <label class="field"><span>Name</span><input v-model="model.name" type="text" placeholder="Internet bill" /></label>
      <label class="field"><span>Account ID</span><input v-model="model.account_id" type="number" min="1" /></label>
      <label class="field"><span>Currency ID</span><input v-model="model.currency_id" type="number" min="1" /></label>
      <label class="field"><span>Amount</span><input v-model="model.amount_minor" type="number" min="1" /></label>
      <label class="field"><span>Date</span><input v-model="model.date" type="date" /></label>
      <label class="field"><span>Counterparty</span><input v-model="model.counterparty_name" type="text" placeholder="Relative or bank" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-recurring-rule')">Create recurring rule</button>
      <button class="button button-secondary" type="button" @click="$emit('create-upcoming-bill')">Create bill</button>
      <button class="button button-secondary" type="button" @click="$emit('create-savings-goal')">Create goal</button>
      <button class="button button-secondary" type="button" @click="$emit('create-debt')">Create debt</button>
      <button class="button" type="button" @click="$emit('refresh-planning')">Refresh planning</button>
    </div>
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-label">Recurring rules</div><strong>{{ recurringRules.length }}</strong></div>
      <div class="stat-card"><div class="stat-label">Upcoming bills</div><strong>{{ upcomingBills.length }}</strong></div>
      <div class="stat-card"><div class="stat-label">Savings goals</div><strong>{{ savingsGoals.length }}</strong></div>
      <div class="stat-card"><div class="stat-label">Debts</div><strong>{{ debts.length }}</strong></div>
    </div>
    <div class="history-list" v-if="savingsGoals.length || debts.length">
      <article v-for="goal in savingsGoals" :key="`goal-${goal.id}`" class="history-row">
        <div>
          <strong>{{ goal.name }}</strong>
          <div class="token-meta">{{ goal.current_minor_amount }}/{{ goal.target_minor_amount }} · {{ goal.status }}</div>
        </div>
        <button class="button button-secondary" type="button" @click="$emit('contribute-to-goal', goal.id)">Contribute</button>
      </article>
      <article v-for="debt in debts" :key="`debt-${debt.id}`" class="history-row">
        <div>
          <strong>{{ debt.name }}</strong>
          <div class="token-meta">{{ debt.counterparty_name }} · remaining {{ debt.remaining_minor_amount }}</div>
        </div>
        <button class="button button-secondary" type="button" @click="$emit('pay-debt-installment', debt.id)">Pay installment</button>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Debt, RecurringRule, SavingsGoal, UpcomingBill } from '../../types/dashboard'

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
</script>
