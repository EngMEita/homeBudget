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

    <section v-if="activeHousehold" class="workflow-strip" :aria-label="t('dashboard_workflow')">
      <button
        v-for="tab in workflowTabs"
        :key="tab.id"
        class="workflow-tab"
        :class="{ active: activeTab === tab.id }"
        type="button"
        @click="activeTab = tab.id"
      >
        <span>{{ tab.step }}</span>
        {{ tab.label }}
      </button>
    </section>

    <MembersPanel
      v-if="activeHousehold && activeTab === 'setup'"
      v-model="invitation"
      :members="members"
      :invitations="invitations"
      @invite-member="inviteMember"
      @refresh-members="loadMembers"
    />

    <SplitExpensePanel
      v-if="activeHousehold && activeTab === 'pay'"
      :household-id="activeHousehold.id"
      :accounts="accounts"
      :currencies="currencies"
      @saved="handleSplitPaymentSaved"
    />

    <ReceiptPanel
      v-if="activeHousehold && activeTab === 'receipts'"
      v-model="receiptForm"
      :active-receipt="activeReceipt"
      :accounts="accounts"
      :currencies="currencies"
      @create-receipt="createReceipt"
      @queue-offline-receipt="enqueueOfflineReceipt"
      @categorize-receipt="categorizeReceipt"
      @select-attachment="selectReceiptAttachment"
      @upload-attachment="uploadReceiptAttachment"
      @queue-attachment-offline="enqueueOfflineReceiptAttachment"
      @complete-receipt="completeReceipt"
    />

    <BudgetPanel
      v-if="activeHousehold && activeTab === 'planning'"
      v-model="budgetForm"
      :summary="budgetSummary"
      @create-budget="createBudget"
      @refresh-budget="loadBudgetSummary"
    />

    <PlanningPanel
      v-if="activeHousehold && activeTab === 'planning'"
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
      v-if="activeHousehold && activeTab === 'operations'"
      :backups="operations.backups"
      :audit-logs="operations.auditLogs"
      @create-backup="createBackup"
      @restore-backup="restoreBackup"
      @refresh-operations="loadOperationsData"
    />

    <OfflineSyncPanel
      v-if="activeHousehold && activeTab === 'sync'"
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
import { computed, onMounted, ref } from 'vue'
import BudgetPanel from '../components/dashboard/BudgetPanel.vue'
import HouseholdPanel from '../components/dashboard/HouseholdPanel.vue'
import MembersPanel from '../components/dashboard/MembersPanel.vue'
import OfflineSyncPanel from '../components/dashboard/OfflineSyncPanel.vue'
import OperationsPanel from '../components/dashboard/OperationsPanel.vue'
import PlanningPanel from '../components/dashboard/PlanningPanel.vue'
import ReceiptPanel from '../components/dashboard/ReceiptPanel.vue'
import SplitExpensePanel from '../components/dashboard/SplitExpensePanel.vue'
import { useBudgets } from '../composables/useBudgets'
import { useHouseholds } from '../composables/useHouseholds'
import { useOfflineSync } from '../composables/useOfflineSync'
import { useOperations } from '../composables/useOperations'
import { usePlanning } from '../composables/usePlanning'
import { useReceipts } from '../composables/useReceipts'
import { useAccounts } from '../composables/useAccounts'
import { useLocaleStore } from '../stores/locale'
import { translate } from '../i18n'

const locale = useLocaleStore()
const activeTab = ref<'setup' | 'pay' | 'receipts' | 'planning' | 'operations' | 'sync'>('pay')

function t(key: string) {
  return translate(locale.locale, key)
}

const workflowTabs = computed(() => [
  { id: 'setup', step: '1', label: t('workflow_setup') },
  { id: 'pay', step: '2', label: t('workflow_pay') },
  { id: 'receipts', step: '3', label: t('workflow_receipts') },
  { id: 'planning', step: '4', label: t('workflow_planning') },
  { id: 'operations', step: '5', label: t('workflow_operations') },
  { id: 'sync', step: '6', label: t('workflow_sync') }
] as const)

const {
  householdsStore,
  households,
  members,
  invitations,
  activeHousehold,
  newHousehold,
  invitation,
  loadHouseholds,
  createHousehold,
  selectHousehold: selectHouseholdBase,
  loadMembers,
  inviteMember
} = useHouseholds()
const { budgetSummary, budgetForm, loadBudgetSummary, createBudget } = useBudgets(activeHousehold)
const { accounts, currencies, loadAccounts } = useAccounts()
const { operations, loadOperationsData, createBackup, restoreBackup: restoreBackupRequest } = useOperations(activeHousehold)
const {
  planning,
  planningForm,
  loadPlanningData,
  createRecurringRule,
  createUpcomingBill,
  createSavingsGoal,
  contributeToGoal,
  createDebt,
  payDebtInstallment
} = usePlanning(activeHousehold, loadOperationsData)
const {
  activeReceipt,
  receiptForm,
  createReceipt,
  categorizeReceipt,
  selectReceiptAttachment,
  uploadReceiptAttachment,
  enqueueOfflineReceipt,
  enqueueOfflineReceiptAttachment,
  completeReceipt
} = useReceipts(activeHousehold, members)
const { offlineQueue, offlineForm, enqueueOfflineTransaction, syncOfflineQueue, retryConflict, loadOfflineQueue } = useOfflineSync(activeHousehold)

async function refreshHouseholdPanels() {
  await loadAccounts()
  await loadMembers()
  await loadPlanningData()
  await loadOperationsData()
}

async function selectHousehold(id: number) {
  await selectHouseholdBase(id)
  activeTab.value = 'pay'
  await refreshHouseholdPanels()
}

async function handleSplitPaymentSaved() {
  await loadAccounts()
  activeTab.value = 'receipts'
}

async function restoreBackup(backupId: number) {
  if (!window.confirm(translate(locale.locale, 'restore_backup_confirm'))) return
  if (await restoreBackupRequest(backupId)) window.location.reload()
}

onMounted(async () => {
  await loadOfflineQueue()
  await loadHouseholds()
  if (activeHousehold.value) await refreshHouseholdPanels()
  window.addEventListener('online', syncOfflineQueue)
  navigator.serviceWorker?.addEventListener('message', (event) => {
    if (event.data?.type === 'HOMEBUDGET_SYNC_NOW') void syncOfflineQueue()
  })
  if ('serviceWorker' in navigator && 'SyncManager' in window) {
    const registration = await navigator.serviceWorker.ready
    await (registration as ServiceWorkerRegistration & { sync: { register(tag: string): Promise<void> } }).sync.register('homebudget-sync')
  }
})
</script>
