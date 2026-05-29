import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import client from '@/api/client.js'

export const useAuthStore = defineStore('auth', () => {
  const usuario = ref(
    JSON.parse(localStorage.getItem('cafeteria_usuario') ?? 'null')
  )
  const token = ref(localStorage.getItem('cafeteria_token') ?? null)

  const isAuthenticated = computed(() => !!token.value)
  const rol             = computed(() => usuario.value?.rol ?? null)
  const isAdmin         = computed(() => usuario.value?.es_superadmin === true)
  const nombreCompleto  = computed(() => usuario.value?.nombre_completo ?? '')

  async function login(email, password) {
    const { data } = await client.post('/auth/login', { email, password })
    token.value   = data.token
    usuario.value = data.usuario
    localStorage.setItem('cafeteria_token',   data.token)
    localStorage.setItem('cafeteria_usuario', JSON.stringify(data.usuario))
    return data
  }

  async function fetchMe() {
    const { data } = await client.get('/auth/me')
    usuario.value = data.usuario
    localStorage.setItem('cafeteria_usuario', JSON.stringify(data.usuario))
  }

  async function logout() {
    try {
      await client.post('/auth/logout')
    } catch {
      // Token ya inválido — continuar
    } finally {
      token.value   = null
      usuario.value = null
      localStorage.removeItem('cafeteria_token')
      localStorage.removeItem('cafeteria_usuario')
    }
  }

  return {
    usuario, token,
    isAuthenticated, rol, isAdmin, nombreCompleto,
    login, fetchMe, logout,
  }
})
