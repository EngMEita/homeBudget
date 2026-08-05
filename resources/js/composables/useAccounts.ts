import { computed, reactive, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useHouseholdStore } from '../stores/household'
import { decimalToMinor } from '../money'

export type Account = {
  id: number
  name: string
  account_type_id: number
  account_type_name?: string | null
  currency_id: number
  currency_code?: string | null
  opening_balance_minor: number
  is_shared: boolean
  is_active: boolean
}

export type AccountTypeOption = { id: number; name: string }
export type CurrencyOption = { id: number; code: string; name_en: string; name_ar: string }

export function useAccounts() {
  const auth = useAuthStore()
  const householdsStore = useHouseholdStore()
  const accounts = ref<Account[]>([])
  const accountTypes = ref<AccountTypeOption[]>([])
  const currencies = ref<CurrencyOption[]>([])
  const form = reactive({
    account_type_id: '',
    currency_id: '',
    name: '',
    opening_balance_minor: '0',
    is_shared: true,
    is_active: true
  })

  const householdId = computed(() => householdsStore.activeHouseholdId)

  async function loadAccounts() {
    if (!auth.token || !householdId.value) return
    const response = await fetch(`/api/households/${householdId.value}/accounts`, { headers: auth.authHeaders() })
    if (!response.ok) return
    const payload = await response.json()
    accounts.value = payload.data ?? []
    accountTypes.value = payload.account_types ?? []
    currencies.value = payload.currencies ?? []
    form.account_type_id ||= accountTypes.value[0]?.id ? String(accountTypes.value[0].id) : ''
    form.currency_id ||= currencies.value[0]?.id ? String(currencies.value[0].id) : ''
  }

  async function createAccount() {
    if (!auth.token || !householdId.value || !form.account_type_id || !form.currency_id || !form.name.trim()) return
    const response = await fetch(`/api/households/${householdId.value}/accounts`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
      body: JSON.stringify({
        account_type_id: Number(form.account_type_id),
        currency_id: Number(form.currency_id),
        name: form.name,
        opening_balance_minor: decimalToMinor(form.opening_balance_minor),
        is_shared: form.is_shared,
        is_active: form.is_active
      })
    })
    if (!response.ok) return
    form.name = ''
    form.opening_balance_minor = '0'
    await loadAccounts()
  }

  return { accounts, accountTypes, currencies, form, loadAccounts, createAccount }
}
