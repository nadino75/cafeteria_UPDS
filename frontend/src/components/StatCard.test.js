import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatCard from './StatCard.vue'

describe('StatCard', () => {
  it('muestra label y value', () => {
    const w = mount(StatCard, { props: { label: 'Ventas hoy', value: 1500 } })
    expect(w.text()).toContain('Ventas hoy')
    expect(w.text()).toContain('1.500')
  })

  it('muestra delta positivo con flecha arriba', () => {
    const w = mount(StatCard, { props: { label: 'X', value: 100, delta: 12.5 } })
    expect(w.text()).toContain('12.5%')
    expect(w.text()).toContain('↑')
  })

  it('muestra delta negativo con flecha abajo', () => {
    const w = mount(StatCard, { props: { label: 'X', value: 50, delta: -8.3 } })
    expect(w.text()).toContain('↓')
  })

  it('no muestra delta si no se pasa prop', () => {
    const w = mount(StatCard, { props: { label: 'X', value: 50 } })
    expect(w.text()).not.toContain('vs ayer')
  })
})
