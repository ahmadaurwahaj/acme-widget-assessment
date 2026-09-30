import { describe, expect, it } from 'vitest'
import { expectApiError, jsonResponse, stubFetch } from '../testing/fetchStub'
import type { BasketSummaryResponseDto } from '../types/dto'
import { getBasketTotal } from './basketService'

const twoRedWidgets: BasketSummaryResponseDto = {
  lines: [
    {
      code: 'R01',
      name: 'Red Widget',
      quantity: 2,
      unitPriceInCents: 3295,
      lineTotalInCents: 6590,
    },
  ],
  subtotalInCents: 6590,
  discountInCents: 1648,
  deliveryInCents: 495,
  totalInCents: 5437,
}

describe('getBasketTotal', () => {
  it('sends the product codes and returns the priced basket', async () => {
    const fetchMock = stubFetch(jsonResponse(twoRedWidgets))

    await expect(getBasketTotal(['R01', 'R01'])).resolves.toEqual(twoRedWidgets)
    expect(fetchMock.mock.calls[0]?.[1]?.body).toBe('{"productCodes":["R01","R01"]}')
  })

  it.each([
    ['has no lines', { ...twoRedWidgets, lines: undefined }],
    ['has a total as a string', { ...twoRedWidgets, totalInCents: '54.37' }],
    [
      'has a line with a fractional quantity',
      { ...twoRedWidgets, lines: [{ ...twoRedWidgets.lines[0], quantity: 1.5 }] },
    ],
    [
      'has a line without a name',
      { ...twoRedWidgets, lines: [{ ...twoRedWidgets.lines[0], name: undefined }] },
    ],
  ])('rejects a response that %s', async (_, body) => {
    stubFetch(jsonResponse(body))

    await expectApiError(getBasketTotal(['R01']), 'The server sent an unexpected response.')
  })
})
