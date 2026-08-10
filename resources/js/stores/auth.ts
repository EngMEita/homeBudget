import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('homebudget_token') ?? '',
    tokenLabel: localStorage.getItem('homebudget_token_label') ?? 'Current device'
  }),
  actions: {
    async login(email: string, password: string, deviceName = 'Current device') {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, device_name: deviceName })
      })
      if (!response.ok) return false
      const payload = await response.json()
      this.setToken(payload.token, deviceName)
      return true
    },
    async register(name: string, email: string, password: string, passwordConfirmation: string, deviceName = 'Current device') {
      const response = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
          device_name: deviceName
        })
      })
      if (!response.ok) return false
      const payload = await response.json()
      this.setToken(payload.token, deviceName)
      return true
    },
    async logout() {
      if (this.token) {
        await fetch('/api/auth/logout', { method: 'POST', headers: this.authHeaders() })
      }
      this.clearToken()
    },
    setToken(token: string, label = 'Current device') {
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
    authHeaders(): Record<string, string> {
      return this.token ? { Authorization: `Bearer ${this.token}` } : {}
    }
  }
})
