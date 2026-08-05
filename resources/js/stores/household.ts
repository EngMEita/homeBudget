import { defineStore } from 'pinia'

const STORAGE_KEY = 'homebudget_active_household_id'

export const useHouseholdStore = defineStore('household', {
  state: () => ({
    activeHouseholdId: Number(localStorage.getItem(STORAGE_KEY) ?? 0) || 0
  }),
  actions: {
    setActiveHouseholdId(id: number) {
      this.activeHouseholdId = id
      localStorage.setItem(STORAGE_KEY, String(id))
    }
  }
})
