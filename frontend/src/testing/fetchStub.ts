import { expect, vi } from 'vitest'
import { ApiError } from '../api/httpClient'

export function jsonResponse(body: unknown, init: ResponseInit = {}): Response {
  return new Response(JSON.stringify(body), {
    ...init,
    headers: { 'Content-Type': 'application/json', ...init.headers },
  })
}

export function stubFetch(result: Response | Error) {
  const fetchMock = vi.fn<typeof fetch>()

  if (result instanceof Error) {
    fetchMock.mockRejectedValue(result)
  } else {
    fetchMock.mockResolvedValue(result)
  }

  vi.stubGlobal('fetch', fetchMock)

  return fetchMock
}

export async function expectApiError(promise: Promise<unknown>, message: string): Promise<void> {
  const error = await promise.catch((caught: unknown) => caught)

  expect(error).toBeInstanceOf(ApiError)
  expect(error).toHaveProperty('message', message)
}
