import { reactive, type Ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import type { AuditLog, BackupLog, Household } from '../types/dashboard'

export function useOperations(activeHousehold: Ref<Household | null>) {
  const auth = useAuthStore()
  const operations = reactive({
    backups: [] as BackupLog[],
    auditLogs: [] as AuditLog[]
  })

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

  async function createBackup() {
    if (!activeHousehold.value) return
    const response = await fetch(`/api/households/${activeHousehold.value.id}/backups`, { method: 'POST', headers: auth.authHeaders() })
    if (!response.ok) return
    await loadOperationsData()
  }

  async function restoreBackup(backupId: number) {
    if (!activeHousehold.value) return false
    const response = await fetch(`/api/households/${activeHousehold.value.id}/backups/restore`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({ backup_log_id: backupId })
    })
    if (response.ok) await loadOperationsData()
    return response.ok
  }

  return { operations, loadOperationsData, createBackup, restoreBackup }
}
