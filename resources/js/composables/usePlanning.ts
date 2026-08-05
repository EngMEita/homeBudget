import { reactive, type Ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import type { Debt, Household, RecurringRule, SavingsGoal, UpcomingBill } from '../types/dashboard'

export function usePlanning(activeHousehold: Ref<Household | null>, afterWrite?: () => Promise<void>) {
  const auth = useAuthStore()
  const planning = reactive({
    recurringRules: [] as RecurringRule[],
    upcomingBills: [] as UpcomingBill[],
    savingsGoals: [] as SavingsGoal[],
    debts: [] as Debt[]
  })
  const planningForm = reactive({
    name: 'Internet bill',
    account_id: '',
    currency_id: '',
    amount_minor: '',
    date: new Date().toISOString().slice(0, 10),
    counterparty_name: ''
  })

  async function loadPlanningData() {
    if (!activeHousehold.value) return
    const headers = auth.authHeaders()
    const [rulesResponse, billsResponse, goalsResponse, debtsResponse] = await Promise.all([
      fetch(`/api/households/${activeHousehold.value.id}/recurring-rules`, { headers }),
      fetch(`/api/households/${activeHousehold.value.id}/upcoming-bills`, { headers }),
      fetch(`/api/households/${activeHousehold.value.id}/savings-goals`, { headers }),
      fetch(`/api/households/${activeHousehold.value.id}/debts`, { headers })
    ])
    if (rulesResponse.ok) planning.recurringRules = (await rulesResponse.json()).data ?? []
    if (billsResponse.ok) planning.upcomingBills = (await billsResponse.json()).data ?? []
    if (goalsResponse.ok) planning.savingsGoals = (await goalsResponse.json()).data ?? []
    if (debtsResponse.ok) planning.debts = (await debtsResponse.json()).data ?? []
  }

  async function refreshAfterWrite() {
    await loadPlanningData()
    await afterWrite?.()
  }

  async function createRecurringRule() {
    if (!activeHousehold.value || !planningForm.account_id || !planningForm.currency_id || !planningForm.amount_minor) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/recurring-rules`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({
        account_id: Number(planningForm.account_id),
        currency_id: Number(planningForm.currency_id),
        name: planningForm.name,
        type: 'expense',
        frequency: 'monthly',
        amount_minor: Number(planningForm.amount_minor),
        starts_on: planningForm.date,
        next_run_on: planningForm.date
      })
    })
    if (!response.ok) return
    await refreshAfterWrite()
  }

  async function createUpcomingBill() {
    if (!activeHousehold.value || !planningForm.currency_id || !planningForm.amount_minor) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/upcoming-bills`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({
        account_id: planningForm.account_id ? Number(planningForm.account_id) : null,
        currency_id: Number(planningForm.currency_id),
        name: planningForm.name,
        amount_minor: Number(planningForm.amount_minor),
        due_on: planningForm.date
      })
    })
    if (!response.ok) return
    await refreshAfterWrite()
  }

  async function createSavingsGoal() {
    if (!activeHousehold.value || !planningForm.currency_id || !planningForm.amount_minor) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/savings-goals`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({
        currency_id: Number(planningForm.currency_id),
        name: planningForm.name,
        target_minor_amount: Number(planningForm.amount_minor),
        target_date: planningForm.date
      })
    })
    if (!response.ok) return
    await refreshAfterWrite()
  }

  async function contributeToGoal(goalId: number) {
    if (!activeHousehold.value || !planningForm.amount_minor) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/savings-goals/${goalId}/contributions`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({ amount_minor: Number(planningForm.amount_minor), contributed_on: planningForm.date })
    })
    if (!response.ok) return
    await refreshAfterWrite()
  }

  async function createDebt() {
    if (!activeHousehold.value || !planningForm.currency_id || !planningForm.amount_minor) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/debts`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({
        currency_id: Number(planningForm.currency_id),
        name: planningForm.name,
        counterparty_name: planningForm.counterparty_name || 'Counterparty',
        principal_minor_amount: Number(planningForm.amount_minor),
        opened_on: planningForm.date
      })
    })
    if (!response.ok) return
    await refreshAfterWrite()
  }

  async function payDebtInstallment(debtId: number) {
    if (!activeHousehold.value || !planningForm.amount_minor) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/debts/${debtId}/installments`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({ principal_minor_amount: Number(planningForm.amount_minor), interest_minor_amount: 0, paid_on: planningForm.date })
    })
    if (!response.ok) return
    await refreshAfterWrite()
  }

  return {
    planning,
    planningForm,
    loadPlanningData,
    createRecurringRule,
    createUpcomingBill,
    createSavingsGoal,
    contributeToGoal,
    createDebt,
    payDebtInstallment
  }
}
