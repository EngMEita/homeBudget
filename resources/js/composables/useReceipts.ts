import { reactive, ref, type Ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { buildOfflineAttachmentPayload, useOfflineQueueStore } from '../stores/offlineQueue'
import type { Household, Member, Receipt } from '../types/dashboard'

export function useReceipts(activeHousehold: Ref<Household | null>, members: Ref<Member[]>) {
  const auth = useAuthStore()
  const offlineQueue = useOfflineQueueStore()
  const activeReceipt = ref<Receipt | null>(null)
  const receiptAttachment = ref<File | null>(null)
  const receiptForm = reactive({
    account_id: '',
    currency_id: '',
    total_minor_amount: '',
    transaction_date: new Date().toISOString().slice(0, 10),
    category_id: '',
    allocation_minor_amount: ''
  })

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

  return {
    activeReceipt,
    receiptForm,
    createReceipt,
    categorizeReceipt,
    selectReceiptAttachment,
    uploadReceiptAttachment,
    enqueueOfflineReceipt,
    enqueueOfflineReceiptAttachment,
    completeReceipt
  }
}
