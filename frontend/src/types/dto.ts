export type ProductResponseDto = {
  code: string
  name: string
  priceInCents: number
}

export type BasketLineResponseDto = {
  code: string
  name: string
  quantity: number
  unitPriceInCents: number
  lineTotalInCents: number
}

export type BasketSummaryResponseDto = {
  lines: BasketLineResponseDto[]
  subtotalInCents: number
  discountInCents: number
  deliveryInCents: number
  totalInCents: number
}

export type PriceBasketRequestDto = {
  productCodes: string[]
}

export type ErrorResponseDto = {
  error: string
}

export type OfferResponseDto = {
  description: string
}
