<template>
  <section class="panel">
    <div class="history-header">
      <div>
        <h2>{{ t('accounts') }}</h2>
        <p class="lead">{{ t('active_household') }} #{{ householdsStore.activeHouseholdId || '-' }}</p>
      </div>
      <button class="button button-secondary" type="button" @click="loadAccounts">{{ t('refresh') }}</button>
    </div>

    <div class="filters-grid">
      <label class="field">
        <span>{{ t('account_name') }}</span>
        <input v-model="form.name" type="text" :placeholder="t('account_name')" />
      </label>
      <label class="field">
        <span>{{ t('account_type') }}</span>
        <select v-model="form.account_type_id">
          <option v-for="type in accountTypes" :key="type.id" :value="String(type.id)">{{ type.name }}</option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('currency') }}</span>
        <select v-model="form.currency_id">
          <option v-for="currency in currencies" :key="currency.id" :value="String(currency.id)">
            {{ currency.code }} - {{ locale.locale === 'ar' ? currency.name_ar : currency.name_en }}
          </option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('opening_balance') }}</span>
        <input v-model="form.opening_balance_minor" type="number" step="0.01" placeholder="1500.25" />
      </label>
    </div>

    <div class="actions-row">
      <label class="check-row"><input v-model="form.is_shared" type="checkbox" /> {{ t('shared') }}</label>
      <label class="check-row"><input v-model="form.is_active" type="checkbox" /> {{ t('active') }}</label>
      <button class="button" type="button" @click="createAccount">{{ t('create_account') }}</button>
    </div>
  </section>

  <section class="panel">
    <div class="history-list" v-if="accounts.length">
      <article v-for="account in accounts" :key="account.id" class="history-row">
        <div>
          <input v-if="editingId === account.id" v-model="account.name" class="inline-input" type="text" />
          <RouterLink v-else :to="`/accounts/${account.id}`"><strong>{{ account.name }}</strong></RouterLink>
          <div class="token-meta">{{ account.account_type_name }} · {{ account.currency_code }}</div>
        </div>
        <div class="history-metrics">
          <span>{{ t('opening_balance') }}: {{ minorToDecimal(account.opening_balance_minor) }}</span>
          <span v-if="account.is_shared">{{ t('shared') }}</span>
          <span v-if="account.is_active">{{ t('active') }}</span>
          <button class="button button-secondary" type="button" @click="editAccount(account.id)">{{ editingId === account.id ? t('save') : t('edit') }}</button>
          <button class="button button-danger" type="button" @click="removeAccount(account.id)">{{ t('delete') }}</button>
        </div>
      </article>
    </div>
    <p v-else class="lead">{{ t('no_accounts') }}</p>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useAccounts } from '../composables/useAccounts'
import { translate } from '../i18n'
import { minorToDecimal } from '../money'
import { useHouseholdStore } from '../stores/household'
import { useLocaleStore } from '../stores/locale'

const householdsStore = useHouseholdStore()
const locale = useLocaleStore()
const { accounts, accountTypes, currencies, form, loadAccounts, createAccount, updateAccount, deleteAccount } = useAccounts()
const editingId = ref<number | null>(null)

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

async function editAccount(id: number) {
  const account = accounts.value.find((item) => item.id === id)
  if (!account) return
  if (editingId.value === id) { await updateAccount(account); editingId.value = null } else editingId.value = id
}

async function removeAccount(id: number) {
  if (!window.confirm(t('confirm_delete'))) return
  const account = accounts.value.find((item) => item.id === id)
  if (account) await deleteAccount(account)
}

onMounted(loadAccounts)
watch(() => householdsStore.activeHouseholdId, loadAccounts)
</script>
