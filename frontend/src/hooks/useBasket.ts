import { startTransition, useActionState } from 'react'
import { toErrorMessage } from '../api/httpClient'
import { getBasketTotal } from '../services/basketService'
import type { BasketSummaryResponseDto } from '../types/dto'

export type BasketState = {
  productCodes: string[]
  summaryResponse: BasketSummaryResponseDto | null
  errorMessage: string | null
}

type BasketAction = { type: 'add'; productCode: string } | { type: 'clear' }

export type UseBasketResult = {
  basket: BasketState
  isUpdating: boolean
  addProduct: (productCode: string) => void
  clearBasket: () => void
}

const emptyBasket: BasketState = { productCodes: [], summaryResponse: null, errorMessage: null }

async function updateBasket(previous: BasketState, action: BasketAction): Promise<BasketState> {
  if (action.type === 'clear') {
    return emptyBasket
  }

  const productCodes = [...previous.productCodes, action.productCode]

  try {
    const summaryResponse = await getBasketTotal(productCodes)
    const updatedBasket: BasketState = { productCodes, summaryResponse, errorMessage: null }

    return updatedBasket
  } catch (error) {
    const unchangedBasket: BasketState = { ...previous, errorMessage: toErrorMessage(error) }

    return unchangedBasket
  }
}

export function useBasket(): UseBasketResult {
  const [basket, dispatch, isUpdating] = useActionState(updateBasket, emptyBasket)

  function addProduct(productCode: string): void {
    startTransition(() => {
      dispatch({ type: 'add', productCode })
    })
  }

  function clearBasket(): void {
    startTransition(() => {
      dispatch({ type: 'clear' })
    })
  }

  return { basket, isUpdating, addProduct, clearBasket }
}
