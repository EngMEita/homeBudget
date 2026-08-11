<template>
  <section class="panel">
    <div class="panel-heading">
      <div>
        <p class="eyebrow">{{ t('workflow_operations') }}</p>
        <h2>{{ t('audit_and_backups') }}</h2>
        <p class="lead">{{ t('operations_reliability_hint') }}</p>
      </div>
      <span class="status-pill" :class="{ ready: health?.status === 'ok' }">{{ health?.status ?? t('not_available') }}</span>
    </div>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-backup')">{{ t('create_backup') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('refresh-operations')">{{ t('refresh_operations') }}</button>
    </div>
    <div v-if="health" class="stats-grid">
      <article v-for="check in health.checks" :key="check.name" class="stat-card">
        <span class="stat-label">{{ t(check.name) }}</span>
        <strong>{{ check.status }}</strong>
        <div class="token-meta">{{ check.message }}</div>
      </article>
    </div>
    <div class="history-list">
      <article v-for="backup in backups" :key="`backup-${backup.id}`" class="history-row">
        <div>
          <strong>{{ backup.status }}</strong>
          <div class="token-meta">{{ backup.path ?? t('pending') }} · {{ t('bytes_count', { count: backup.size_bytes }) }}</div>
        </div>
        <button v-if="backup.status === 'completed'" class="button button-secondary" type="button" @click="$emit('restore-backup', backup.id)">{{ t('restore_backup') }}</button>
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
import type { AuditLog, BackupLog, SystemHealth } from '../../types/dashboard'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'

defineProps<{ backups: BackupLog[]; auditLogs: AuditLog[]; health: SystemHealth | null }>()
defineEmits<{ 'create-backup': []; 'refresh-operations': []; 'restore-backup': [backupId: number] }>()
const locale = useLocaleStore()

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}
</script>
