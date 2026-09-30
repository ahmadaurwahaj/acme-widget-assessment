import { describe, expect, it } from 'vitest'
import { expectApiError, jsonResponse, stubFetch } from '../testing/fetchStub'
import { get, post } from './httpClient'

describe('httpClient', () => {
  it('returns the parsed JSON body', async () => {
    stubFetch(jsonResponse([{ code: 'R01' }]))

    await expect(get('/products')).resolves.toEqual([{ code: 'R01' }])
  })

  it('sends POST bodies as JSON to the versioned API', async () => {
    const fetchMock = stubFetch(jsonResponse({}))

    await post('/basket/total', { productCodes: ['R01'] })

    expect(fetchMock).toHaveBeenCalledWith('/api/v1/basket/total', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: '{"productCodes":["R01"]}',
    })
  })

  it.each([
    [30, 'Too many requests. Please try again in 30 seconds.'],
    [1, 'Too many requests. Please try again in 1 second.'],
  ])(
    'tells the user how long to wait after a 429 with Retry-After %i',
    async (seconds, message) => {
      const headers = { 'Retry-After': String(seconds) }
      stubFetch(
        jsonResponse({ error: 'Too many requests. Please slow down.' }, { status: 429, headers }),
      )

      await expectApiError(post('/basket/total', {}), message)
    },
  )

  it('uses the error message from the API', async () => {
    stubFetch(jsonResponse({ error: 'Unknown product code: X99' }, { status: 422 }))

    await expectApiError(post('/basket/total', {}), 'Unknown product code: X99')
  })

  it('ignores an error body with the wrong shape', async () => {
    stubFetch(jsonResponse({ error: 42 }, { status: 422 }))

    await expectApiError(post('/basket/total', {}), 'Request failed with status 422.')
  })

  it('gives a friendly message when the server fails without JSON', async () => {
    stubFetch(new Response('<h1>Bad Gateway</h1>', { status: 502 }))

    await expectApiError(get('/products'), 'The server is unavailable right now. Please try again.')
  })

  it('reports when the server cannot be reached', async () => {
    stubFetch(new TypeError('fetch failed'))

    await expectApiError(get('/products'), 'Could not reach the server. Is the API running?')
  })

  it('passes aborts through so callers can ignore them', async () => {
    const abortError = new DOMException('The operation was aborted.', 'AbortError')
    stubFetch(abortError)

    await expect(get('/products')).rejects.toBe(abortError)
  })

  it('rejects a successful response that is not JSON', async () => {
    stubFetch(new Response('not json', { status: 200 }))

    await expectApiError(get('/products'), 'The server sent an unexpected response.')
  })
})
