import { describe, it, expect } from 'vitest'
import { DASHBOARD_BY_ROL } from './dashboards.js'

describe('DASHBOARD_BY_ROL', () => {
  it('mapea todos los roles a rutas', () => {
    expect(DASHBOARD_BY_ROL['Administrador']).toBe('/dashboard/admin')
    expect(DASHBOARD_BY_ROL['Gerente']).toBe('/dashboard/gerente')
    expect(DASHBOARD_BY_ROL['Cajero']).toBe('/dashboard/cajero')
    expect(DASHBOARD_BY_ROL['Almacenista']).toBe('/dashboard/almacenista')
    expect(DASHBOARD_BY_ROL['Contador']).toBe('/dashboard/contador')
  })

  it('cubre exactamente 5 roles', () => {
    expect(Object.keys(DASHBOARD_BY_ROL)).toHaveLength(5)
  })
})
