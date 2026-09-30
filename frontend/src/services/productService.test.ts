import { describe, expect, it } from 'vitest'
import { expectApiError, jsonResponse, stubFetch } from '../testing/fetchStub'
import { getProducts } from './productService'

const redWidget = { code: 'R01', name: 'Red Widget', priceInCents: 3295 }

describe('getProducts', () => {
  it('returns products with the expected shape', async () => {
    stubFetch(jsonResponse([redWidget]))

    await expect(getProducts(new AbortController().signal)).resolves.toEqual([redWidget])
  })

  it.each([
    ['is not a list', { products: [redWidget] }],
    ['has a price as a string', [{ ...redWidget, priceInCents: '32.95' }]],
    ['has a price in dollars', [{ ...redWidget, priceInCents: 32.95 }]],
    ['is missing a name', [{ code: 'R01', priceInCents: 3295 }]],
    ['contains null', [null]],
  ])('rejects a response that %s', async (_, body) => {
    stubFetch(jsonResponse(body))

    await expectApiError(
      getProducts(new AbortController().signal),
      'The server sent an unexpected response.',
    )
  })
})
