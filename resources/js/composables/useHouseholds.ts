import { reactive, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'
import type { Household, Invitation, Member } from '../types/dashboard'

export function useHouseholds() {
  const auth = useAuthStore()
  const householdsStore = useHouseholdStore()
  const households = ref<Household[]>([])
  const members = ref<Member[]>([])
  const invitations = ref<Invitation[]>([])
  const activeHousehold = ref<Household | null>(null)
  const newHousehold = reactive({ name: '', base_currency_code: 'SAR', default_locale: 'en' })
  const invitation = reactive({ email: '', role: 'viewer' })

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

  return {
    householdsStore,
    households,
    members,
    invitations,
    activeHousehold,
    newHousehold,
    invitation,
    loadHouseholds,
    createHousehold,
    selectHousehold,
    loadMembers,
    inviteMember
  }
}
