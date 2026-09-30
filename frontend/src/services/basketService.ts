import { isRecord, post, unexpectedResponse } from '../api/httpClient'
import type {
  BasketLineResponseDto,
  BasketSummaryResponseDto,
  PriceBasketRequestDto,
} from '../types/dto'

function isBasketLine(value: unknown): value is BasketLineResponseDto {
  return (
    isRecord(value) &&
    typeof value.code === 'string' &&
    typeof value.name === 'string' &&
    Number.isInteger(value.quantity) &&
    Number.isInteger(value.unitPriceInCents) &&
    Number.isInteger(value.lineTotalInCents)
  )
}

function isBasketSummary(value: unknown): value is BasketSummaryResponseDto {
  return (
    isRecord(value) &&
    Array.isArray(value.lines) &&
    value.lines.every(isBasketLine) &&
    Number.isInteger(value.subtotalInCents) &&
    Number.isInteger(value.discountInCents) &&
    Number.isInteger(value.deliveryInCents) &&
    Number.isInteger(value.totalInCents)
  )
}

export async function getBasketTotal(productCodes: string[]): Promise<BasketSummaryResponseDto> {
  const requestBody: PriceBasketRequestDto = { productCodes }
  const basketTotalResponse = await post('/basket/total', requestBody)

  if (!isBasketSummary(basketTotalResponse)) {
    throw unexpectedResponse()
  }

  return basketTotalResponse
}
