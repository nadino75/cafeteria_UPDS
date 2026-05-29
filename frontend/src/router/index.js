import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { DASHBOARD_BY_ROL } from './dashboards.js'

export { DASHBOARD_BY_ROL }

const routes = [
  { path: '/', redirect: '/login' },
  {
    path: '/login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { publico: true },
  },
  {
    path: '/register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { rolRequerido: 'Administrador' },
  },
  {
    path: '/dashboard',
    component: () => import('@/layouts/AppLayout.vue'),
    children: [
      {
        path: 'admin',
        component: () => import('@/views/dashboard/AdminDashboard.vue'),
        meta: { rolRequerido: 'Administrador' },
      },
      {
        path: 'gerente',
        component: () => import('@/views/dashboard/GerenteDashboard.vue'),
        meta: { rolRequerido: 'Gerente' },
      },
      {
        path: 'cajero',
        component: () => import('@/views/dashboard/CajeroDashboard.vue'),
        meta: { rolRequerido: 'Cajero' },
      },
      {
        path: 'almacenista',
        component: () => import('@/views/dashboard/AlmacenistaDashboard.vue'),
        meta: { rolRequerido: 'Almacenista' },
      },
      {
        path: 'contador',
        component: () => import('@/views/dashboard/ContadorDashboard.vue'),
        meta: { rolRequerido: 'Contador' },
      },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async to => {
  const auth = useAuthStore()

  // Ruta pública → permitir, pero si ya autenticado redirigir a su dashboard
  if (to.meta.publico) {
    if (auth.isAuthenticated && auth.rol) {
      return DASHBOARD_BY_ROL[auth.rol] ?? '/login'
    }
    return true
  }

  // No autenticado → login
  if (!auth.isAuthenticated) return '/login'

  // Token en localStorage pero usuario no hidratado → rehidratar
  if (!auth.usuario) {
    try {
      await auth.fetchMe()
    } catch {
      await auth.logout()
      return '/login'
    }
  }

  // Verificar rol requerido por la ruta
  const rolRequerido = to.meta.rolRequerido
  if (rolRequerido && auth.rol !== rolRequerido && !auth.isAdmin) {
    return DASHBOARD_BY_ROL[auth.rol] ?? '/login'
  }

  return true
})

export default router
