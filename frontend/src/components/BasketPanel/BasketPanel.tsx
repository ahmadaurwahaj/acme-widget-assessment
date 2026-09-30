import type { BasketState } from '../../hooks/useBasket'
import type { BasketSummaryResponseDto } from '../../types/dto'
import { formatCents } from '../../utils/money'
import { BasketIcon, CartIcon, MinusIcon, PlusIcon } from '../icons/icons'
import styles from './BasketPanel.module.scss'

type BasketPanelProps = {
  basket: BasketState
  isUpdating: boolean
  onAddProduct: (productCode: string) => void
  onRemoveProduct: (productCode: string) => void
  onClearBasket: () => void
}

export function BasketPanel({
  basket,
  isUpdating,
  onAddProduct,
  onRemoveProduct,
  onClearBasket,
}: BasketPanelProps) {
  const hasItems = basket.productCodes.length > 0

  return (
    <section id="basket" className={styles.panel} aria-labelledby="basket-heading">
      <header className={styles.header}>
        <h2 id="basket-heading" className={styles.title}>
          <CartIcon size={20} />
          Basket
        </h2>

        {hasItems && (
          <button type="button" className={styles.clearButton} onClick={onClearBasket}>
            Clear
          </button>
        )}
      </header>

      {basket.errorMessage && (
        <p className={styles.error} role="alert">
          {basket.errorMessage}
        </p>
      )}

      <BasketContent
        summaryResponse={basket.summaryResponse}
        isUpdating={isUpdating}
        onAddProduct={onAddProduct}
        onRemoveProduct={onRemoveProduct}
      />
    </section>
  )
}

type BasketContentProps = {
  summaryResponse: BasketSummaryResponseDto | null
  isUpdating: boolean
  onAddProduct: (productCode: string) => void
  onRemoveProduct: (productCode: string) => void
}

function BasketContent({
  summaryResponse,
  isUpdating,
  onAddProduct,
  onRemoveProduct,
}: BasketContentProps) {
  if (summaryResponse) {
    return (
      <BasketSummary
        summaryResponse={summaryResponse}
        isUpdating={isUpdating}
        onAddProduct={onAddProduct}
        onRemoveProduct={onRemoveProduct}
      />
    )
  }

  if (isUpdating) {
    return <p className={styles.muted}>Calculating…</p>
  }

  return (
    <div className={styles.empty}>
      <div className={styles.emptyIcon}>
        <BasketIcon />
      </div>
      <p className={styles.emptyTitle}>Your basket is empty.</p>
      <p className={styles.muted}>Add some widgets to get started!</p>
    </div>
  )
}

type BasketSummaryProps = {
  summaryResponse: BasketSummaryResponseDto
  isUpdating: boolean
  onAddProduct: (productCode: string) => void
  onRemoveProduct: (productCode: string) => void
}

function BasketSummary({
  summaryResponse,
  isUpdating,
  onAddProduct,
  onRemoveProduct,
}: BasketSummaryProps) {
  const hasDiscount = summaryResponse.discountInCents > 0
  const deliveryLabel =
    summaryResponse.deliveryInCents === 0 ? 'Free' : formatCents(summaryResponse.deliveryInCents)

  return (
    <div className={styles.summary} aria-busy={isUpdating}>
      <ul className={styles.lines}>
        {summaryResponse.lines.map((line) => (
          <li key={line.code}>
            <span className={styles.lineName}>{line.name}</span>
            <span className={styles.stepper}>
              <button
                type="button"
                onClick={() => onRemoveProduct(line.code)}
                aria-label={`Remove one ${line.name}`}
              >
                <MinusIcon />
              </button>
              <span className={styles.quantity}>{line.quantity}</span>
              <button
                type="button"
                onClick={() => onAddProduct(line.code)}
                aria-label={`Add one ${line.name}`}
              >
                <PlusIcon />
              </button>
            </span>
            <span className={styles.lineTotal}>{formatCents(line.lineTotalInCents)}</span>
          </li>
        ))}
      </ul>

      <dl className={styles.totals}>
        <dt>Subtotal</dt>
        <dd>{formatCents(summaryResponse.subtotalInCents)}</dd>

        {hasDiscount && (
          <>
            <dt>Offer discount</dt>
            <dd className={styles.discount}>−{formatCents(summaryResponse.discountInCents)}</dd>
          </>
        )}

        <dt>Delivery</dt>
        <dd>{deliveryLabel}</dd>

        <dt className={styles.grandTotal}>Total</dt>
        <dd className={styles.grandTotal}>{formatCents(summaryResponse.totalInCents)}</dd>
      </dl>
    </div>
  )
}
