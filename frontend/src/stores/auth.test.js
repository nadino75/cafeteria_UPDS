import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from './auth.js'

vi.mock('@/api/client.js', () => ({
  default: {
    post: vi.fn(),
    get:  vi.fn(),
  }
}))

import client from '@/api/client.js'

const mockUsuario = {
  id: 1,
  nombre_completo: 'Admin Test',
  email: 'admin@test.com',
  rol: 'Administrador',
  es_superadmin: true,
}

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('inicia sin autenticación', () => {
    const auth = useAuthStore()
    expect(auth.isAuthenticated).toBe(false)
    expect(auth.rol).toBe(null)
  })

  it('login guarda token y usuario', async () => {
    client.post.mockResolvedValueOnce({
      data: { success: true, token: 'jwt-test', usuario: mockUsuario }
    })
    const auth = useAuthStore()
    await auth.login('admin@test.com', 'password')

    expect(auth.isAuthenticated).toBe(true)
    expect(auth.rol).toBe('Administrador')
    expect(auth.isAdmin).toBe(true)
    expect(localStorage.getItem('cafeteria_token')).toBe('jwt-test')
  })

  it('logout limpia estado y localStorage', async () => {
    client.post.mockResolvedValueOnce({ data: { success: true } })
    const auth = useAuthStore()
    auth.token   = 'jwt-test'
    auth.usuario = mockUsuario

    await auth.logout()

    expect(auth.isAuthenticated).toBe(false)
    expect(auth.usuario).toBe(null)
    expect(localStorage.getItem('cafeteria_token')).toBe(null)
  })

  it('fetchMe actualiza usuario desde API', async () => {
    client.get.mockResolvedValueOnce({
      data: { success: true, usuario: { ...mockUsuario, activo: true } }
    })
    const auth = useAuthStore()
    await auth.fetchMe()

    expect(auth.nombreCompleto).toBe('Admin Test')
  })
})
