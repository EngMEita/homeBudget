export function decimalToMinor(value: string | number): number {
  const normalized = String(value).trim().replace(/,/g, '')
  if (!normalized) return 0

  const sign = normalized.startsWith('-') ? -1 : 1
  const unsigned = normalized.replace(/^-/, '')
  const [wholePart, fractionPart = ''] = unsigned.split('.')
  const whole = Number.parseInt(wholePart || '0', 10)
  const fraction = Number.parseInt(fractionPart.padEnd(2, '0').slice(0, 2) || '0', 10)

  return sign * (whole * 100 + fraction)
}

export function minorToDecimal(value: number | null | undefined): string {
  const minor = Number(value ?? 0)
  const sign = minor < 0 ? '-' : ''
  const absolute = Math.abs(minor)
  const whole = Math.floor(absolute / 100)
  const fraction = String(absolute % 100).padStart(2, '0')

  return `${sign}${whole}.${fraction}`
}
