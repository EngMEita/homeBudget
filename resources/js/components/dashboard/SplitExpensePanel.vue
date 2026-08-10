<template>
  <section class="panel">
    <h2>{{ t('split_payment') }}</h2>
    <p class="lead">{{ t('split_payment_hint') }}</p>
    <div class="form-grid">
      <label class="field"><span>{{ t('description') }}</span><input v-model="description" :placeholder="t('split_payment_description_placeholder')" /></label>
      <label class="field"><span>{{ t('amount') }}</span><input v-model="total" inputmode="decimal" type="number" min="0.01" step="0.01" /></label>
      <label class="field"><span>{{ t('currency') }}</span><select v-model="currencyId"><option v-for="currency in currencies" :key="currency.id" :value="String(currency.id)">{{ currency.code }}</option></select></label>
      <label class="field"><span>{{ t('date') }}</span><input v-model="date" type="date" /></label>
    </div>
    <div v-for="(leg, index) in legs" :key="index" class="form-grid split-leg">
      <label class="field"><span>{{ t('payment_source') }} {{ index + 1 }}</span><select v-model="leg.accountId"><option v-for="account in accountsForCurrency" :key="account.id" :value="String(account.id)">{{ account.name }}</option></select></label>
      <label class="field"><span>{{ t('amount') }}</span><input v-model="leg.amount" inputmode="decimal" type="number" min="0.01" step="0.01" /></label>
      <button v-if="legs.length > 2" class="button button-secondary" type="button" @click="legs.splice(index, 1)">{{ t('remove') }}</button>
    </div>
    <div class="actions-row"><strong>{{ t('remaining_amount', { amount: minorToDecimal(remainingMinor) }) }}</strong><button class="button button-secondary" type="button" @click="legs.push({ accountId: '', amount: '' })">{{ t('add_payment_source') }}</button><button class="button" type="button" :disabled="!canSubmit || saving" @click="submit">{{ saving ? t('saving') : t('save_expense') }}</button></div>
    <p v-if="error" class="error-message">{{ error }}</p>
  </section>
</template>
<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'
import { decimalToMinor, minorToDecimal } from '../../money'
import type { Account, CurrencyOption } from '../../composables/useAccounts'
const props = defineProps<{ householdId: number; accounts: Account[]; currencies: CurrencyOption[] }>()
const emit = defineEmits<{ saved: [] }>()
const auth = useAuthStore(); const locale = useLocaleStore(); const total = ref(''); const description = ref(''); const date = ref(new Date().toISOString().slice(0, 10)); const currencyId = ref(''); const saving = ref(false); const error = ref('')
const legs = ref([{ accountId: '', amount: '' }, { accountId: '', amount: '' }])
const currencies = computed(() => props.currencies.filter((currency) => props.accounts.some((account) => account.currency_id === currency.id)))
const accountsForCurrency = computed(() => props.accounts.filter((account) => String(account.currency_id) === currencyId.value && account.is_active))
const totalMinor = computed(() => decimalToMinor(total.value || '0')); const paidMinor = computed(() => legs.value.reduce((sum, leg) => sum + decimalToMinor(leg.amount || '0'), 0)); const remainingMinor = computed(() => totalMinor.value - paidMinor.value)
const canSubmit = computed(() => totalMinor.value > 0 && remainingMinor.value === 0 && legs.value.every((leg) => leg.accountId && decimalToMinor(leg.amount || '0') > 0) && Boolean(currencyId.value))
function t(key: string, params: Record<string, string | number> = {}) { return translate(locale.locale, key, params) }
async function submit() { saving.value = true; error.value = ''; const response = await fetch(`/api/households/${props.householdId}/transactions/split-expense`, { method: 'POST', headers: { 'Content-Type': 'application/json', ...auth.authHeaders() }, body: JSON.stringify({ currency_id: Number(currencyId.value), amount_minor: totalMinor.value, description: description.value || null, transaction_date: date.value, payment_legs: legs.value.map((leg) => ({ account_id: Number(leg.accountId), amount_minor: decimalToMinor(leg.amount) })) }) }); if (response.ok) { total.value = ''; description.value = ''; legs.value = [{ accountId: '', amount: '' }, { accountId: '', amount: '' }]; emit('saved') } else error.value = t('split_payment_failed'); saving.value = false }
</script>
