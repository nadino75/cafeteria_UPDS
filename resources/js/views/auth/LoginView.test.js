import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHashHistory } from 'vue-router'
import LoginView from './LoginView.vue'
import * as authModule from '@/stores/auth.js'

vi.mock('@/stores/auth.js', () => ({
  useAuthStore: vi.fn(() => ({
    login: vi.fn().mockResolvedValue({ usuario: { rol: 'Cajero' } }),
    isAuthenticated: false,
  }))
}))

vi.mock('@/router/index.js', () => ({
  DASHBOARD_BY_ROL: { 'Cajero': '/dashboard/cajero' }
}))

function makeWrapper() {
  const router = createRouter({
    history: createWebHashHistory(),
    routes: [{ path: '/:p(.*)', component: { template: '<div/>' } }]
  })
  return mount(LoginView, {
    global: { plugins: [createPinia(), router] }
  })
}

describe('LoginView', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('renderiza campos email y password', () => {
    const w = makeWrapper()
    expect(w.find('input[type="email"]').exists()).toBe(true)
    expect(w.find('input[type="password"]').exists()).toBe(true)
  })

  it('muestra error si campos vacíos al hacer submit', async () => {
    const w = makeWrapper()
    await w.find('form').trigger('submit')
    expect(w.text()).toContain('Completa todos los campos')
  })

  it('botón muestra "Ingresando..." mientras carga', async () => {
    // Use a never-resolving promise so loading stays true during the assertion
    vi.mocked(authModule.useAuthStore).mockReturnValueOnce({
      login: vi.fn(() => new Promise(() => {})),
      isAuthenticated: false,
    })
    const w = makeWrapper()
    await w.find('input[type="email"]').setValue('a@b.com')
    await w.find('input[type="password"]').setValue('pass')
    w.find('form').trigger('submit')
    await new Promise(r => setTimeout(r, 0))
    expect(w.find('button[type="submit"]').text()).toContain('Ingresando')
  })
})
