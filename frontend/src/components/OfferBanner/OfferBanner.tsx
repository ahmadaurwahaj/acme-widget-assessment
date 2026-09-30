import type { OfferResponseDto } from '../../types/dto'
import { TagIcon } from '../icons/icons'
import styles from './OfferBanner.module.scss'

type OfferBannerProps = {
  offers: OfferResponseDto[]
}

export function OfferBanner({ offers }: OfferBannerProps) {
  if (offers.length === 0) {
    return null
  }

  return (
    <aside className={styles.banner} aria-label="Special offers">
      <div className={styles.content}>
        <span className={styles.label}>
          <TagIcon />
          Special offer
        </span>

        <ul className={styles.offers}>
          {offers.map((offer) => (
            <li key={offer.code}>{offer.description}</li>
          ))}
        </ul>
      </div>

      <div className={styles.art} aria-hidden="true">
        <span className={styles.cubeRed} />
        <span className={styles.cubeGreen} />
        <span className={styles.cubeBlue} />
      </div>
    </aside>
  )
}
