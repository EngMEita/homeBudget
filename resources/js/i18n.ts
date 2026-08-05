import en from './locales/en/common.json'
import ar from './locales/ar/common.json'

const dictionaries: Record<string, Record<string, string>> = { en, ar }

export function translate(locale: string, key: string): string {
  return dictionaries[locale]?.[key] ?? dictionaries.en[key] ?? key
}
