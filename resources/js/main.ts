import { createApp } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { registerSW } from 'virtual:pwa-register'
import App from './App.vue'
import AccountsView from './views/AccountsView.vue'
import DashboardView from './views/DashboardView.vue'
import ReportsView from './views/ReportsView.vue'
import SecurityView from './views/SecurityView.vue'
import TransactionHistoryView from './views/TransactionHistoryView.vue'
import './styles/app.css'
import { useLocaleStore } from './stores/locale'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: DashboardView },
  { path: '/accounts', component: AccountsView },
  { path: '/reports', component: ReportsView },
  { path: '/transactions', component: TransactionHistoryView },
  { path: '/security', component: SecurityView }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

const pinia = createPinia()
setActivePinia(pinia)

const localeStore = useLocaleStore()
localeStore.setLocale(localeStore.locale)

createApp(App).use(pinia).use(router).mount('#app')

registerSW({
  immediate: true,
  onNeedRefresh() {
    window.dispatchEvent(new CustomEvent('homebudget:pwa-update-ready'))
  },
  onOfflineReady() {
    window.dispatchEvent(new CustomEvent('homebudget:pwa-offline-ready'))
  }
})
