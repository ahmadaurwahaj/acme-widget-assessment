const usdFormatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })

export function formatCents(cents: number): string {
  const dollars = cents / 100
  const formatted = usdFormatter.format(dollars)

  return formatted
}
