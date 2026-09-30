import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '../api/httpClient'
import { getBasketTotal } from '../services/basketService'
import type { BasketSummaryResponseDto } from '../types/dto'
import { type BasketState, updateBasket } from './useBasket'

vi.mock('../services/basketService')

const getBasketTotalMock = vi.mocked(getBasketTotal)

const summary: BasketSummaryResponseDto = {
  lines: [],
  subtotalInCents: 0,
  discountInCents: 0,
  deliveryInCents: 0,
  totalInCents: 0,
}

const emptyBasket: BasketState = { productCodes: [], summaryResponse: null, errorMessage: null }

function basketWith(productCodes: string[], errorMessage: string | null = null): BasketState {
  return { productCodes, summaryResponse: summary, errorMessage }
}

describe('updateBasket', () => {
  beforeEach(() => {
    getBasketTotalMock.mockReset()
    getBasketTotalMock.mockResolvedValue(summary)
  })

  it('adds the code and prices the new list', async () => {
    const basket = await updateBasket(basketWith(['R01']), { type: 'add', productCode: 'G01' })

    expect(getBasketTotalMock).toHaveBeenCalledWith(['R01', 'G01'])
    expect(basket).toEqual(basketWith(['R01', 'G01']))
  })

  it('removes only the last matching code', async () => {
    const previous = basketWith(['R01', 'G01', 'R01'])

    const basket = await updateBasket(previous, { type: 'remove', productCode: 'R01' })

    expect(getBasketTotalMock).toHaveBeenCalledWith(['R01', 'G01'])
    expect(basket.productCodes).toEqual(['R01', 'G01'])
  })

  it('keeps the list when the removed code is not in the basket', async () => {
    const basket = await updateBasket(basketWith(['G01']), { type: 'remove', productCode: 'R01' })

    expect(basket.productCodes).toEqual(['G01'])
  })

  it('empties the basket without calling the server when the last item is removed', async () => {
    const basket = await updateBasket(basketWith(['R01']), { type: 'remove', productCode: 'R01' })

    expect(basket).toEqual(emptyBasket)
    expect(getBasketTotalMock).not.toHaveBeenCalled()
  })

  it('clears the basket and any error without calling the server', async () => {
    const previous = basketWith(['R01', 'G01'], 'Something went wrong.')

    const basket = await updateBasket(previous, { type: 'clear' })

    expect(basket).toEqual(emptyBasket)
    expect(getBasketTotalMock).not.toHaveBeenCalled()
  })

  it('refuses to add more than 100 items', async () => {
    const fullBasket = basketWith(Array<string>(100).fill('B01'))

    const basket = await updateBasket(fullBasket, { type: 'add', productCode: 'B01' })

    expect(basket.productCodes).toHaveLength(100)
    expect(basket.errorMessage).toBe('A basket cannot hold more than 100 items.')
    expect(getBasketTotalMock).not.toHaveBeenCalled()
  })

  it('still allows removing items from a full basket', async () => {
    const fullBasket = basketWith(Array<string>(100).fill('B01'))

    const basket = await updateBasket(fullBasket, { type: 'remove', productCode: 'B01' })

    expect(basket.productCodes).toHaveLength(99)
  })

  it('keeps the previous basket and shows the error when pricing fails', async () => {
    getBasketTotalMock.mockRejectedValue(new ApiError('Too many requests. Please slow down.'))
    const previous = basketWith(['R01'])

    const basket = await updateBasket(previous, { type: 'add', productCode: 'R01' })

    expect(basket).toEqual(basketWith(['R01'], 'Too many requests. Please slow down.'))
  })

  it('hides unexpected errors behind a generic message', async () => {
    getBasketTotalMock.mockRejectedValue(new Error('undefined is not a function'))

    const basket = await updateBasket(basketWith(['R01']), { type: 'add', productCode: 'G01' })

    expect(basket.errorMessage).toBe('Something went wrong.')
  })

  it('clears an earlier error once pricing works again', async () => {
    const previous = basketWith(['R01'], 'Too many requests. Please slow down.')

    const basket = await updateBasket(previous, { type: 'add', productCode: 'G01' })

    expect(basket.errorMessage).toBeNull()
  })
})
