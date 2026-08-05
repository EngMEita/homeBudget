<template>
  <section class="panel">
    <h2>{{ t('household_members') }}</h2>
    <div class="filters-grid">
      <label class="field">
        <span>{{ t('email') }}</span>
        <input v-model="model.email" type="email" :placeholder="t('email_placeholder')" />
      </label>
      <label class="field">
        <span>{{ t('role') }}</span>
        <select v-model="model.role">
          <option value="administrator">{{ t('administrator') }}</option>
          <option value="contributor">{{ t('contributor') }}</option>
          <option value="viewer">{{ t('viewer') }}</option>
          <option value="restricted">{{ t('restricted') }}</option>
        </select>
      </label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('invite-member')">{{ t('send_invitation') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('refresh-members')">{{ t('refresh_members') }}</button>
    </div>

    <div class="history-list">
      <article v-for="member in members" :key="member.user_id" class="history-row">
        <div>
          <strong>{{ member.name }}</strong>
          <div class="token-meta">{{ member.email }}</div>
        </div>
        <div class="history-metrics">
          <span>{{ t(member.role) }}</span>
          <span v-if="member.can_create_transactions">{{ t('can_create') }}</span>
          <span v-if="member.can_view_transactions">{{ t('can_view') }}</span>
        </div>
      </article>
    </div>

    <div class="history-list" v-if="invitations.length">
      <h3>{{ t('pending_invitations') }}</h3>
      <article v-for="invitation in invitations" :key="invitation.id" class="history-row">
        <div>
          <strong>{{ invitation.email }}</strong>
          <div class="token-meta">{{ t(invitation.role) }}</div>
        </div>
        <div class="history-metrics">
          <span>{{ invitation.accepted_at ? t('accepted') : t('pending') }}</span>
          <span>{{ invitation.token }}</span>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Invitation, Member } from '../../types/dashboard'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'

defineProps<{ members: Member[]; invitations: Invitation[] }>()
defineEmits<{ 'invite-member': []; 'refresh-members': [] }>()
const model = defineModel<{ email: string; role: string }>({ required: true })
const locale = useLocaleStore()

function t(key: string) {
  return translate(locale.locale, key)
}
</script>
