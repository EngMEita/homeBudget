import { defineStore } from 'pinia'

const STORAGE_KEY = 'homebudget_locale'
const RTL_LOCALES = new Set(['ar'])

export const useLocaleStore = defineStore('locale', {
  state: () => ({
    locale: localStorage.getItem(STORAGE_KEY) ?? 'en'
  }),
  getters: {
    isRtl: (state) => RTL_LOCALES.has(state.locale)
  },
  actions: {
    setLocale(locale: string) {
      this.locale = locale
      localStorage.setItem(STORAGE_KEY, locale)
      document.documentElement.lang = locale
      document.documentElement.dir = RTL_LOCALES.has(locale) ? 'rtl' : 'ltr'
    }
  }
})
