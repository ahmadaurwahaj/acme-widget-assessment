import type { ProductResponseDto } from '../../types/dto'
import { formatCents } from '../../utils/money'
import styles from './ProductList.module.scss'

type ProductListProps = {
  products: ProductResponseDto[]
  onAddProduct: (productCode: string) => void
}

export function ProductList({ products, onAddProduct }: ProductListProps) {
  return (
    <ul className={styles.list}>
      {products.map((product) => (
        <li key={product.code} className={styles.card}>
          <span className={styles.swatch} data-product={product.code} aria-hidden="true" />

          <div className={styles.info}>
            <span className={styles.name}>{product.name}</span>
            <span className={styles.code}>{product.code}</span>
          </div>

          <span className={styles.price}>{formatCents(product.priceInCents)}</span>

          <button
            type="button"
            className={styles.addButton}
            onClick={() => onAddProduct(product.code)}
            aria-label={`Add ${product.name} to basket`}
          >
            Add
          </button>
        </li>
      ))}
    </ul>
  )
}
