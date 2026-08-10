<template>
  <section class="panel">
    <h2>{{ t('audit_and_backups') }}</h2>
    <div class="actions-row">
      <button class="button" type="button" @click="$emit('create-backup')">{{ t('create_backup') }}</button>
      <button class="button button-secondary" type="button" @click="$emit('refresh-operations')">{{ t('refresh_operations') }}</button>
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
import type { AuditLog, BackupLog } from '../../types/dashboard'
import { useLocaleStore } from '../../stores/locale'
import { translate } from '../../i18n'

defineProps<{ backups: BackupLog[]; auditLogs: AuditLog[] }>()
defineEmits<{ 'create-backup': []; 'refresh-operations': []; 'restore-backup': [backupId: number] }>()
const locale = useLocaleStore()

function t(key: string, params: Record<string, string | number> = {}) {
  return translate(locale.locale, key, params)
}
</script>
