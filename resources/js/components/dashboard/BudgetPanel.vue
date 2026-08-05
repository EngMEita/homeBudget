<template>
  <section class="panel">
    <h2>{{ t('budgets') }}</h2>
    <div class="filters-grid">
      <label class="field"><span>{{ t('budget_name') }}</span><input v-model="model.name" type="text" :placeholder="t('monthly_budget_placeholder')" /></label>
      <label class="field"><span>{{ t('start') }}</span><input v-model="model.starts_on" type="date" /></label>
      <label class="field"><span>{{ t('end') }}</span><input v-model="model.ends_on" type="date" /></label>
      <label class="field"><span>{{ t('category') }}</span><input v-model="model.category_id" type="number" min="1" /></label>
      <label class="field"><span>{{ t('planned_amount') }}</span><input v-model="model.planned_minor_amount" type="number" min="0.01" step="0.01" placeholder="1500.25" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-budget')">{{ t('create_budget') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('refresh-budget')">{{ t('refresh_budget') }}</button>
    </div>
    <div class="history-list" v-if="summary.budget">
      <article class="history-row">
        <div>
          <strong>{{ summary.budget.name }}</strong>
          <div class="token-meta">{{ summary.budget.period_type }} · {{ summary.budget.base_currency_code }}</div>
        </div>
      </article>
      <article v-for="period in summary.periods" :key="period.id" class="history-row">
        <div>
          <strong>{{ period.starts_on }} {{ t('to') }} {{ period.ends_on }}</strong>
          <div class="token-meta">{{ period.status }}</div>
        </div>
        <div class="history-metrics">
          <span v-for="line in period.lines" :key="line.category_id">
            {{ line.category_name }}: {{ minorToDecimal(line.actual_minor_amount) }}/{{ minorToDecimal(line.planned_minor_amount) }}
          </span>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { BudgetSummary } from '../../types/dashboard'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'
import { minorToDecimal } from '../../money'

defineProps<{ summary: BudgetSummary }>()
defineEmits<{ 'create-budget': []; 'refresh-budget': [] }>()
const model = defineModel<{ name: string; starts_on: string; ends_on: string; category_id: string; planned_minor_amount: string }>({ required: true })
const locale = useLocaleStore()

function t(key: string) {
  return translate(locale.locale, key)
}
</script>
