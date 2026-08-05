import { reactive, type Ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useOfflineQueueStore } from '../stores/offlineQueue'
import type { Household } from '../types/dashboard'

export function useOfflineSync(activeHousehold: Ref<Household | null>) {
  const auth = useAuthStore()
  const offlineQueue = useOfflineQueueStore()
  const offlineForm = reactive({
    account_id: '',
    currency_id: '',
    amount_minor: '',
    description: '',
    transaction_date: new Date().toISOString().slice(0, 10)
  })

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

  async function loadOfflineQueue() {
    await offlineQueue.load()
  }

  return { offlineQueue, offlineForm, enqueueOfflineTransaction, syncOfflineQueue, retryConflict, loadOfflineQueue }
}
