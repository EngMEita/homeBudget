<template>
  <main class="app-frame">
    <aside class="sidebar">
      <RouterLink class="brand" :to="auth.token ? '/dashboard' : '/login'">
        <span class="brand-mark">HB</span>
        <span>
          <strong>{{ t('app_brand') }}</strong>
          <small>{{ auth.token ? t('active_household') : t('login') }}</small>
        </span>
      </RouterLink>

      <nav v-if="auth.token" class="nav-stack" :aria-label="t('primary_navigation')">
        <section v-for="group in navGroups" :key="group.label" class="nav-group">
          <p class="nav-label">{{ group.label }}</p>
          <RouterLink v-for="item in group.items" :key="item.to" :to="item.to" class="nav-link">
            <span class="nav-icon" aria-hidden="true">{{ item.icon }}</span>
            <span>
              <strong>{{ item.label }}</strong>
              <small>{{ item.hint }}</small>
            </span>
          </RouterLink>
        </section>
      </nav>

      <nav v-else class="nav-stack" :aria-label="t('primary_navigation')">
        <RouterLink to="/login" class="nav-link">
          <span class="nav-dot"></span>
          {{ t('login') }}
        </RouterLink>
      </nav>

      <button v-if="auth.token" class="logout-button" type="button" @click="logout">{{ t('logout') }}</button>
    </aside>

    <section class="workspace">
      <header class="topbar">
        <div>
          <p class="eyebrow">{{ currentSection }}</p>
          <h1>{{ t('app_title') }}</h1>
          <p class="lead">{{ t('app_subtitle') }}</p>
        </div>
        <div class="locale-pill" :dir="locale.isRtl ? 'rtl' : 'ltr'">
          <span>{{ t('language') }}</span>
          <select v-model="locale.locale" @change="locale.setLocale(locale.locale)">
            <option value="en">{{ t('english') }}</option>
            <option value="ar">{{ t('arabic') }}</option>
          </select>
        </div>
      </header>

      <RouterView />
    </section>
  </main>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useLocaleStore } from './stores/locale'
import { useAuthStore } from './stores/auth'
import { translate } from './i18n'

const locale = useLocaleStore()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

function t(key: string) {
  return translate(locale.locale, key)
}

const navGroups = computed(() => [
  {
    label: t('dashboard'),
    items: [
      { to: '/dashboard', label: t('dashboard') },
      { to: '/reports', label: t('reports') },
      { to: '/notifications', label: t('notifications') }
    ]
  },
  {
    label: t('household'),
    items: [
      { to: '/accounts', label: t('accounts') },
      { to: '/categories', label: t('categories') },
      { to: '/receipts', label: t('receipts') }
    ]
  },
  {
    label: t('operations'),
    items: [
      { to: '/transactions', label: t('transaction_history') },
      { to: '/offline-sync', label: t('offline_sync') }
    ]
  },
  {
    label: t('settings'),
    items: [
      { to: '/settings', label: t('settings') },
      { to: '/security', label: t('security_sessions') }
    ]
  }
].map((group) => ({
  ...group,
  items: group.items.map((item) => ({
    ...item,
    icon: navIcon(item.to),
    hint: navHint(item.to)
  }))
})))

const currentSection = computed(() => {
  const flatItems = navGroups.value.flatMap((group) => group.items)
  return flatItems.find((item) => route.path.startsWith(item.to))?.label ?? t('login')
})

async function logout() {
  await auth.logout()
  await router.push('/login')
}

function navIcon(path: string) {
  return ({
    '/dashboard': '⌂',
    '/reports': '▦',
    '/notifications': '!',
    '/accounts': '$',
    '/categories': '#',
    '/receipts': '□',
    '/transactions': '↕',
    '/offline-sync': '↻',
    '/settings': '⚙',
    '/security': '✓'
  } as Record<string, string>)[path] ?? '•'
}

function navHint(path: string) {
  return ({
    '/dashboard': t('nav_dashboard_hint'),
    '/reports': t('nav_reports_hint'),
    '/notifications': t('nav_notifications_hint'),
    '/accounts': t('nav_accounts_hint'),
    '/categories': t('nav_categories_hint'),
    '/receipts': t('nav_receipts_hint'),
    '/transactions': t('nav_transactions_hint'),
    '/offline-sync': t('nav_sync_hint'),
    '/settings': t('nav_settings_hint'),
    '/security': t('nav_security_hint')
  } as Record<string, string>)[path] ?? ''
}
</script>
