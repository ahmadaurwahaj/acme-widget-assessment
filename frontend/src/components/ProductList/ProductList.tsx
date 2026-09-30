import type { ProductResponseDto } from '../../types/dto'
import { formatCents } from '../../utils/money'
import { CartIcon } from '../icons/icons'
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
          <div className={styles.tile} data-product={product.code} aria-hidden="true">
            <span className={styles.cube} />
          </div>

          <div className={styles.info}>
            <h3 className={styles.name}>{product.name}</h3>
            <span className={styles.code}>{product.code}</span>
          </div>

          <div className={styles.buy}>
            <span className={styles.price}>{formatCents(product.priceInCents)}</span>

            <button
              type="button"
              className={styles.addButton}
              onClick={() => onAddProduct(product.code)}
              aria-label={`Add ${product.name} to basket`}
            >
              <CartIcon size={16} />
              Add
            </button>
          </div>
        </li>
      ))}
    </ul>
  )
}
