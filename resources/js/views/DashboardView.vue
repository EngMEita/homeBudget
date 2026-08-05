<template>
  <main class="shell">
    <section class="panel">
      <div class="history-header">
        <div>
          <h2>{{ t('active_household') }}</h2>
          <p class="lead">Switch workspaces or create a new household.</p>
        </div>
        <label class="field compact">
          <span>{{ t('language') }}</span>
          <select v-model="locale.locale" @change="locale.setLocale(locale.locale)">
            <option value="en">{{ t('english') }}</option>
            <option value="ar">{{ t('arabic') }}</option>
          </select>
        </label>
      </div>

      <div class="filters-grid">
        <label class="field">
          <span>Household</span>
          <select v-model.number="householdsStore.activeHouseholdId" @change="selectHousehold">
            <option :value="0">Select a household</option>
            <option v-for="household in households" :key="household.id" :value="household.id">
              {{ household.name }} ({{ household.base_currency_code }})
            </option>
          </select>
        </label>
        <label class="field">
          <span>Name</span>
          <input v-model="newHousehold.name" type="text" placeholder="Family Budget" />
        </label>
        <label class="field">
          <span>Base currency</span>
          <input v-model="newHousehold.base_currency_code" type="text" maxlength="3" placeholder="SAR" />
        </label>
        <label class="field">
          <span>Default locale</span>
          <select v-model="newHousehold.default_locale">
            <option value="en">en</option>
            <option value="ar">ar</option>
          </select>
        </label>
      </div>

      <div class="actions-row">
        <button class="button" type="button" @click="createHousehold">Create household</button>
        <button class="button button-secondary" type="button" @click="loadHouseholds">Refresh households</button>
      </div>
    </section>

    <section class="panel" v-if="activeHousehold">
      <h2>Household members</h2>
      <div class="filters-grid">
        <label class="field">
          <span>Email</span>
          <input v-model="invitation.email" type="email" placeholder="spouse@example.com" />
        </label>
        <label class="field">
          <span>Role</span>
          <select v-model="invitation.role">
            <option value="administrator">Administrator</option>
            <option value="contributor">Contributor</option>
            <option value="viewer">Viewer</option>
            <option value="restricted">Restricted</option>
          </select>
        </label>
      </div>
      <div class="actions-row">
        <button class="button" type="button" @click="inviteMember">Send invitation</button>
        <button class="button button-secondary" type="button" @click="loadMembers">Refresh members</button>
      </div>

      <div class="history-list">
        <article v-for="member in members" :key="member.user_id" class="history-row">
          <div>
            <strong>{{ member.name }}</strong>
            <div class="token-meta">{{ member.email }}</div>
          </div>
          <div class="history-metrics">
            <span>{{ member.role }}</span>
            <span v-if="member.can_create_transactions">can create</span>
            <span v-if="member.can_view_transactions">can view</span>
          </div>
        </article>
      </div>

      <div class="history-list" v-if="invitations.length">
        <h3>Pending invitations</h3>
        <article v-for="invitationItem in invitations" :key="invitationItem.id" class="history-row">
          <div>
            <strong>{{ invitationItem.email }}</strong>
            <div class="token-meta">{{ invitationItem.role }}</div>
          </div>
          <div class="history-metrics">
            <span>{{ invitationItem.accepted_at ? 'accepted' : 'pending' }}</span>
            <span>{{ invitationItem.token }}</span>
          </div>
        </article>
      </div>
    </section>

    <section class="panel" v-if="activeHousehold">
      <h2>Budgets</h2>
      <div class="filters-grid">
        <label class="field">
          <span>Budget name</span>
          <input v-model="budgetForm.name" type="text" placeholder="Monthly budget" />
        </label>
        <label class="field">
          <span>Start</span>
          <input v-model="budgetForm.starts_on" type="date" />
        </label>
        <label class="field">
          <span>End</span>
          <input v-model="budgetForm.ends_on" type="date" />
        </label>
        <label class="field">
          <span>Category</span>
          <input v-model="budgetForm.category_id" type="number" min="1" />
        </label>
        <label class="field">
          <span>Planned amount</span>
          <input v-model="budgetForm.planned_minor_amount" type="number" min="1" />
        </label>
      </div>
      <div class="actions-row">
        <button class="button" type="button" @click="createBudget">Create budget</button>
        <button class="button button-secondary" type="button" @click="loadBudgetSummary">Refresh budget</button>
      </div>
      <div class="history-list" v-if="budgetSummary.budget">
        <article class="history-row">
          <div>
            <strong>{{ budgetSummary.budget.name }}</strong>
            <div class="token-meta">{{ budgetSummary.budget.period_type }} · {{ budgetSummary.budget.base_currency_code }}</div>
          </div>
        </article>
        <article v-for="period in budgetSummary.periods" :key="period.id" class="history-row">
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

    <section class="panel" v-if="activeHousehold">
      <h2>Receipts</h2>
      <div class="filters-grid">
        <label class="field">
          <span>Account ID</span>
          <input v-model="receiptForm.account_id" type="number" min="1" />
        </label>
        <label class="field">
          <span>Currency ID</span>
          <input v-model="receiptForm.currency_id" type="number" min="1" />
        </label>
        <label class="field">
          <span>Total</span>
          <input v-model="receiptForm.total_minor_amount" type="number" min="1" />
        </label>
        <label class="field">
          <span>Date</span>
          <input v-model="receiptForm.transaction_date" type="date" />
        </label>
        <label class="field">
          <span>Category ID</span>
          <input v-model="receiptForm.category_id" type="number" min="1" />
        </label>
        <label class="field">
          <span>Allocation</span>
          <input v-model="receiptForm.allocation_minor_amount" type="number" min="1" />
        </label>
      </div>
      <div class="actions-row">
        <button class="button" type="button" @click="createReceipt">Create receipt</button>
        <button class="button button-secondary" type="button" @click="enqueueOfflineReceipt">Queue offline receipt</button>
        <button class="button button-secondary" type="button" @click="categorizeReceipt">Save categorization</button>
      </div>
      <div class="filters-grid" v-if="activeReceipt">
        <label class="field">
          <span>Receipt attachment</span>
          <input type="file" accept="image/*,.pdf" @change="selectReceiptAttachment" />
        </label>
      </div>
      <div class="actions-row" v-if="activeReceipt">
        <button class="button button-secondary" type="button" @click="uploadReceiptAttachment">Upload attachment</button>
        <button class="button button-secondary" type="button" @click="enqueueOfflineReceiptAttachment">Queue attachment offline</button>
        <button class="button" type="button" @click="completeReceipt">Complete receipt</button>
      </div>
      <article class="history-row" v-if="activeReceipt">
        <div>
          <strong>Receipt #{{ activeReceipt.id }}</strong>
          <div class="token-meta">{{ activeReceipt.categorization_status }}</div>
        </div>
        <div class="history-metrics">
          <span>categorized {{ activeReceipt.categorized_minor_amount }}</span>
          <span>remaining {{ activeReceipt.remaining_uncategorized_minor_amount }}</span>
          <span>attachments {{ activeReceipt.attachments.length }}</span>
        </div>
      </article>
    </section>

    <section class="panel" v-if="activeHousehold">
      <h2>Recurring, bills, goals, and debts</h2>
      <div class="filters-grid">
        <label class="field">
          <span>Name</span>
          <input v-model="planningForm.name" type="text" placeholder="Internet bill" />
        </label>
        <label class="field">
          <span>Account ID</span>
          <input v-model="planningForm.account_id" type="number" min="1" />
        </label>
        <label class="field">
          <span>Currency ID</span>
          <input v-model="planningForm.currency_id" type="number" min="1" />
        </label>
        <label class="field">
          <span>Amount</span>
          <input v-model="planningForm.amount_minor" type="number" min="1" />
        </label>
        <label class="field">
          <span>Date</span>
          <input v-model="planningForm.date" type="date" />
        </label>
        <label class="field">
          <span>Counterparty</span>
          <input v-model="planningForm.counterparty_name" type="text" placeholder="Relative or bank" />
        </label>
      </div>
      <div class="actions-row">
        <button class="button" type="button" @click="createRecurringRule">Create recurring rule</button>
        <button class="button button-secondary" type="button" @click="createUpcomingBill">Create bill</button>
        <button class="button button-secondary" type="button" @click="createSavingsGoal">Create goal</button>
        <button class="button button-secondary" type="button" @click="createDebt">Create debt</button>
        <button class="button" type="button" @click="loadPlanningData">Refresh planning</button>
      </div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">Recurring rules</div>
          <strong>{{ planning.recurringRules.length }}</strong>
        </div>
        <div class="stat-card">
          <div class="stat-label">Upcoming bills</div>
          <strong>{{ planning.upcomingBills.length }}</strong>
        </div>
        <div class="stat-card">
          <div class="stat-label">Savings goals</div>
          <strong>{{ planning.savingsGoals.length }}</strong>
        </div>
        <div class="stat-card">
          <div class="stat-label">Debts</div>
          <strong>{{ planning.debts.length }}</strong>
        </div>
      </div>
      <div class="history-list" v-if="planning.savingsGoals.length || planning.debts.length">
        <article v-for="goal in planning.savingsGoals" :key="`goal-${goal.id}`" class="history-row">
          <div>
            <strong>{{ goal.name }}</strong>
            <div class="token-meta">{{ goal.current_minor_amount }}/{{ goal.target_minor_amount }} · {{ goal.status }}</div>
          </div>
          <button class="button button-secondary" type="button" @click="contributeToGoal(goal.id)">Contribute</button>
        </article>
        <article v-for="debt in planning.debts" :key="`debt-${debt.id}`" class="history-row">
          <div>
            <strong>{{ debt.name }}</strong>
            <div class="token-meta">{{ debt.counterparty_name }} · remaining {{ debt.remaining_minor_amount }}</div>
          </div>
          <button class="button button-secondary" type="button" @click="payDebtInstallment(debt.id)">Pay installment</button>
        </article>
      </div>
    </section>

    <section class="panel" v-if="activeHousehold">
      <h2>Audit and backups</h2>
      <div class="actions-row">
        <button class="button" type="button" @click="createBackup">Create SQLite backup</button>
        <button class="button button-secondary" type="button" @click="loadOperationsData">Refresh operations</button>
      </div>
      <div class="history-list">
        <article v-for="backup in operations.backups" :key="`backup-${backup.id}`" class="history-row">
          <div>
            <strong>{{ backup.status }}</strong>
            <div class="token-meta">{{ backup.path ?? 'pending' }} · {{ backup.size_bytes }} bytes</div>
          </div>
        </article>
        <article v-for="log in operations.auditLogs" :key="`audit-${log.id}`" class="history-row">
          <div>
            <strong>{{ log.event }}</strong>
            <div class="token-meta">{{ log.created_at }}</div>
          </div>
        </article>
      </div>
    </section>

    <section class="panel" v-if="activeHousehold">
      <h2>Offline sync</h2>
      <div class="filters-grid">
        <label class="field">
          <span>Account ID</span>
          <input v-model="offlineForm.account_id" type="number" min="1" />
        </label>
        <label class="field">
          <span>Currency ID</span>
          <input v-model="offlineForm.currency_id" type="number" min="1" />
        </label>
        <label class="field">
          <span>Amount</span>
          <input v-model="offlineForm.amount_minor" type="number" min="1" />
        </label>
        <label class="field">
          <span>Description</span>
          <input v-model="offlineForm.description" type="text" />
        </label>
      </div>
      <div class="actions-row">
        <button class="button" type="button" @click="enqueueOfflineTransaction">Queue offline expense</button>
        <button class="button button-secondary" type="button" @click="syncOfflineQueue">Sync queue</button>
      </div>
      <p class="lead">Pending operations: {{ offlineQueue.operations.length }}</p>
      <div class="history-list" v-if="offlineQueue.operations.length">
        <article v-for="operation in offlineQueue.operations" :key="operation.client_uuid" class="history-row">
          <div>
            <strong>{{ operation.operation_type }}</strong>
            <div class="token-meta">attempts {{ operation.attempts ?? 0 }} · next {{ operation.next_attempt_at ?? 'ready' }}</div>
          </div>
        </article>
      </div>
      <div class="history-list" v-if="offlineQueue.conflicts.length">
        <article v-for="conflict in offlineQueue.conflicts" :key="conflict.client_uuid" class="history-row">
          <div>
            <strong>{{ conflict.client_uuid }}</strong>
            <div class="token-meta">{{ conflict.conflict_reason }}</div>
            <details class="conflict-details">
              <summary>Compare client and server payloads</summary>
              <pre>Client: {{ formatPayload(conflict.client_payload) }}</pre>
              <pre>Server: {{ formatPayload(conflict.server_payload ?? conflict.server_result) }}</pre>
            </details>
          </div>
          <div class="history-metrics">
            <button class="button button-secondary" type="button" @click="offlineQueue.discard(conflict.client_uuid)">Discard</button>
            <button class="button" type="button" @click="retryConflict(conflict.client_uuid)">Retry as new</button>
          </div>
        </article>
      </div>
    </section>

  </main>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'
import { useLocaleStore } from '../stores/locale'
import { buildOfflineAttachmentPayload, useOfflineQueueStore } from '../stores/offlineQueue'
import { translate } from '../i18n'

type Household = {
  id: number
  name: string
  base_currency_code: string
  default_locale: string
}

type Member = {
  user_id: number
  name: string
  email: string
  role: string
  can_view_balances: boolean
  can_create_transactions: boolean
  can_view_transactions: boolean
}

type Invitation = {
  id: number
  email: string
  role: string
  token: string
  accepted_at: string | null
}

type BudgetSummary = {
  budget: null | {
    id: number
    name: string
    period_type: string
    base_currency_code: string
  }
  periods: Array<{
    id: number
    starts_on: string
    ends_on: string
    status: string
    lines: Array<{
      category_id: number
      category_name: string | null
      planned_minor_amount: number
      actual_minor_amount: number
      remaining_minor_amount: number
    }>
  }>
}

type Receipt = {
  id: number
  client_uuid: string | null
  account_id: number
  currency_id: number
  total_minor_amount: number
  categorization_status: string
  categorized_minor_amount: number
  remaining_uncategorized_minor_amount: number
  attachments: Array<{ id: number; original_name: string }>
}

type RecurringRule = { id: number; name: string; amount_minor: number; next_run_on: string | null }
type UpcomingBill = { id: number; name: string; amount_minor: number; due_on: string | null; status: string }
type SavingsGoal = { id: number; name: string; target_minor_amount: number; current_minor_amount: number; status: string }
type Debt = { id: number; name: string; counterparty_name: string; remaining_minor_amount: number; status: string }
type BackupLog = { id: number; status: string; path: string | null; size_bytes: number }
type AuditLog = { id: number; event: string; created_at: string }

const auth = useAuthStore()
const locale = useLocaleStore()
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
const newHousehold = reactive({
  name: '',
  base_currency_code: 'SAR',
  default_locale: 'en'
})
const invitation = reactive({
  email: '',
  role: 'viewer'
})
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

function t(key: string) {
  return translate(locale.locale, key)
}

function formatPayload(payload: unknown) {
  if (!payload) return 'No payload returned'
  return JSON.stringify(payload, null, 2)
}

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
  if (payload.data?.id) {
    householdsStore.setActiveHouseholdId(payload.data.id)
  }
  newHousehold.name = ''
  await loadHouseholds()
}

async function selectHousehold() {
  if (!householdsStore.activeHouseholdId) return
  activeHousehold.value = households.value.find((household) => household.id === householdsStore.activeHouseholdId) ?? null
  await loadMembers()
  await loadPlanningData()
  await loadOperationsData()
  locale.setLocale(locale.locale)
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
      lines: [
        {
          category_id: Number(budgetForm.category_id),
          planned_minor_amount: Number(budgetForm.planned_minor_amount)
        }
      ]
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
    body: JSON.stringify({
      amount_minor: Number(planningForm.amount_minor),
      contributed_on: planningForm.date
    })
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
    body: JSON.stringify({
      principal_minor_amount: Number(planningForm.amount_minor),
      interest_minor_amount: 0,
      paid_on: planningForm.date
    })
  })
  if (!response.ok) return
  await loadPlanningData()
  await loadOperationsData()
}

async function createBackup() {
  if (!activeHousehold.value) return
  const response = await fetch(`/api/households/${activeHousehold.value.id}/backups`, {
    method: 'POST',
    headers: auth.authHeaders()
  })
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
  const payload = await response.json()
  activeReceipt.value = payload.data ?? null
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
      allocations: [
        {
          category_id: Number(receiptForm.category_id),
          amount_minor: Number(receiptForm.allocation_minor_amount)
        }
      ]
    })
  })
  if (!response.ok) return
  const payload = await response.json()
  activeReceipt.value = payload.data ?? null
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
  const attachmentPayload = await buildOfflineAttachmentPayload(receiptAttachment.value)

  await offlineQueue.enqueue({
    client_uuid: crypto.randomUUID(),
    operation_type: 'receipt.attachment.create',
    payload: {
      account_id: activeReceipt.value.account_id,
      currency_id: activeReceipt.value.currency_id,
      transaction_date: receiptForm.transaction_date,
      receipt_client_uuid: activeReceipt.value.client_uuid,
      ...attachmentPayload
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
  const payload = await response.json()
  activeReceipt.value = payload.data ?? null
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
    .map((result: {
      client_uuid: string
      conflict_reason: string | null
      client_payload?: Record<string, unknown> | null
      server_payload?: Record<string, unknown> | null
      server_result?: Record<string, unknown> | null
    }) => ({
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
    if (event.data?.type === 'HOMEBUDGET_SYNC_NOW') {
      void syncOfflineQueue()
    }
  })
  if ('serviceWorker' in navigator && 'SyncManager' in window) {
    const registration = await navigator.serviceWorker.ready
    await registration.sync.register('homebudget-sync')
  }
})
</script>
