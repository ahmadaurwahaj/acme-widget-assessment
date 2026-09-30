import { post } from '../api/httpClient'
import type { BasketSummaryResponseDto, PriceBasketRequestDto } from '../types/dto'

export async function getBasketTotal(productCodes: string[]): Promise<BasketSummaryResponseDto> {
  const requestBody: PriceBasketRequestDto = { productCodes }
  const basketTotalResponse = await post<BasketSummaryResponseDto>('/basket/total', requestBody)

  return basketTotalResponse
}
