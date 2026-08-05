import en from './locales/en/common.json'
import ar from './locales/ar/common.json'

const dictionaries: Record<string, Record<string, string>> = { en, ar }

export function translate(locale: string, key: string, params: Record<string, string | number> = {}): string {
  const value = dictionaries[locale]?.[key] ?? dictionaries.en[key] ?? key

  return Object.entries(params).reduce(
    (text, [name, replacement]) => text.replaceAll(`{${name}}`, String(replacement)),
    value
  )
}
