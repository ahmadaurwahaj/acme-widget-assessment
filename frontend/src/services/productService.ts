import { get } from '../api/httpClient'
import type { ProductResponseDto } from '../types/dto'

export async function getProducts(signal: AbortSignal): Promise<ProductResponseDto[]> {
  const productsResponse = await get<ProductResponseDto[]>('/products', signal)

  return productsResponse
}
