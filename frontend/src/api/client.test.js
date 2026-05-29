import { describe, it, expect, vi } from 'vitest'

vi.mock('axios', () => {
  const instance = {
    interceptors: {
      request:  { use: vi.fn() },
      response: { use: vi.fn() },
    },
  }
  return { default: { create: vi.fn(() => instance) } }
})

import axios from 'axios'

describe('API client', () => {
  it('crea instancia con headers correctos', async () => {
    await import('./client.js')
    expect(axios.create).toHaveBeenCalledWith(
      expect.objectContaining({
        headers: expect.objectContaining({
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        }),
      })
    )
  })

  it('registra interceptor de request', async () => {
    const instance = axios.create()
    expect(instance.interceptors.request.use).toHaveBeenCalled()
  })

  it('registra interceptor de response', async () => {
    const instance = axios.create()
    expect(instance.interceptors.response.use).toHaveBeenCalled()
  })
})
