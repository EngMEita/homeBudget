<template>
  <section class="panel">
    <div class="panel-heading">
      <div>
        <p class="eyebrow">{{ t('workflow_pay') }}</p>
        <h2>{{ t('split_payment') }}</h2>
        <p class="lead">{{ t('split_payment_hint') }}</p>
      </div>
      <span class="status-pill" :class="{ ready: canSubmit }">{{ canSubmit ? t('ready') : t('needs_input') }}</span>
    </div>

    <div v-if="!hasAccounts" class="empty-state">
      <strong>{{ t('no_payment_sources_ready') }}</strong>
      <p>{{ t('create_account_first_hint') }}</p>
    </div>

    <div v-else class="form-grid">
      <label class="field">
        <span>{{ t('description') }}</span>
        <input v-model="description" :placeholder="t('split_payment_description_placeholder')" />
      </label>
      <label class="field">
        <span>{{ t('amount') }}</span>
        <input v-model="total" inputmode="decimal" type="number" min="0.01" step="0.01" />
      </label>
      <label class="field">
        <span>{{ t('currency') }}</span>
        <select v-model="currencyId">
          <option v-for="currency in currencies" :key="currency.id" :value="String(currency.id)">
            {{ currency.code }}
          </option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('date') }}</span>
        <input v-model="date" type="date" />
      </label>
    </div>

    <div v-for="(leg, index) in legs" v-show="hasAccounts" :key="index" class="form-grid split-leg">
      <label class="field">
        <span>{{ t('source_currency') }}</span>
        <select v-model="leg.currencyId" @change="leg.accountId = ''; normalizeLegRate(leg)">
          <option v-for="currency in currencies" :key="currency.id" :value="String(currency.id)">
            {{ currency.code }}
          </option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('payment_source') }} {{ index + 1 }}</span>
        <select v-model="leg.accountId">
          <option value="">{{ t('select_account') }}</option>
          <option v-for="account in accountsForLeg(leg)" :key="account.id" :value="String(account.id)">
            {{ account.name }} · {{ account.currency_code ?? currencyCode(leg.currencyId) }}
          </option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('source_amount') }}</span>
        <input v-model="leg.amount" inputmode="decimal" type="number" min="0.01" step="0.01" />
      </label>
      <label class="field">
        <span>{{ t('exchange_rate') }}</span>
        <input v-model="leg.exchangeRate" :disabled="isParentCurrency(leg)" inputmode="decimal" type="number" min="0.000001" step="0.000001" />
      </label>
      <div class="field">
        <span>{{ t('base_amount') }}</span>
        <strong>{{ minorToDecimal(baseMinorForLeg(leg)) }} {{ selectedCurrencyCode }}</strong>
      </div>
      <button v-if="legs.length > 2" class="button button-secondary" type="button" @click="legs.splice(index, 1)">
        {{ t('remove') }}
      </button>
    </div>

    <div class="actions-row">
      <strong :class="{ 'error-text': remainingMinor !== 0 && totalMinor > 0 }">{{ statusMessage }}</strong>
      <button class="button button-secondary" type="button" :disabled="!hasAccounts" @click="addLeg">{{ t('add_payment_source') }}</button>
      <button class="button" type="button" :disabled="!canSubmit || saving" @click="submit">{{ saving ? t('saving') : t('save_expense') }}</button>
    </div>
    <p v-if="error" class="error-message">{{ error }}</p>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'
import { decimalToMinor, minorToDecimal } from '../../money'
import type { Account, CurrencyOption } from '../../composables/useAccounts'

type LegForm = { accountId: string; currencyId: string; amount: string; exchangeRate: string }

const props = defineProps<{ householdId: number; accounts: Account[]; currencies: CurrencyOption[] }>()
const emit = defineEmits<{ saved: [] }>()
const auth = useAuthStore()
const locale = useLocaleStore()
const total = ref('')
const description = ref('')
const date = ref(new Date().toISOString().slice(0, 10))
const currencyId = ref('')
const saving = ref(false)
const error = ref('')
const legs = ref<LegForm[]>([newLeg(), newLeg()])

const currencies = computed(() => props.currencies.filter((currency) => props.accounts.some((account) => account.currency_id === currency.id)))
const hasAccounts = computed(() => props.accounts.some((account) => account.is_active))
const selectedCurrencyCode = computed(() => currencyCode(currencyId.value))
const totalMinor = computed(() => decimalToMinor(total.value || '0'))
const paidBaseMinor = computed(() => legs.value.reduce((sum, leg) => sum + baseMinorForLeg(leg), 0))
const remainingMinor = computed(() => totalMinor.value - paidBaseMinor.value)
const canSubmit = computed(() => {
  return hasAccounts.value
    && totalMinor.value > 0
    && remainingMinor.value === 0
    && Boolean(currencyId.value)
    && legs.value.every((leg) => leg.accountId && decimalToMinor(leg.amount || '0') > 0 && validRate(leg))
})
const statusMessage = computed(() => {
  if (!hasAccounts.value) return t('create_account_first_hint')
  if (!currencyId.value) return t('select_currency_first')
  if (totalMinor.value <= 0) return t('enter_total_first')
  if (legs.value.some((leg) => !leg.accountId)) return t('select_all_payment_sources')
  if (legs.value.some((leg) => decimalToMinor(leg.amount || '0') <= 0)) return t('enter_all_source_amounts')
  if (legs.value.some((leg) => !validRate(leg))) return t('enter_exchange_rates')
  return t('remaining_amount', { amount: `${minorToDecimal(remainingMinor.value)} ${selectedCurrencyCode.value}` })
})

watch(currencies, (available) => {
  if (!currencyId.value && available[0]) currencyId.value = String(available[0].id)
  for (const leg of legs.value) {
    if (!leg.currencyId && available[0]) leg.currencyId = String(available[0].id)
    normalizeLegRate(leg)
  }
}, { immediate: true })

watch(currencyId, () => {
  for (const leg of legs.value) normalizeLegRate(leg)
})

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

function newLeg(): LegForm {
  return { accountId: '', currencyId: currencyId.value, amount: '', exchangeRate: '1' }
}

function addLeg() {
  legs.value.push(newLeg())
}

function currencyCode(id: string) {
  return props.currencies.find((currency) => String(currency.id) === id)?.code ?? ''
}

function accountsForLeg(leg: LegForm) {
  return props.accounts.filter((account) => String(account.currency_id) === leg.currencyId && account.is_active)
}

function isParentCurrency(leg: LegForm) {
  return leg.currencyId === currencyId.value
}

function normalizeLegRate(leg: LegForm) {
  if (isParentCurrency(leg)) leg.exchangeRate = '1'
}

function validRate(leg: LegForm) {
  return isParentCurrency(leg) || Number(leg.exchangeRate) > 0
}

function baseMinorForLeg(leg: LegForm) {
  const amountMinor = decimalToMinor(leg.amount || '0')
  if (amountMinor <= 0) return 0
  if (isParentCurrency(leg)) return amountMinor
  return Math.round(amountMinor * Number(leg.exchangeRate || 0))
}

async function submit() {
  saving.value = true
  error.value = ''
  const response = await fetch(`/api/households/${props.householdId}/transactions/split-expense`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...auth.authHeaders() },
    body: JSON.stringify({
      currency_id: Number(currencyId.value),
      amount_minor: totalMinor.value,
      base_amount_minor: totalMinor.value,
      description: description.value || null,
      transaction_date: date.value,
      payment_legs: legs.value.map((leg) => ({
        account_id: Number(leg.accountId),
        amount_minor: decimalToMinor(leg.amount),
        base_amount_minor: baseMinorForLeg(leg),
        exchange_rate: isParentCurrency(leg) ? null : leg.exchangeRate,
        exchange_rate_source: isParentCurrency(leg) ? null : 'manual',
        exchange_rate_date: isParentCurrency(leg) ? null : date.value
      }))
    })
  })

  if (response.ok) {
    total.value = ''
    description.value = ''
    legs.value = [newLeg(), newLeg()]
    emit('saved')
  } else {
    error.value = t('split_payment_failed')
  }
  saving.value = false
}
</script>
