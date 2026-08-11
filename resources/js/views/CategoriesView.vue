<template>
  <section class="panel">
    <div class="history-header">
      <div>
        <h2>{{ t('categories') }}</h2>
        <p class="lead">{{ t('active_household') }} #{{ householdsStore.activeHouseholdId || '-' }}</p>
      </div>
      <button class="button button-secondary" type="button" @click="loadCategories">{{ t('refresh') }}</button>
    </div>

    <div class="filters-grid">
      <label class="field">
        <span>{{ t('name') }}</span>
        <input v-model="form.name" type="text" :placeholder="t('category')" />
      </label>
      <label class="field">
        <span>{{ t('type') }}</span>
        <select v-model="form.type">
          <option value="expense">{{ t('expense') }}</option>
          <option value="income">{{ t('income') }}</option>
          <option value="transfer">{{ t('transfer') }}</option>
          <option value="refund">{{ t('refund') }}</option>
        </select>
      </label>
      <label class="field">
        <span>{{ t('category') }}</span>
        <select v-model="form.parent_id">
          <option value="">{{ t('not_available') }}</option>
          <option v-for="category in categories" :key="category.id" :value="String(category.id)">
            {{ category.name }}
          </option>
        </select>
      </label>
    </div>

    <div class="actions-row">
      <label class="check-row"><input v-model="form.is_active" type="checkbox" /> {{ t('active') }}</label>
      <button class="button" type="button" @click="createCategory">{{ t('create_category') }}</button>
    </div>
  </section>

  <section class="panel">
    <div class="history-list" v-if="categories.length">
      <article v-for="category in categories" :key="category.id" class="history-row">
        <div>
          <strong>{{ category.name }}</strong>
          <div class="token-meta">{{ category.type }}</div>
        </div>
        <div class="history-metrics">
          <span v-if="category.parent_id">{{ t('parent_category') }}: {{ parentCategoryName(category.parent_id) }}</span>
          <span v-if="category.is_active">{{ t('active') }}</span>
        </div>
      </article>
    </div>
    <p v-else class="lead">{{ t('no_categories') }}</p>
  </section>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useCategories } from '../composables/useCategories'
import { translate } from '../i18n'
import { useHouseholdStore } from '../stores/household'
import { useLocaleStore } from '../stores/locale'

const householdsStore = useHouseholdStore()
const locale = useLocaleStore()
const { categories, form, loadCategories, createCategory } = useCategories()

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

function parentCategoryName(id: number) {
  return categories.value.find((category) => category.id === id)?.name ?? String(id)
}

onMounted(loadCategories)
watch(() => householdsStore.activeHouseholdId, loadCategories)
</script>
