import { get, isRecord, unexpectedResponse } from '../api/httpClient'
import type { ProductResponseDto } from '../types/dto'

function isProduct(value: unknown): value is ProductResponseDto {
  return (
    isRecord(value) &&
    typeof value.code === 'string' &&
    typeof value.name === 'string' &&
    Number.isInteger(value.priceInCents)
  )
}

export async function getProducts(signal: AbortSignal): Promise<ProductResponseDto[]> {
  const productsResponse = await get('/products', signal)

  if (!Array.isArray(productsResponse) || !productsResponse.every(isProduct)) {
    throw unexpectedResponse()
  }

  return productsResponse
}
