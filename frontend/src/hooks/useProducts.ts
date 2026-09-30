import { useEffect, useState } from 'react'
import { isAbortError, toErrorMessage } from '../api/httpClient'
import { getProducts } from '../services/productService'
import type { ProductResponseDto } from '../types/dto'
import type { RemoteData } from '../types/remoteData'

export function useProducts(): RemoteData<ProductResponseDto[]> {
  const [products, setProducts] = useState<RemoteData<ProductResponseDto[]>>({ status: 'loading' })

  useEffect(() => {
    const controller = new AbortController()

    async function loadProducts(): Promise<void> {
      try {
        const productsResponse = await getProducts(controller.signal)
        setProducts({ status: 'loaded', response: productsResponse })
      } catch (error) {
        if (isAbortError(error)) {
          return
        }

        setProducts({ status: 'failed', message: toErrorMessage(error) })
      }
    }

    loadProducts()

    return () => {
      controller.abort()
    }
  }, [])

  return products
}
