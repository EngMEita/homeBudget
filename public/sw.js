self.addEventListener('install', (event) => {
  self.skipWaiting()
})

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim())
})

self.addEventListener('sync', (event) => {
  if (event.tag === 'homebudget-sync') {
    event.waitUntil(notifyClients())
  }
})

self.addEventListener('message', (event) => {
  if (event.data?.type === 'HOMEBUDGET_SYNC_NOW') {
    event.waitUntil(notifyClients())
  }
})

async function notifyClients() {
  const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
  clients.forEach((client) => client.postMessage({ type: 'HOMEBUDGET_SYNC_NOW' }))
}
