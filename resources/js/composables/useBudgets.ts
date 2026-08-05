import { reactive, ref, type Ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import type { BudgetSummary, Household } from '../types/dashboard'

export function useBudgets(activeHousehold: Ref<Household | null>) {
  const auth = useAuthStore()
  const budgetSummary = ref<BudgetSummary>({ budget: null, periods: [] })
  const budgetForm = reactive({
    name: 'Monthly budget',
    starts_on: new Date().toISOString().slice(0, 10),
    ends_on: new Date().toISOString().slice(0, 10),
    category_id: '',
    planned_minor_amount: ''
  })

  async function loadBudgetSummary() {
    if (!activeHousehold.value) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/budgets`, { headers: auth.authHeaders() })
    if (!response.ok) return
    budgetSummary.value = await response.json()
  }

  async function createBudget() {
    if (!activeHousehold.value || !budgetForm.category_id || !budgetForm.planned_minor_amount) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/budgets`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({
        name: budgetForm.name,
        starts_on: budgetForm.starts_on,
        ends_on: budgetForm.ends_on,
        lines: [{ category_id: Number(budgetForm.category_id), planned_minor_amount: Number(budgetForm.planned_minor_amount) }]
      })
    })
    if (!response.ok) return
    await loadBudgetSummary()
  }

  return { budgetSummary, budgetForm, loadBudgetSummary, createBudget }
}
