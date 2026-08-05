import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('homebudget_token') ?? '',
    tokenLabel: localStorage.getItem('homebudget_token_label') ?? 'Current device'
  }),
  actions: {
    setToken(token: string, label = this.tokenLabel) {
      this.token = token
      this.tokenLabel = label
      localStorage.setItem('homebudget_token', token)
      localStorage.setItem('homebudget_token_label', label)
    },
    setTokenLabel(label: string) {
      this.tokenLabel = label
      localStorage.setItem('homebudget_token_label', label)
    },
    clearToken() {
      this.token = ''
      localStorage.removeItem('homebudget_token')
      localStorage.removeItem('homebudget_token_label')
    },
    authHeaders() {
      return this.token ? { Authorization: `Bearer ${this.token}` } : {}
    }
  }
})
