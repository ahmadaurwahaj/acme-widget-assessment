import { describe, expect, it } from 'vitest'
import { expectApiError, jsonResponse, stubFetch } from '../testing/fetchStub'
import { getOffers } from './offerService'

const redWidgetOffer = {
  code: 'r01-second-half-price',
  description: 'Buy one Red Widget, get the second one half price',
}

describe('getOffers', () => {
  it('returns offers with the expected shape', async () => {
    stubFetch(jsonResponse([redWidgetOffer]))

    await expect(getOffers(new AbortController().signal)).resolves.toEqual([redWidgetOffer])
  })

  it.each([
    ['is not a list', redWidgetOffer],
    ['is missing a code', [{ description: redWidgetOffer.description }]],
    ['has a description that is not text', [{ ...redWidgetOffer, description: null }]],
  ])('rejects a response that %s', async (_, body) => {
    stubFetch(jsonResponse(body))

    await expectApiError(
      getOffers(new AbortController().signal),
      'The server sent an unexpected response.',
    )
  })
})
