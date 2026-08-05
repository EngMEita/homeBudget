import fs from 'node:fs'
import path from 'node:path'

const root = process.cwd()
const locales = {
  en: JSON.parse(fs.readFileSync(path.join(root, 'resources/js/locales/en/common.json'), 'utf8')),
  ar: JSON.parse(fs.readFileSync(path.join(root, 'resources/js/locales/ar/common.json'), 'utf8'))
}

const enKeys = Object.keys(locales.en).sort()
const arKeys = Object.keys(locales.ar).sort()
const missingInAr = enKeys.filter((key) => !arKeys.includes(key))
const missingInEn = arKeys.filter((key) => !enKeys.includes(key))

if (missingInAr.length || missingInEn.length) {
  console.error('Locale dictionaries are out of sync.')
  if (missingInAr.length) console.error(`Missing in ar: ${missingInAr.join(', ')}`)
  if (missingInEn.length) console.error(`Missing in en: ${missingInEn.join(', ')}`)
  process.exit(1)
}

console.log(`Locale dictionaries are synchronized (${enKeys.length} keys).`)
