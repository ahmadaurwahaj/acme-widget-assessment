import { get, isRecord, unexpectedResponse } from '../api/httpClient'
import type { OfferResponseDto } from '../types/dto'

function isOffer(value: unknown): value is OfferResponseDto {
  return isRecord(value) && typeof value.code === 'string' && typeof value.description === 'string'
}

export async function getOffers(signal: AbortSignal): Promise<OfferResponseDto[]> {
  const offersResponse = await get('/offers', signal)

  if (!Array.isArray(offersResponse) || !offersResponse.every(isOffer)) {
    throw unexpectedResponse()
  }

  return offersResponse
}
