import { computed, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'

export type HouseholdNotification = {
  id: string
  title: string | null
  message: string | null
  read_at: string | null
  created_at: string | null
}

export function useNotifications() {
  const auth = useAuthStore()
  const householdsStore = useHouseholdStore()
  const notifications = ref<HouseholdNotification[]>([])
  const householdId = computed(() => householdsStore.activeHouseholdId)

  async function loadNotifications() {
    if (!auth.token || !householdId.value) return
    const response = await fetch(`/api/households/${householdId.value}/notifications`, { headers: auth.authHeaders() })
    if (!response.ok) return
    const payload = await response.json()
    notifications.value = payload.data ?? []
  }

  async function markRead(id: string) {
    if (!auth.token || !householdId.value) return
    const response = await fetch(`/api/households/${householdId.value}/notifications/${id}/read`, {
      method: 'POST',
      headers: auth.authHeaders()
    })
    if (!response.ok) return
    await loadNotifications()
  }

  return { notifications, loadNotifications, markRead }
}
