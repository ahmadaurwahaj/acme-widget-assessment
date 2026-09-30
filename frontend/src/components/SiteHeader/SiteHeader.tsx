import { CartIcon } from '../icons/icons'
import styles from './SiteHeader.module.scss'

type SiteHeaderProps = {
  itemCount: number
}

export function SiteHeader({ itemCount }: SiteHeaderProps) {
  return (
    <header className={styles.header}>
      <div className={styles.inner}>
        <div className={styles.brand}>
          <span className={styles.logo} aria-hidden="true" />
          <div>
            <h1 className={styles.title}>Acme Widget Co</h1>
            <p className={styles.subtitle}>Sales basket proof of concept</p>
          </div>
        </div>

        <a href="#basket" className={styles.basketLink}>
          <CartIcon />
          Basket ({itemCount})
        </a>
      </div>
    </header>
  )
}
