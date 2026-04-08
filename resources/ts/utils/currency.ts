/**
 * Format a number as Iraqi Dinar
 * e.g. 13000 → "13,000 د.ع"
 */
export const formatIQD = (value: number | string | null | undefined): string => {
  if (value === null || value === undefined || value === '') return '—'
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num)) return '—'
  return num.toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' د.ع'
}
