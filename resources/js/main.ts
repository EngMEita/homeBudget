import { createApp } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import DashboardView from './views/DashboardView.vue'
import SecurityView from './views/SecurityView.vue'
import TransactionHistoryView from './views/TransactionHistoryView.vue'
import './styles/app.css'
import { useLocaleStore } from './stores/locale'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', component: DashboardView },
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

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js')
}
