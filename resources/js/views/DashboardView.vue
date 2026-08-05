<template>
  <main class="shell">
    <HouseholdPanel
      v-model="newHousehold"
      :households="households"
      :active-household-id="householdsStore.activeHouseholdId"
      @create-household="createHousehold"
      @refresh-households="loadHouseholds"
      @select-household="selectHousehold"
    />

    <MembersPanel
      v-if="activeHousehold"
      v-model="invitation"
      :members="members"
      :invitations="invitations"
      @invite-member="inviteMember"
      @refresh-members="loadMembers"
    />

    <BudgetPanel
      v-if="activeHousehold"
      v-model="budgetForm"
      :summary="budgetSummary"
      @create-budget="createBudget"
      @refresh-budget="loadBudgetSummary"
    />

    <ReceiptPanel
      v-if="activeHousehold"
      v-model="receiptForm"
      :active-receipt="activeReceipt"
      @create-receipt="createReceipt"
      @queue-offline-receipt="enqueueOfflineReceipt"
      @categorize-receipt="categorizeReceipt"
      @select-attachment="selectReceiptAttachment"
      @upload-attachment="uploadReceiptAttachment"
      @queue-attachment-offline="enqueueOfflineReceiptAttachment"
      @complete-receipt="completeReceipt"
    />

    <PlanningPanel
      v-if="activeHousehold"
      v-model="planningForm"
      :recurring-rules="planning.recurringRules"
      :upcoming-bills="planning.upcomingBills"
      :savings-goals="planning.savingsGoals"
      :debts="planning.debts"
      @create-recurring-rule="createRecurringRule"
      @create-upcoming-bill="createUpcomingBill"
      @create-savings-goal="createSavingsGoal"
      @create-debt="createDebt"
      @refresh-planning="loadPlanningData"
      @contribute-to-goal="contributeToGoal"
      @pay-debt-installment="payDebtInstallment"
    />

    <OperationsPanel
      v-if="activeHousehold"
      :backups="operations.backups"
      :audit-logs="operations.auditLogs"
      @create-backup="createBackup"
      @refresh-operations="loadOperationsData"
    />

    <OfflineSyncPanel
      v-if="activeHousehold"
      v-model="offlineForm"
      :operations="offlineQueue.operations"
      :conflicts="offlineQueue.conflicts"
      @queue-transaction="enqueueOfflineTransaction"
      @sync-queue="syncOfflineQueue"
      @discard-conflict="offlineQueue.discard"
      @retry-conflict="retryConflict"
    />
  </main>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import BudgetPanel from '../components/dashboard/BudgetPanel.vue'
import HouseholdPanel from '../components/dashboard/HouseholdPanel.vue'
import MembersPanel from '../components/dashboard/MembersPanel.vue'
import OfflineSyncPanel from '../components/dashboard/OfflineSyncPanel.vue'
import OperationsPanel from '../components/dashboard/OperationsPanel.vue'
import PlanningPanel from '../components/dashboard/PlanningPanel.vue'
import ReceiptPanel from '../components/dashboard/ReceiptPanel.vue'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'
import { buildOfflineAttachmentPayload, useOfflineQueueStore } from '../stores/offlineQueue'
import type {
  AuditLog,
  BackupLog,
  BudgetSummary,
  Debt,
  Household,
  Invitation,
  Member,
  Receipt,
  RecurringRule,
  SavingsGoal,
  UpcomingBill
} from '../types/dashboard'

const auth = useAuthStore()
const householdsStore = useHouseholdStore()
const offlineQueue = useOfflineQueueStore()
const households = ref<Household[]>([])
const members = ref<Member[]>([])
const invitations = ref<Invitation[]>([])
const activeHousehold = ref<Household | null>(null)
const budgetSummary = ref<BudgetSummary>({ budget: null, periods: [] })
const activeReceipt = ref<Receipt | null>(null)
const receiptAttachment = ref<File | null>(null)
const planning = reactive({
  recurringRules: [] as RecurringRule[],
  upcomingBills: [] as UpcomingBill[],
  savingsGoals: [] as SavingsGoal[],
  debts: [] as Debt[]
})
const operations = reactive({
  backups: [] as BackupLog[],
  auditLogs: [] as AuditLog[]
})
const newHousehold = reactive({ name: '', base_currency_code: 'SAR', default_locale: 'en' })
const invitation = reactive({ email: '', role: 'viewer' })
const budgetForm = reactive({
  name: 'Monthly budget',
  starts_on: new Date().toISOString().slice(0, 10),
  ends_on: new Date().toISOString().slice(0, 10),
  category_id: '',
  planned_minor_amount: ''
})
const receiptForm = reactive({
  account_id: '',
  currency_id: '',
  total_minor_amount: '',
  transaction_date: new Date().toISOString().slice(0, 10),
  category_id: '',
  allocation_minor_amount: ''
})
const offlineForm = reactive({
  account_id: '',
  currency_id: '',
  amount_minor: '',
  description: '',
  transaction_date: new Date().toISOString().slice(0, 10)
})
const planningForm = reactive({
  name: 'Internet bill',
  account_id: '',
  currency_id: '',
  amount_minor: '',
  date: new Date().toISOString().slice(0, 10),
  counterparty_name: ''
})

async function loadHouseholds() {
  if (!auth.token) return
  const response = await fetch('/api/households', { headers: auth.authHeaders() })
  if (!response.ok) return
  const payload = await response.json()
  households.value = payload.data ?? []
  if (!householdsStore.activeHouseholdId && households.value.length) {
    householdsStore.setActiveHouseholdId(households.value[0].id)
  }
  activeHousehold.value = households.value.find((household) => household.id === householdsStore.activeHouseholdId) ?? null
  if (activeHousehold.value) {
    await loadMembers()
    await loadPlanningData()
    await loadOperationsData()
  }
}

async function createHousehold() {
  if (!auth.token || !newHousehold.name.trim()) return
  const response = await fetch('/api/households', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify(newHousehold)
  })
  if (!response.ok) return
  const payload = await response.json()
  if (payload.data?.id) householdsStore.setActiveHouseholdId(payload.data.id)
  newHousehold.name = ''
  await loadHouseholds()
}

async function selectHousehold(id: number) {
  if (!id) return
  householdsStore.setActiveHouseholdId(id)
  activeHousehold.value = households.value.find((household) => household.id === id) ?? null
  await loadMembers()
  await loadPlanningData()
  await loadOperationsData()
}

async function loadMembers() {
  if (!activeHousehold.value) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/members`, { headers: auth.authHeaders() })
  if (!response.ok) return
  const payload = await response.json()
  members.value = payload.members ?? []
  invitations.value = payload.invitations ?? []
}

async function inviteMember() {
  if (!activeHousehold.value || !invitation.email.trim()) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/members/invitations`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify(invitation)
  })
  if (!response.ok) return
  invitation.email = ''
  await loadMembers()
}

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

async function loadOperationsData() {
  if (!activeHousehold.value) return
  const headers = auth.authHeaders()
  const [backupsResponse, auditResponse] = await Promise.all([
    fetch(`/api/households/${activeHousehold.value.id}/backups`, { headers }),
    fetch(`/api/households/${activeHousehold.value.id}/audit-logs`, { headers })
  ])
  if (backupsResponse.ok) operations.backups = (await backupsResponse.json()).data ?? []
  if (auditResponse.ok) operations.auditLogs = (await auditResponse.json()).data ?? []
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
  await loadPlanningData()
  await loadOperationsData()
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
  await loadPlanningData()
  await loadOperationsData()
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
  await loadPlanningData()
  await loadOperationsData()
}

async function contributeToGoal(goalId: number) {
  if (!activeHousehold.value || !planningForm.amount_minor) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/savings-goals/${goalId}/contributions`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify({ amount_minor: Number(planningForm.amount_minor), contributed_on: planningForm.date })
  })
  if (!response.ok) return
  await loadPlanningData()
  await loadOperationsData()
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
  await loadPlanningData()
  await loadOperationsData()
}

async function payDebtInstallment(debtId: number) {
  if (!activeHousehold.value || !planningForm.amount_minor) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/debts/${debtId}/installments`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify({ principal_minor_amount: Number(planningForm.amount_minor), interest_minor_amount: 0, paid_on: planningForm.date })
  })
  if (!response.ok) return
  await loadPlanningData()
  await loadOperationsData()
}

async function createBackup() {
  if (!activeHousehold.value) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/backups`, { method: 'POST', headers: auth.authHeaders() })
  if (!response.ok) return
  await loadOperationsData()
}

async function createReceipt() {
  if (!activeHousehold.value || !receiptForm.account_id || !receiptForm.currency_id || !receiptForm.total_minor_amount) return
  const total = Number(receiptForm.total_minor_amount)
  const response = await fetch(`/api/households/${activeHousehold.value.id}/receipts`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify({
      account_id: Number(receiptForm.account_id),
      currency_id: Number(receiptForm.currency_id),
      paid_by_user_id: members.value[0]?.user_id,
      total_minor_amount: total,
      base_currency_minor_amount: total,
      transaction_date: receiptForm.transaction_date
    })
  })
  if (!response.ok) return
  activeReceipt.value = (await response.json()).data ?? null
}

async function categorizeReceipt() {
  if (!activeHousehold.value || !activeReceipt.value || !receiptForm.category_id || !receiptForm.allocation_minor_amount) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/receipts/${activeReceipt.value.id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify({
      account_id: activeReceipt.value.account_id,
      currency_id: activeReceipt.value.currency_id,
      paid_by_user_id: members.value[0]?.user_id,
      total_minor_amount: activeReceipt.value.total_minor_amount,
      base_currency_minor_amount: activeReceipt.value.total_minor_amount,
      transaction_date: receiptForm.transaction_date,
      allocations: [{ category_id: Number(receiptForm.category_id), amount_minor: Number(receiptForm.allocation_minor_amount) }]
    })
  })
  if (!response.ok) return
  activeReceipt.value = (await response.json()).data ?? null
}

function selectReceiptAttachment(event: Event) {
  const input = event.target as HTMLInputElement
  receiptAttachment.value = input.files?.[0] ?? null
}

async function uploadReceiptAttachment() {
  if (!activeHousehold.value || !activeReceipt.value || !receiptAttachment.value) return
  const form = new FormData()
  form.append('attachment', receiptAttachment.value)
  const response = await fetch(`/api/households/${activeHousehold.value.id}/receipts/${activeReceipt.value.id}/attachments`, {
    method: 'POST',
    headers: auth.authHeaders(),
    body: form
  })
  if (!response.ok) return
  await categorizeReceipt()
}

async function enqueueOfflineReceipt() {
  if (!receiptForm.account_id || !receiptForm.currency_id || !receiptForm.total_minor_amount) return
  const total = Number(receiptForm.total_minor_amount)
  const clientUuid = crypto.randomUUID()
  await offlineQueue.enqueue({
    client_uuid: clientUuid,
    operation_type: 'receipt.create',
    payload: {
      account_id: Number(receiptForm.account_id),
      currency_id: Number(receiptForm.currency_id),
      paid_by_user_id: members.value[0]?.user_id,
      total_minor_amount: total,
      base_currency_minor_amount: total,
      transaction_date: receiptForm.transaction_date
    }
  })
  activeReceipt.value = {
    id: 0,
    client_uuid: clientUuid,
    account_id: Number(receiptForm.account_id),
    currency_id: Number(receiptForm.currency_id),
    total_minor_amount: total,
    categorization_status: 'pending',
    categorized_minor_amount: 0,
    remaining_uncategorized_minor_amount: total,
    attachments: []
  }
}

async function enqueueOfflineReceiptAttachment() {
  if (!activeReceipt.value?.client_uuid || !receiptAttachment.value) return
  await offlineQueue.enqueue({
    client_uuid: crypto.randomUUID(),
    operation_type: 'receipt.attachment.create',
    payload: {
      account_id: activeReceipt.value.account_id,
      currency_id: activeReceipt.value.currency_id,
      transaction_date: receiptForm.transaction_date,
      receipt_client_uuid: activeReceipt.value.client_uuid,
      ...(await buildOfflineAttachmentPayload(receiptAttachment.value))
    }
  })
}

async function completeReceipt() {
  if (!activeHousehold.value || !activeReceipt.value) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/receipts/${activeReceipt.value.id}/complete`, {
    method: 'POST',
    headers: auth.authHeaders()
  })
  if (!response.ok) return
  activeReceipt.value = (await response.json()).data ?? null
}

async function enqueueOfflineTransaction() {
  if (!offlineForm.account_id || !offlineForm.currency_id || !offlineForm.amount_minor) return
  await offlineQueue.enqueue({
    client_uuid: crypto.randomUUID(),
    operation_type: 'transaction.create',
    payload: {
      account_id: Number(offlineForm.account_id),
      currency_id: Number(offlineForm.currency_id),
      type: 'expense',
      status: 'confirmed',
      description: offlineForm.description || 'Offline expense',
      amount_minor: Number(offlineForm.amount_minor),
      transaction_date: offlineForm.transaction_date,
      version: 1
    }
  })
}

async function syncOfflineQueue() {
  const readyOperations = offlineQueue.readyOperations()
  if (!activeHousehold.value || readyOperations.length === 0) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/sync`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify({ operations: readyOperations })
  })
  if (!response.ok) {
    await offlineQueue.markFailed(readyOperations.map((operation) => operation.client_uuid))
    return
  }
  const payload = await response.json()
  const applied = (payload.results ?? [])
    .filter((result: { status: string }) => result.status === 'applied')
    .map((result: { client_uuid: string }) => result.client_uuid)
  const conflicts = (payload.results ?? [])
    .filter((result: { status: string }) => result.status === 'conflict')
    .map((result: { client_uuid: string; conflict_reason: string | null; client_payload?: Record<string, unknown> | null; server_payload?: Record<string, unknown> | null; server_result?: Record<string, unknown> | null }) => ({
      client_uuid: result.client_uuid,
      conflict_reason: result.conflict_reason,
      client_payload: result.client_payload ?? null,
      server_payload: result.server_payload ?? null,
      server_result: result.server_result ?? null
    }))
  offlineQueue.setConflicts(conflicts)
  const failed = readyOperations
    .map((operation) => operation.client_uuid)
    .filter((clientUuid) => !applied.includes(clientUuid) && !conflicts.some((conflict: { client_uuid: string }) => conflict.client_uuid === clientUuid))
  await offlineQueue.markFailed(failed)
  await offlineQueue.clearApplied(applied)
}

async function retryConflict(clientUuid: string) {
  await offlineQueue.retryAsNew(clientUuid)
  await syncOfflineQueue()
}

onMounted(async () => {
  await offlineQueue.load()
  await loadHouseholds()
  window.addEventListener('online', syncOfflineQueue)
  navigator.serviceWorker?.addEventListener('message', (event) => {
    if (event.data?.type === 'HOMEBUDGET_SYNC_NOW') void syncOfflineQueue()
  })
  if ('serviceWorker' in navigator && 'SyncManager' in window) {
    const registration = await navigator.serviceWorker.ready
    await registration.sync.register('homebudget-sync')
  }
})
</script>
