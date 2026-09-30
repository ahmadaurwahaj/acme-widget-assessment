import styles from './App.module.scss'
import { BasketPanel } from './components/BasketPanel/BasketPanel'
import { OfferBanner } from './components/OfferBanner/OfferBanner'
import { ProductList } from './components/ProductList/ProductList'
import { SiteHeader } from './components/SiteHeader/SiteHeader'
import { useBasket } from './hooks/useBasket'
import { useRemoteData } from './hooks/useRemoteData'
import { getOffers } from './services/offerService'
import { getProducts } from './services/productService'

export default function App() {
  const products = useRemoteData(getProducts)
  const offers = useRemoteData(getOffers)
  const { basket, isUpdating, isFull, addProduct, removeProduct, clearBasket } = useBasket()
  const itemCount = basket.productCodes.length

  return (
    <>
      <SiteHeader itemCount={itemCount} />

      <main className={styles.layout}>
        {offers.status === 'loaded' && <OfferBanner offers={offers.response} />}

        <section aria-labelledby="products-heading">
          <div className={styles.sectionHeader}>
            <h2 id="products-heading" className={styles.sectionTitle}>
              Products
            </h2>
            <p className={styles.muted}>Pick a widget and add it to your basket.</p>
          </div>

          {products.status === 'loading' && <p className={styles.muted}>Loading products…</p>}
          {products.status === 'failed' && (
            <p className={styles.error} role="alert">
              {products.message}
            </p>
          )}
          {products.status === 'loaded' && (
            <ProductList
              products={products.response}
              isBasketFull={isFull}
              onAddProduct={addProduct}
            />
          )}
        </section>

        <BasketPanel
          basket={basket}
          isUpdating={isUpdating}
          isFull={isFull}
          onAddProduct={addProduct}
          onRemoveProduct={removeProduct}
          onClearBasket={clearBasket}
        />
      </main>
    </>
  )
}
