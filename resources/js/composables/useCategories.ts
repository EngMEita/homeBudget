import { computed, reactive, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'

export type Category = {
  id: number
  parent_id: number | null
  name: string
  type: string
  is_active: boolean
}

export function useCategories() {
  const auth = useAuthStore()
  const householdsStore = useHouseholdStore()
  const categories = ref<Category[]>([])
  const form = reactive({
    parent_id: '',
    name: '',
    type: 'expense',
    is_active: true
  })
  const householdId = computed(() => householdsStore.activeHouseholdId)

  async function loadCategories() {
    if (!auth.token || !householdId.value) return
    const response = await fetch(`/api/households/${householdId.value}/categories`, { headers: auth.authHeaders() })
    if (!response.ok) return
    const payload = await response.json()
    categories.value = payload.data ?? []
  }

  async function createCategory() {
    if (!auth.token || !householdId.value || !form.name.trim()) return
    const response = await fetch(`/api/households/${householdId.value}/categories`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({
        parent_id: form.parent_id ? Number(form.parent_id) : null,
        name: form.name,
        type: form.type,
        is_active: form.is_active
      })
    })
    if (!response.ok) return
    form.name = ''
    form.parent_id = ''
    await loadCategories()
  }

  return { categories, form, loadCategories, createCategory }
}
