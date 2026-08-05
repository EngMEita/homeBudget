<template>
  <section class="panel">
    <div class="history-header">
      <div>
        <h2>{{ t('active_household') }}</h2>
        <p class="lead">{{ t('switch_households') }}</p>
      </div>
      <label class="field compact">
        <span>{{ t('language') }}</span>
        <select v-model="locale.locale" @change="locale.setLocale(locale.locale)">
          <option value="en">{{ t('english') }}</option>
          <option value="ar">{{ t('arabic') }}</option>
        </select>
      </label>
    </div>

    <div class="filters-grid">
      <label class="field">
        <span>{{ t('household') }}</span>
        <select :value="activeHouseholdId" @change="$emit('select-household', Number(($event.target as HTMLSelectElement).value))">
          <option :value="0">{{ t('select_household') }}</option>
          <option v-for="household in households" :key="household.id" :value="household.id">
            {{ household.name }} ({{ household.base_currency_code }})
          </option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('name') }}</span>
        <input v-model="model.name" type="text" :placeholder="t('family_budget_placeholder')" />
      </label>
      <label class="field">
        <span>{{ t('base_currency') }}</span>
        <input v-model="model.base_currency_code" type="text" maxlength="3" :placeholder="t('currency_placeholder')" />
      </label>
      <label class="field">
        <span>{{ t('default_locale') }}</span>
        <select v-model="model.default_locale">
          <option value="en">{{ t('english') }}</option>
          <option value="ar">{{ t('arabic') }}</option>
        </select>
      </label>
    </div>

    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-household')">{{ t('create_household') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('refresh-households')">{{ t('refresh_households') }}</button>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Household } from '../../types/dashboard'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'

defineProps<{
  households: Household[]
  activeHouseholdId: number
}>()

defineEmits<{
  'create-household': []
  'refresh-households': []
  'select-household': [id: number]
}>()

const model = defineModel<{ name: string; base_currency_code: string; default_locale: string }>({ required: true })
const locale = useLocaleStore()

function t(key: string) {
  return translate(locale.locale, key)
}
</script>
