import { defineStore } from 'pinia'

const DB_NAME = 'homebudget-offline'
const DB_VERSION = 1
const STORE_NAME = 'sync_operations'

export type QueuedOperation = {
  client_uuid: string
  operation_type: 'transaction.create' | 'transaction.update' | 'transaction.delete' | 'receipt.create' | 'receipt.attachment.create'
  payload: Record<string, unknown>
  attempts?: number
  next_attempt_at?: string | null
}

export type SyncConflict = {
  client_uuid: string
  conflict_reason: string | null
  client_payload?: Record<string, unknown> | null
  server_payload?: Record<string, unknown> | null
  server_result?: Record<string, unknown> | null
}

const ATTACHMENT_CHUNK_SIZE = 256 * 1024

function readFileAsDataUrl(file: File | Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result))
    reader.onerror = () => reject(reader.error)
    reader.readAsDataURL(file)
  })
}

function base64Payload(dataUrl: string): string {
  return dataUrl.split(',', 2)[1] ?? ''
}

function chunkString(value: string, size = ATTACHMENT_CHUNK_SIZE): string[] {
  const chunks: string[] = []
  for (let index = 0; index < value.length; index += size) {
    chunks.push(value.slice(index, index + size))
  }
  return chunks
}

async function compressImage(file: File, maxDimension = 1600, quality = 0.82): Promise<Blob> {
  if (!file.type.startsWith('image/')) return file

  const bitmap = await createImageBitmap(file)
  const scale = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height))
  const canvas = document.createElement('canvas')
  canvas.width = Math.max(1, Math.round(bitmap.width * scale))
  canvas.height = Math.max(1, Math.round(bitmap.height * scale))
  canvas.getContext('2d')?.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
  bitmap.close()

  return new Promise((resolve) => {
    canvas.toBlob((blob) => resolve(blob ?? file), 'image/jpeg', quality)
  })
}

export async function buildOfflineAttachmentPayload(file: File) {
  const compressed = await compressImage(file)
  const dataUrl = await readFileAsDataUrl(compressed)
  const fileBase64 = base64Payload(dataUrl)

  return {
    original_name: file.name,
    mime_type: compressed.type || file.type || 'application/octet-stream',
    size_bytes: compressed.size,
    file_base64_chunks: chunkString(fileBase64)
  }
}

function openDatabase(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION)

    request.onupgradeneeded = () => {
      const database = request.result
      if (!database.objectStoreNames.contains(STORE_NAME)) {
        database.createObjectStore(STORE_NAME, { keyPath: 'client_uuid' })
      }
    }

    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error)
  })
}

async function readAll(): Promise<QueuedOperation[]> {
  const database = await openDatabase()
  return new Promise((resolve, reject) => {
    const transaction = database.transaction(STORE_NAME, 'readonly')
    const store = transaction.objectStore(STORE_NAME)
    const request = store.getAll()
    request.onsuccess = () => resolve(request.result as QueuedOperation[])
    request.onerror = () => reject(request.error)
  })
}

async function putOperation(operation: QueuedOperation): Promise<void> {
  const database = await openDatabase()
  return new Promise((resolve, reject) => {
    const transaction = database.transaction(STORE_NAME, 'readwrite')
    transaction.objectStore(STORE_NAME).put(operation)
    transaction.oncomplete = () => resolve()
    transaction.onerror = () => reject(transaction.error)
  })
}

async function deleteOperations(clientUuids: string[]): Promise<void> {
  const database = await openDatabase()
  return new Promise((resolve, reject) => {
    const transaction = database.transaction(STORE_NAME, 'readwrite')
    const store = transaction.objectStore(STORE_NAME)
    clientUuids.forEach((clientUuid) => store.delete(clientUuid))
    transaction.oncomplete = () => resolve()
    transaction.onerror = () => reject(transaction.error)
  })
}

export const useOfflineQueueStore = defineStore('offlineQueue', {
  state: () => ({
    operations: [] as QueuedOperation[],
    conflicts: [] as SyncConflict[]
  }),
  actions: {
    async load() {
      this.operations = await readAll()
    },
    async enqueue(operation: QueuedOperation) {
      await putOperation({
        ...operation,
        attempts: operation.attempts ?? 0,
        next_attempt_at: operation.next_attempt_at ?? null
      })
      await this.load()
    },
    async clearApplied(clientUuids: string[]) {
      await deleteOperations(clientUuids)
      await this.load()
    },
    async discard(clientUuid: string) {
      await deleteOperations([clientUuid])
      this.conflicts = this.conflicts.filter((conflict) => conflict.client_uuid !== clientUuid)
      await this.load()
    },
    async retryAsNew(clientUuid: string) {
      const operation = this.operations.find((item) => item.client_uuid === clientUuid)
      if (!operation) return

      await putOperation({ ...operation, client_uuid: crypto.randomUUID(), attempts: 0, next_attempt_at: null })
      await deleteOperations([clientUuid])
      this.conflicts = this.conflicts.filter((conflict) => conflict.client_uuid !== clientUuid)
      await this.load()
    },
    async markFailed(clientUuids: string[]) {
      const now = Date.now()
      await Promise.all(this.operations
        .filter((operation) => clientUuids.includes(operation.client_uuid))
        .map((operation) => {
          const attempts = (operation.attempts ?? 0) + 1
          const delayMs = Math.min(60000, 1000 * 2 ** attempts)
          return putOperation({
            ...operation,
            attempts,
            next_attempt_at: new Date(now + delayMs).toISOString()
          })
        }))
      await this.load()
    },
    readyOperations() {
      const now = Date.now()
      return this.operations.filter((operation) => !operation.next_attempt_at || Date.parse(operation.next_attempt_at) <= now)
    },
    setConflicts(conflicts: SyncConflict[]) {
      this.conflicts = conflicts
    }
  }
})
