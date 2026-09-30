import type { ErrorResponseDto } from '../types/dto'

const API_BASE_URL = '/api/v1'

export class ApiError extends Error {}

export function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError'
}

export function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function isErrorResponse(value: unknown): value is ErrorResponseDto {
  return isRecord(value) && typeof value.error === 'string'
}

export function unexpectedResponse(): ApiError {
  return new ApiError('The server sent an unexpected response.')
}

export function toErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message
  }

  return 'Something went wrong.'
}

async function readErrorMessage(response: Response): Promise<string> {
  const retryAfterSeconds = Number(response.headers.get('Retry-After'))
  if (response.status === 429 && retryAfterSeconds > 0) {
    const unit = retryAfterSeconds === 1 ? 'second' : 'seconds'

    return `Too many requests. Please try again in ${retryAfterSeconds} ${unit}.`
  }

  const errorResponse: unknown = await response.json().catch(() => null)

  if (isErrorResponse(errorResponse)) {
    return errorResponse.error
  }

  if (response.status >= 500) {
    return 'The server is unavailable right now. Please try again.'
  }

  return `Request failed with status ${response.status}.`
}

async function request(path: string, options: RequestInit): Promise<unknown> {
  const url = `${API_BASE_URL}${path}`
  let response: Response

  try {
    response = await fetch(url, options)
  } catch (error) {
    if (isAbortError(error)) {
      throw error
    }

    throw new ApiError('Could not reach the server. Is the API running?')
  }

  if (!response.ok) {
    const message = await readErrorMessage(response)
    throw new ApiError(message)
  }

  return response.json().catch(() => {
    throw unexpectedResponse()
  })
}

export function get(path: string, signal?: AbortSignal): Promise<unknown> {
  return request(path, { method: 'GET', signal })
}

export function post(path: string, requestBody: unknown): Promise<unknown> {
  const options: RequestInit = {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(requestBody),
  }

  return request(path, options)
}
