<template>
  <section class="panel">
    <h2>Audit and backups</h2>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-backup')">Create SQLite backup</button>
      <button class="button button-secondary" type="button" @click="$emit('refresh-operations')">Refresh operations</button>
    </div>
    <div class="history-list">
      <article v-for="backup in backups" :key="`backup-${backup.id}`" class="history-row">
        <div>
          <strong>{{ backup.status }}</strong>
          <div class="token-meta">{{ backup.path ?? 'pending' }} · {{ backup.size_bytes }} bytes</div>
        </div>
      </article>
      <article v-for="log in auditLogs" :key="`audit-${log.id}`" class="history-row">
        <div>
          <strong>{{ log.event }}</strong>
          <div class="token-meta">{{ log.created_at }}</div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { AuditLog, BackupLog } from '../../types/dashboard'

defineProps<{ backups: BackupLog[]; auditLogs: AuditLog[] }>()
defineEmits<{ 'create-backup': []; 'refresh-operations': [] }>()
</script>
