import { useEffect, useState } from 'react'
import { isAbortError, toErrorMessage } from '../api/httpClient'
import type { RemoteData } from '../types/remoteData'

export function useRemoteData<T>(load: (signal: AbortSignal) => Promise<T>): RemoteData<T> {
  const [remoteData, setRemoteData] = useState<RemoteData<T>>({ status: 'loading' })

  useEffect(() => {
    const controller = new AbortController()

    async function loadData(): Promise<void> {
      try {
        const response = await load(controller.signal)
        setRemoteData({ status: 'loaded', response })
      } catch (error) {
        if (isAbortError(error)) {
          return
        }

        setRemoteData({ status: 'failed', message: toErrorMessage(error) })
      }
    }

    loadData()

    return () => {
      controller.abort()
    }
  }, [load])

  return remoteData
}
