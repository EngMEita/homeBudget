<template>
  <section class="panel">
    <h2>Household members</h2>
    <div class="filters-grid">
      <label class="field">
        <span>Email</span>
        <input v-model="model.email" type="email" placeholder="spouse@example.com" />
      </label>
      <label class="field">
        <span>Role</span>
        <select v-model="model.role">
          <option value="administrator">Administrator</option>
          <option value="contributor">Contributor</option>
          <option value="viewer">Viewer</option>
          <option value="restricted">Restricted</option>
        </select>
      </label>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('invite-member')">Send invitation</button>
      <button class="button button-secondary" type="button" @click="$emit('refresh-members')">Refresh members</button>
    </div>

    <div class="history-list">
      <article v-for="member in members" :key="member.user_id" class="history-row">
        <div>
          <strong>{{ member.name }}</strong>
          <div class="token-meta">{{ member.email }}</div>
        </div>
        <div class="history-metrics">
          <span>{{ member.role }}</span>
          <span v-if="member.can_create_transactions">can create</span>
          <span v-if="member.can_view_transactions">can view</span>
        </div>
      </article>
    </div>

    <div class="history-list" v-if="invitations.length">
      <h3>Pending invitations</h3>
      <article v-for="invitation in invitations" :key="invitation.id" class="history-row">
        <div>
          <strong>{{ invitation.email }}</strong>
          <div class="token-meta">{{ invitation.role }}</div>
        </div>
        <div class="history-metrics">
          <span>{{ invitation.accepted_at ? 'accepted' : 'pending' }}</span>
          <span>{{ invitation.token }}</span>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Invitation, Member } from '../../types/dashboard'

defineProps<{ members: Member[]; invitations: Invitation[] }>()
defineEmits<{ 'invite-member': []; 'refresh-members': [] }>()
const model = defineModel<{ email: string; role: string }>({ required: true })
</script>
