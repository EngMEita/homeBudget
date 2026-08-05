<template>
  <section class="panel">
    <h2>Budgets</h2>
    <div class="filters-grid">
      <label class="field"><span>Budget name</span><input v-model="model.name" type="text" placeholder="Monthly budget" /></label>
      <label class="field"><span>Start</span><input v-model="model.starts_on" type="date" /></label>
      <label class="field"><span>End</span><input v-model="model.ends_on" type="date" /></label>
      <label class="field"><span>Category</span><input v-model="model.category_id" type="number" min="1" /></label>
      <label class="field"><span>Planned amount</span><input v-model="model.planned_minor_amount" type="number" min="1" /></label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-budget')">Create budget</button>
      <button class="button button-secondary" type="button" @click="$emit('refresh-budget')">Refresh budget</button>
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
          <strong>{{ period.starts_on }} to {{ period.ends_on }}</strong>
          <div class="token-meta">{{ period.status }}</div>
        </div>
        <div class="history-metrics">
          <span v-for="line in period.lines" :key="line.category_id">
            {{ line.category_name }}: {{ line.actual_minor_amount }}/{{ line.planned_minor_amount }}
          </span>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { BudgetSummary } from '../../types/dashboard'

defineProps<{ summary: BudgetSummary }>()
defineEmits<{ 'create-budget': []; 'refresh-budget': [] }>()
const model = defineModel<{ name: string; starts_on: string; ends_on: string; category_id: string; planned_minor_amount: string }>({ required: true })
</script>
