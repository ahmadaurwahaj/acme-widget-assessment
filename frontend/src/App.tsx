import styles from './App.module.scss'
import { BasketPanel } from './components/BasketPanel/BasketPanel'
import { ProductList } from './components/ProductList/ProductList'
import { useBasket } from './hooks/useBasket'
import { useProducts } from './hooks/useProducts'

export default function App() {
  const products = useProducts()
  const { basket, isUpdating, addProduct, clearBasket } = useBasket()

  return (
    <main className={styles.layout}>
      <header className={styles.header}>
        <h1>Acme Widget Co</h1>
        <p className={styles.muted}>Sales basket proof of concept</p>
      </header>

      <section aria-labelledby="products-heading">
        <h2 id="products-heading">Products</h2>

        {products.status === 'loading' && <p className={styles.muted}>Loading products…</p>}
        {products.status === 'failed' && (
          <p className={styles.error} role="alert">
            {products.message}
          </p>
        )}
        {products.status === 'loaded' && (
          <ProductList products={products.response} onAddProduct={addProduct} />
        )}
      </section>

      <BasketPanel basket={basket} isUpdating={isUpdating} onClearBasket={clearBasket} />
    </main>
  )
}
