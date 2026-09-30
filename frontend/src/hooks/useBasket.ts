import { startTransition, useActionState } from 'react'
import { toErrorMessage } from '../api/httpClient'
import { getBasketTotal } from '../services/basketService'
import type { BasketSummaryResponseDto } from '../types/dto'

export type BasketState = {
  productCodes: string[]
  summaryResponse: BasketSummaryResponseDto | null
  errorMessage: string | null
}

type ProductAction = { type: 'add' | 'remove'; productCode: string }

type BasketAction = ProductAction | { type: 'clear' }

export type UseBasketResult = {
  basket: BasketState
  isUpdating: boolean
  isFull: boolean
  addProduct: (productCode: string) => void
  removeProduct: (productCode: string) => void
  clearBasket: () => void
}

const MAX_BASKET_ITEMS = 100

const emptyBasket: BasketState = { productCodes: [], summaryResponse: null, errorMessage: null }

export async function updateBasket(
  previous: BasketState,
  action: BasketAction,
): Promise<BasketState> {
  if (action.type === 'clear') {
    return emptyBasket
  }

  if (action.type === 'add' && previous.productCodes.length >= MAX_BASKET_ITEMS) {
    return {
      ...previous,
      errorMessage: `A basket cannot hold more than ${MAX_BASKET_ITEMS} items.`,
    }
  }

  const productCodes = nextProductCodes(previous.productCodes, action)

  if (productCodes.length === 0) {
    return emptyBasket
  }

  try {
    const summaryResponse = await getBasketTotal(productCodes)

    return { productCodes, summaryResponse, errorMessage: null }
  } catch (error) {
    return { ...previous, errorMessage: toErrorMessage(error) }
  }
}

function nextProductCodes(productCodes: string[], action: ProductAction): string[] {
  if (action.type === 'add') {
    return [...productCodes, action.productCode]
  }

  const lastIndex = productCodes.lastIndexOf(action.productCode)

  return productCodes.filter((_, index) => index !== lastIndex)
}

export function useBasket(): UseBasketResult {
  const [basket, dispatch, isUpdating] = useActionState(updateBasket, emptyBasket)
  const isFull = basket.productCodes.length >= MAX_BASKET_ITEMS

  function addProduct(productCode: string): void {
    startTransition(() => {
      dispatch({ type: 'add', productCode })
    })
  }

  function removeProduct(productCode: string): void {
    startTransition(() => {
      dispatch({ type: 'remove', productCode })
    })
  }

  function clearBasket(): void {
    startTransition(() => {
      dispatch({ type: 'clear' })
    })
  }

  return { basket, isUpdating, isFull, addProduct, removeProduct, clearBasket }
}
