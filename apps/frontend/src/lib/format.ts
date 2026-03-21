export function formatMoney(value: number | string | null | undefined, suffix = ' đ'): string {
  if (value === null || value === undefined || value === '') return `0${suffix}`
  const amount = Number(String(value).replace(/[^0-9-]/g, '')) || 0
  return `${new Intl.NumberFormat('vi-VN').format(amount)}${suffix}`
}

export function formatDate(value: string | Date | null | undefined): string {
  if (!value) return ''
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return new Intl.DateTimeFormat('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(date)
}

export function formatRelativeTime(value: string | Date | null | undefined): string {
  if (!value) return ''
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return ''

  const diffMs = date.getTime() - Date.now()
  const diffMin = Math.round(diffMs / 60000)
  const rtf = new Intl.RelativeTimeFormat('vi', { numeric: 'auto' })

  if (Math.abs(diffMin) < 60) return rtf.format(diffMin, 'minute')
  const diffHour = Math.round(diffMin / 60)
  if (Math.abs(diffHour) < 24) return rtf.format(diffHour, 'hour')
  const diffDay = Math.round(diffHour / 24)
  return rtf.format(diffDay, 'day')
}
