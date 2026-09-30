import { get } from '../api/httpClient'
import type { OfferResponseDto } from '../types/dto'

export async function getOffers(signal: AbortSignal): Promise<OfferResponseDto[]> {
  const offersResponse = await get<OfferResponseDto[]>('/offers', signal)

  return offersResponse
}
