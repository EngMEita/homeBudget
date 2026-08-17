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
          <input v-if="editingId === category.id" v-model="category.name" class="inline-input" type="text" />
          <strong v-else>{{ category.name }}</strong>
          <div class="token-meta">{{ category.type }}</div>
        </div>
        <div class="history-metrics">
          <span v-if="category.parent_id">{{ t('parent_category') }}: {{ parentCategoryName(category.parent_id) }}</span>
          <span v-if="category.is_active">{{ t('active') }}</span>
          <button v-if="editingId !== category.id" class="button button-secondary" type="button" @click="editingId = category.id">{{ t('edit') }}</button>
          <button v-if="editingId === category.id" class="button" type="button" @click="saveCategory(category); editingId = null">{{ t('save') }}</button>
          <button v-if="editingId === category.id" class="button button-secondary" type="button" @click="loadCategories(); editingId = null">{{ t('cancel') }}</button>
          <button class="button button-danger" type="button" @click="removeCategory(category.id)">{{ t('delete') }}</button>
        </div>
      </article>
    </div>
    <p v-else class="lead">{{ t('no_categories') }}</p>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useCategories } from '../composables/useCategories'
import { translate } from '../i18n'
import { useHouseholdStore } from '../stores/household'
import { useLocaleStore } from '../stores/locale'

const householdsStore = useHouseholdStore()
const locale = useLocaleStore()
const { categories, form, loadCategories, createCategory, saveCategory, deleteCategory } = useCategories()
const editingId = ref<number | null>(null)

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}

function parentCategoryName(id: number) {
  return categories.value.find((category) => category.id === id)?.name ?? String(id)
}

async function removeCategory(id: number) {
  if (!window.confirm(t('confirm_delete'))) return
  const category = categories.value.find((item) => item.id === id)
  if (category) await deleteCategory(category)
}

onMounted(loadCategories)
watch(() => householdsStore.activeHouseholdId, loadCategories)
</script>
