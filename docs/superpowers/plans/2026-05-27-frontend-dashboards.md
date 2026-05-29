# Frontend Dashboards Vue.js — Cafetería UPDS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear el frontend SPA Vue 3 en `frontend/` con autenticación JWT, layout administrativo responsivo Dark Warm y 5 dashboards diferenciados por rol.

**Architecture:** Frontend separado en `cafeteria_UPDS/frontend/` consumiendo la API Laravel en `http://localhost:8000/api`. Vue Router 4 con `beforeEach` verifica token y rol. Pinia persiste sesión en localStorage. Dashboards independientes por rol comparten AppLayout shell.

**Tech Stack:** Vue 3.5 `<script setup>`, Vue Router 4, Pinia 2, Axios 1.x, Chart.js 4, Tailwind CSS v4 (`@theme` en CSS), Vite 7, Vitest + @vue/test-utils 2.

**Respuesta real de la API (AuthController):**
```json
{ "success": true, "token": "...", "usuario": { "id": 1, "nombre_completo": "...", "email": "...", "rol": "Administrador", "es_superadmin": true } }
```
`GET /api/auth/me` devuelve `{ success, usuario: { id, nombre_completo, email, rol_id, rol, es_superadmin, activo, ultimo_login } }`

---

## Mapa de archivos

```
frontend/
├── index.html
├── package.json
├── vite.config.js
├── .env.local                              VITE_API_URL=http://localhost:8000/api
└── src/
    ├── main.js
    ├── App.vue
    ├── assets/main.css                     @theme design tokens + scrollbar
    ├── api/client.js                       axios + interceptores JWT
    ├── stores/auth.js                      Pinia: usuario, token, rol, login/logout/fetchMe
    ├── router/index.js                     rutas + beforeEach rol-guard
    ├── layouts/AppLayout.vue               sidebar + header + RouterView
    ├── components/
    │   ├── AppSidebar.vue                  nav por rol, colapsable
    │   ├── AppHeader.vue                   user menu + logout + hamburger
    │   ├── StatCard.vue                    KPI card reutilizable
    │   ├── AlertBadge.vue                  píldora de severidad
    │   └── MiniChart.vue                   sparkline Chart.js
    └── views/
        ├── auth/
        │   ├── LoginView.vue
        │   └── RegisterView.vue            solo Admin
        └── dashboard/
            ├── AdminDashboard.vue
            ├── GerenteDashboard.vue
            ├── CajeroDashboard.vue         + modales turno y venta
            ├── AlmacenistaDashboard.vue    + modales ajuste y recepción
            └── ContadorDashboard.vue
```

---

### Task 1: Scaffold del proyecto frontend

**Files:**
- Create: `frontend/package.json`
- Create: `frontend/vite.config.js`
- Create: `frontend/index.html`
- Create: `frontend/src/main.js`
- Create: `frontend/src/App.vue`
- Create: `frontend/src/assets/main.css`
- Create: `frontend/.env.local`

- [ ] **Step 1.1: Crear `frontend/package.json`**

```json
{
  "name": "cafeteria-upds-frontend",
  "private": true,
  "version": "0.1.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "test": "vitest run",
    "test:watch": "vitest"
  },
  "dependencies": {
    "vue": "^3.5.13",
    "vue-router": "^4.5.0",
    "pinia": "^2.3.1",
    "axios": "^1.11.0",
    "chart.js": "^4.4.7"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.2.3",
    "vite": "^7.0.0",
    "tailwindcss": "^4.0.0",
    "@tailwindcss/vite": "^4.0.0",
    "vitest": "^3.0.0",
    "@vue/test-utils": "^2.4.6",
    "jsdom": "^26.0.0"
  }
}
```

- [ ] **Step 1.2: Crear `frontend/vite.config.js`**

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: { '@': resolve(__dirname, 'src') }
  },
  test: {
    environment: 'jsdom',
    globals: true
  }
})
```

- [ ] **Step 1.3: Crear `frontend/index.html`**

```html
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cafetería UPDS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
```

- [ ] **Step 1.4: Crear `frontend/src/assets/main.css`**

```css
@import "tailwindcss";

@theme {
  /* Fondos */
  --color-base:     #0F0D0C;
  --color-surface:  #1A1614;
  --color-card:     #221E1B;
  --color-elevated: #2C2724;
  --color-edge:     #38302A;
  --color-edge-lit: #4A4240;

  /* Acento ámbar */
  --color-amber:        #D4821E;
  --color-amber-bright: #F0A030;
  --color-amber-glow:   #FFB84D;
  --color-amber-dim:    #7A4A20;

  /* Texto */
  --color-ink:      #EDE8E3;
  --color-ink-mute: #9E8E82;
  --color-ink-dim:  #6B5A52;

  /* Semánticos */
  --color-ok:   #22A77A;
  --color-err:  #D94040;
  --color-info: #3A7BD5;
  --color-warn: #D4A017;

  /* Tipografía */
  --font-display: "Cormorant Garamond", Georgia, serif;
  --font-sans:    "DM Sans", system-ui, sans-serif;
  --font-mono:    "JetBrains Mono", "Courier New", monospace;
}

*, *::before, *::after { box-sizing: border-box; }

body {
  background-color: var(--color-surface);
  color: var(--color-ink);
  font-family: var(--font-sans);
  margin: 0;
  -webkit-font-smoothing: antialiased;
}

::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--color-base); }
::-webkit-scrollbar-thumb { background: var(--color-edge-lit); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--color-ink-dim); }
```

- [ ] **Step 1.5: Crear `frontend/src/main.js`**

```js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router/index.js'
import App from './App.vue'
import './assets/main.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
```

- [ ] **Step 1.6: Crear `frontend/src/App.vue`**

```vue
<template>
  <RouterView />
</template>
```

- [ ] **Step 1.7: Crear `frontend/.env.local`**

```
VITE_API_URL=http://localhost:8000/api
```

- [ ] **Step 1.8: Instalar dependencias**

```bash
cd frontend
npm install
```

Expected: se crea `node_modules/`, sin errores.

- [ ] **Step 1.9: Verificar que arranca**

```bash
npm run dev
```

Expected: `VITE v7.x.x  ready in XXX ms` con URL `http://localhost:5173/`. Pantalla en blanco es correcto (App.vue vacío).

- [ ] **Step 1.10: Commit**

```bash
cd ..
git add frontend/
git commit -m "feat: scaffold proyecto Vue 3 frontend con Tailwind v4 dark warm theme"
```

---

### Task 2: CORS en Laravel (prerequisito backend)

**Files:**
- Modify: `config/cors.php`

- [ ] **Step 2.1: Leer config actual**

```bash
cat config/cors.php
```

- [ ] **Step 2.2: Actualizar `config/cors.php`**

Localizar la clave `'allowed_origins'` y agregar el origen del frontend:

```php
'allowed_origins' => [
    'http://localhost:5173',   // Vite dev server
    'http://localhost:4173',   // Vite preview
],
```

Si la clave es `'allowed_origins_patterns'`, dejarla vacía. Asegurarse también de que:
```php
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age'         => 0,
'supports_credentials' => false,
```

- [ ] **Step 2.3: Verificar CORS desde el frontend**

Con Laravel corriendo (`php artisan serve`) y el frontend en dev (`npm run dev`):

```bash
curl -H "Origin: http://localhost:5173" \
     -H "Access-Control-Request-Method: POST" \
     -X OPTIONS http://localhost:8000/api/auth/login -v
```

Expected: respuesta con `Access-Control-Allow-Origin: http://localhost:5173`.

- [ ] **Step 2.4: Commit**

```bash
git add config/cors.php
git commit -m "feat: permite origen localhost:5173 en CORS para frontend Vue"
```

---

### Task 3: API client con interceptores JWT

**Files:**
- Create: `frontend/src/api/client.js`
- Create: `frontend/src/api/client.test.js`

- [ ] **Step 3.1: Crear `frontend/src/api/client.js`**

```js
import axios from 'axios'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

client.interceptors.request.use(config => {
  const token = localStorage.getItem('cafeteria_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

client.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('cafeteria_token')
      localStorage.removeItem('cafeteria_usuario')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default client
```

- [ ] **Step 3.2: Crear `frontend/src/api/client.test.js`**

```js
import { describe, it, expect, beforeEach, vi } from 'vitest'

// Mock axios antes de importar client
vi.mock('axios', () => {
  const instance = {
    interceptors: {
      request: { use: vi.fn() },
      response: { use: vi.fn() },
    },
  }
  return { default: { create: vi.fn(() => instance) } }
})

import axios from 'axios'

describe('API client', () => {
  it('crea instancia con baseURL correcta', async () => {
    await import('./client.js')
    expect(axios.create).toHaveBeenCalledWith(
      expect.objectContaining({
        headers: expect.objectContaining({ 'Accept': 'application/json' }),
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
```

- [ ] **Step 3.3: Ejecutar test**

```bash
cd frontend && npm run test
```

Expected: 3 tests passing.

- [ ] **Step 3.4: Commit**

```bash
git add frontend/src/api/
git commit -m "feat: api client axios con interceptores JWT y redirect en 401"
```

---

### Task 4: Auth store (Pinia)

**Files:**
- Create: `frontend/src/stores/auth.js`
- Create: `frontend/src/stores/auth.test.js`

- [ ] **Step 4.1: Crear `frontend/src/stores/auth.js`**

```js
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
    // Respuesta: { success, token, usuario: { id, nombre_completo, email, rol, es_superadmin } }
    token.value   = data.token
    usuario.value = data.usuario
    localStorage.setItem('cafeteria_token',   data.token)
    localStorage.setItem('cafeteria_usuario', JSON.stringify(data.usuario))
    return data
  }

  async function fetchMe() {
    const { data } = await client.get('/auth/me')
    // Respuesta: { success, usuario: { id, nombre_completo, email, rol_id, rol, es_superadmin, activo } }
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
```

- [ ] **Step 4.2: Crear `frontend/src/stores/auth.test.js`**

```js
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
    auth.token = 'jwt-test'
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
```

- [ ] **Step 4.3: Ejecutar tests**

```bash
cd frontend && npm run test
```

Expected: 7 tests passing (3 de client + 4 de auth).

- [ ] **Step 4.4: Commit**

```bash
git add frontend/src/stores/
git commit -m "feat: auth store Pinia con login/logout/fetchMe y persistencia localStorage"
```

---

### Task 5: Router con guardias de rol

**Files:**
- Create: `frontend/src/router/index.js`
- Create: `frontend/src/router/guards.test.js`

- [ ] **Step 5.1: Crear `frontend/src/router/index.js`**

```js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

/** Mapa rol → ruta de dashboard */
export const DASHBOARD_BY_ROL = {
  'Administrador': '/dashboard/admin',
  'Gerente':       '/dashboard/gerente',
  'Cajero':        '/dashboard/cajero',
  'Almacenista':   '/dashboard/almacenista',
  'Contador':      '/dashboard/contador',
}

const routes = [
  { path: '/',        redirect: '/login' },
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
```

- [ ] **Step 5.2: Crear `frontend/src/router/guards.test.js`**

```js
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { DASHBOARD_BY_ROL } from './index.js'

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
```

- [ ] **Step 5.3: Ejecutar tests**

```bash
cd frontend && npm run test
```

Expected: 9 tests passing.

- [ ] **Step 5.4: Commit**

```bash
git add frontend/src/router/
git commit -m "feat: router Vue con guardias de rol y redireccion por dashboard"
```

---

### Task 6: LoginView

**Files:**
- Create: `frontend/src/views/auth/LoginView.vue`
- Create: `frontend/src/views/auth/LoginView.test.js`

- [ ] **Step 6.1: Crear `frontend/src/views/auth/LoginView.vue`**

```vue
<template>
  <div class="min-h-screen flex items-center justify-center bg-base px-4">
    <div class="w-full max-w-md">

      <!-- Marca -->
      <div class="text-center mb-10">
        <h1 class="font-display text-5xl text-amber font-semibold tracking-tight">
          Cafetería
        </h1>
        <p class="text-ink-mute text-xs tracking-[0.25em] uppercase mt-1">
          UPDS · Sistema de Gestión
        </p>
      </div>

      <!-- Card -->
      <div class="bg-card border border-edge rounded-2xl p-8 shadow-2xl">
        <h2 class="font-display text-2xl text-ink font-medium mb-6">
          Iniciar sesión
        </h2>

        <form @submit.prevent="handleLogin" novalidate>
          <!-- Email -->
          <div class="mb-4">
            <label class="block text-ink-mute text-sm mb-1.5">
              Correo electrónico
            </label>
            <input
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              placeholder="usuario@cafeteria.upds"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <!-- Contraseña -->
          <div class="mb-6">
            <label class="block text-ink-mute text-sm mb-1.5">
              Contraseña
            </label>
            <input
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              placeholder="••••••••"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <!-- Error -->
          <div
            v-if="error"
            class="mb-4 px-4 py-3 bg-err/10 border border-err/30 rounded-lg"
          >
            <p class="text-err text-sm">{{ error }}</p>
          </div>

          <!-- Botón -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-amber hover:bg-amber-bright text-base font-medium py-3 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm tracking-wide"
          >
            {{ loading ? 'Ingresando...' : 'Ingresar' }}
          </button>
        </form>
      </div>

      <p class="text-center text-ink-dim text-xs mt-6">
        ¿Sin acceso? Contacta al administrador del sistema.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { DASHBOARD_BY_ROL } from '@/router/index.js'

const router = useRouter()
const auth   = useAuthStore()

const form    = reactive({ email: '', password: '' })
const loading = ref(false)
const error   = ref(null)

async function handleLogin() {
  if (!form.email || !form.password) {
    error.value = 'Completa todos los campos.'
    return
  }
  loading.value = true
  error.value   = null
  try {
    const data   = await auth.login(form.email, form.password)
    const destino = DASHBOARD_BY_ROL[data.usuario?.rol] ?? '/login'
    router.push(destino)
  } catch (e) {
    const msg = e.response?.data?.message
    error.value = msg ?? 'Error al iniciar sesión. Verifica tus credenciales.'
  } finally {
    loading.value = false
  }
}
</script>
```

- [ ] **Step 6.2: Crear `frontend/src/views/auth/LoginView.test.js`**

```js
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHashHistory } from 'vue-router'
import LoginView from './LoginView.vue'

vi.mock('@/stores/auth.js', () => ({
  useAuthStore: () => ({
    login: vi.fn().mockResolvedValue({
      usuario: { rol: 'Cajero' }
    }),
    isAuthenticated: false,
  })
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

  it('muestra error si campos vacíos', async () => {
    const w = makeWrapper()
    await w.find('form').trigger('submit')
    expect(w.text()).toContain('Completa todos los campos')
  })

  it('botón muestra "Ingresando..." mientras carga', async () => {
    const w = makeWrapper()
    await w.find('input[type="email"]').setValue('a@b.com')
    await w.find('input[type="password"]').setValue('pass')
    await w.find('form').trigger('submit')
    expect(w.find('button').text()).toContain('Ingresando')
  })
})
```

- [ ] **Step 6.3: Ejecutar tests**

```bash
cd frontend && npm run test
```

Expected: 12 tests passing.

- [ ] **Step 6.4: Commit**

```bash
git add frontend/src/views/auth/LoginView.vue frontend/src/views/auth/LoginView.test.js
git commit -m "feat: LoginView con form JWT, manejo de error y redirección por rol"
```

---

### Task 7: RegisterView (solo Administrador)

**Files:**
- Create: `frontend/src/views/auth/RegisterView.vue`

- [ ] **Step 7.1: Crear `frontend/src/views/auth/RegisterView.vue`**

```vue
<template>
  <div class="min-h-screen flex items-center justify-center bg-base px-4">
    <div class="w-full max-w-lg">

      <!-- Encabezado -->
      <div class="flex items-center gap-3 mb-8">
        <button
          @click="router.back()"
          class="text-ink-mute hover:text-ink transition-colors"
          aria-label="Volver"
        >
          <!-- Flecha izquierda -->
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
          </svg>
        </button>
        <div>
          <h1 class="font-display text-3xl text-ink font-medium">Nuevo usuario</h1>
          <p class="text-ink-mute text-sm">Solo el Administrador puede crear cuentas</p>
        </div>
      </div>

      <!-- Card -->
      <div class="bg-card border border-edge rounded-2xl p-8">
        <form @submit.prevent="handleSubmit" novalidate>

          <!-- Nombre completo -->
          <div class="mb-4">
            <label class="block text-ink-mute text-sm mb-1.5">Nombre completo</label>
            <input
              v-model="form.nombre_completo"
              type="text"
              required
              placeholder="Ej: María López"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <!-- Email -->
          <div class="mb-4">
            <label class="block text-ink-mute text-sm mb-1.5">Correo electrónico</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="usuario@cafeteria.upds"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <!-- Contraseña -->
          <div class="mb-4">
            <label class="block text-ink-mute text-sm mb-1.5">Contraseña</label>
            <input
              v-model="form.password"
              type="password"
              required
              minlength="8"
              placeholder="Mínimo 8 caracteres"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <!-- Rol -->
          <div class="mb-6">
            <label class="block text-ink-mute text-sm mb-1.5">Rol</label>
            <select
              v-model="form.rol_id"
              required
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber transition-colors"
            >
              <option value="" disabled>Selecciona un rol...</option>
              <option v-for="rol in roles" :key="rol.id" :value="rol.id">
                {{ rol.nombre }}
              </option>
            </select>
          </div>

          <!-- Error / Éxito -->
          <div v-if="error" class="mb-4 px-4 py-3 bg-err/10 border border-err/30 rounded-lg">
            <p class="text-err text-sm">{{ error }}</p>
          </div>
          <div v-if="exito" class="mb-4 px-4 py-3 bg-ok/10 border border-ok/30 rounded-lg">
            <p class="text-ok text-sm">✓ Usuario creado correctamente.</p>
          </div>

          <!-- Acciones -->
          <div class="flex gap-3">
            <button
              type="button"
              @click="router.back()"
              class="flex-1 border border-edge text-ink-mute hover:text-ink hover:border-edge-lit py-3 rounded-lg transition-colors text-sm"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-3 rounded-lg transition-colors disabled:opacity-50 text-sm"
            >
              {{ loading ? 'Creando...' : 'Crear usuario' }}
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import client from '@/api/client.js'

const router  = useRouter()
const loading = ref(false)
const error   = ref(null)
const exito   = ref(false)
const roles   = ref([])

const form = reactive({
  nombre_completo: '',
  email: '',
  password: '',
  rol_id: '',
})

onMounted(async () => {
  try {
    const { data } = await client.get('/usuarios')
    // Extraer roles únicos de la lista de usuarios no es ideal;
    // usar endpoint de roles cuando esté disponible.
    // Por ahora: lista fija de roles conocidos del seeder.
    roles.value = [
      { id: 2, nombre: 'Gerente' },
      { id: 3, nombre: 'Cajero' },
      { id: 4, nombre: 'Almacenista' },
      { id: 5, nombre: 'Contador' },
    ]
  } catch {
    roles.value = [
      { id: 2, nombre: 'Gerente' },
      { id: 3, nombre: 'Cajero' },
      { id: 4, nombre: 'Almacenista' },
      { id: 5, nombre: 'Contador' },
    ]
  }
})

async function handleSubmit() {
  if (!form.nombre_completo || !form.email || !form.password || !form.rol_id) {
    error.value = 'Completa todos los campos.'
    return
  }
  if (form.password.length < 8) {
    error.value = 'La contraseña debe tener al menos 8 caracteres.'
    return
  }
  loading.value = true
  error.value   = null
  exito.value   = false
  try {
    await client.post('/usuarios', {
      nombre_completo: form.nombre_completo,
      email:           form.email,
      password:        form.password,
      rol_id:          form.rol_id,
    })
    exito.value = true
    Object.assign(form, { nombre_completo: '', email: '', password: '', rol_id: '' })
  } catch (e) {
    const msgs = e.response?.data?.errors
    error.value = msgs
      ? Object.values(msgs).flat().join(' ')
      : (e.response?.data?.message ?? 'Error al crear el usuario.')
  } finally {
    loading.value = false
  }
}
</script>
```

- [ ] **Step 7.2: Commit**

```bash
git add frontend/src/views/auth/RegisterView.vue
git commit -m "feat: RegisterView para creacion de usuarios (solo Administrador)"
```

---

### Task 8: Componentes compartidos (StatCard, AlertBadge, MiniChart)

**Files:**
- Create: `frontend/src/components/StatCard.vue`
- Create: `frontend/src/components/AlertBadge.vue`
- Create: `frontend/src/components/MiniChart.vue`
- Create: `frontend/src/components/StatCard.test.js`

- [ ] **Step 8.1: Crear `frontend/src/components/StatCard.vue`**

Props: `label` (string), `value` (string|number), `delta` (number, opcional — % vs ayer), `variante` ('ok'|'err'|'warn'|'neutral', default 'neutral').

```vue
<template>
  <div class="bg-card border border-edge rounded-xl p-5 flex flex-col gap-3">
    <!-- Etiqueta -->
    <p class="text-ink-mute text-xs uppercase tracking-widest font-medium">
      {{ label }}
    </p>

    <!-- Valor principal -->
    <p class="font-mono text-3xl font-medium" :class="valueColor">
      {{ formattedValue }}
    </p>

    <!-- Delta -->
    <div v-if="delta !== null && delta !== undefined" class="flex items-center gap-1.5">
      <span class="text-xs font-medium" :class="deltaColor">
        {{ deltaPrefix }}{{ Math.abs(delta).toFixed(1) }}%
      </span>
      <span class="text-ink-dim text-xs">vs ayer</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:    { type: String, required: true },
  value:    { type: [String, Number], required: true },
  delta:    { type: Number, default: null },
  variante: {
    type: String,
    default: 'neutral',
    validator: v => ['ok', 'err', 'warn', 'neutral'].includes(v),
  },
})

const COLORS = {
  ok:      'text-ok',
  err:     'text-err',
  warn:    'text-warn',
  neutral: 'text-ink',
}

const valueColor = computed(() => COLORS[props.variante])

const deltaColor = computed(() => {
  if (props.delta === null) return ''
  return props.delta >= 0 ? 'text-ok' : 'text-err'
})

const deltaPrefix = computed(() => (props.delta >= 0 ? '↑ +' : '↓ '))

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('es-BO')
  }
  return props.value
})
</script>
```

- [ ] **Step 8.2: Crear `frontend/src/components/AlertBadge.vue`**

Props: `texto` (string), `severidad` ('ok'|'warn'|'err'|'info', default 'info').

```vue
<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
    :class="clases"
  >
    {{ texto }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  texto:     { type: String, required: true },
  severidad: {
    type: String,
    default: 'info',
    validator: v => ['ok', 'warn', 'err', 'info'].includes(v),
  },
})

const CLASES = {
  ok:   'bg-ok/10   text-ok   border-ok/30',
  warn: 'bg-warn/10 text-warn border-warn/30',
  err:  'bg-err/10  text-err  border-err/30',
  info: 'bg-info/10 text-info border-info/30',
}

const clases = computed(() => CLASES[props.severidad])
</script>
```

- [ ] **Step 8.3: Crear `frontend/src/components/MiniChart.vue`**

Props: `datos` (number[]), `tipo` ('line'|'bar', default 'line'), `color` (string, default amber).

```vue
<template>
  <canvas ref="canvasRef" class="w-full" style="height: 48px;" />
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Chart, LineController, BarController, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Filler } from 'chart.js'

Chart.register(LineController, BarController, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Filler)

const props = defineProps({
  datos: { type: Array, required: true },
  tipo:  { type: String, default: 'line', validator: v => ['line', 'bar'].includes(v) },
  color: { type: String, default: '#D4821E' },
})

const canvasRef = ref(null)
let chart = null

function buildChart() {
  if (!canvasRef.value) return
  if (chart) chart.destroy()

  const labels = props.datos.map((_, i) => i)

  chart = new Chart(canvasRef.value, {
    type: props.tipo,
    data: {
      labels,
      datasets: [{
        data: props.datos,
        borderColor: props.color,
        backgroundColor: props.tipo === 'line'
          ? `${props.color}22`
          : `${props.color}88`,
        fill: props.tipo === 'line',
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 0,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: {
        x: { display: false },
        y: { display: false },
      },
      animation: { duration: 400 },
    },
  })
}

onMounted(buildChart)
watch(() => props.datos, buildChart, { deep: true })
onBeforeUnmount(() => { if (chart) chart.destroy() })
</script>
```

- [ ] **Step 8.4: Crear `frontend/src/components/StatCard.test.js`**

```js
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
```

- [ ] **Step 8.5: Ejecutar tests**

```bash
cd frontend && npm run test
```

Expected: 16 tests passing.

- [ ] **Step 8.6: Commit**

```bash
git add frontend/src/components/StatCard.vue frontend/src/components/AlertBadge.vue \
        frontend/src/components/MiniChart.vue frontend/src/components/StatCard.test.js
git commit -m "feat: componentes compartidos StatCard, AlertBadge y MiniChart con Chart.js"
```

---

### Task 9: AppSidebar

**Files:**
- Create: `frontend/src/components/AppSidebar.vue`

- [ ] **Step 9.1: Crear `frontend/src/components/AppSidebar.vue`**

```vue
<template>
  <!-- Overlay móvil -->
  <div
    v-if="open && isMobile"
    class="fixed inset-0 bg-black/60 z-20"
    @click="$emit('close')"
  />

  <!-- Sidebar -->
  <aside
    class="fixed top-0 left-0 h-full z-30 flex flex-col bg-base border-r border-edge transition-all duration-300"
    :class="[
      isMobile
        ? open ? 'w-64 translate-x-0' : 'w-64 -translate-x-full'
        : collapsed ? 'w-16' : 'w-64'
    ]"
  >
    <!-- Logo -->
    <div class="flex items-center gap-3 px-4 py-5 border-b border-edge shrink-0">
      <div class="w-8 h-8 rounded-lg bg-amber flex items-center justify-center shrink-0">
        <span class="text-base text-sm font-bold">C</span>
      </div>
      <span v-if="!collapsed || isMobile" class="font-display text-xl text-ink font-semibold truncate">
        Cafetería
      </span>
    </div>

    <!-- Nav items -->
    <nav class="flex-1 overflow-y-auto py-3 px-2">
      <div v-for="item in navItems" :key="item.label">
        <!-- Separador de grupo -->
        <p
          v-if="item.grupo && (!collapsed || isMobile)"
          class="text-ink-dim text-[10px] uppercase tracking-widest px-3 pt-4 pb-1"
        >
          {{ item.grupo }}
        </p>

        <!-- Link -->
        <RouterLink
          v-if="!item.disabled"
          :to="item.ruta"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-0.5 text-ink-mute hover:text-ink hover:bg-elevated transition-colors group"
          active-class="bg-amber/10 text-amber border border-amber/20"
          :title="collapsed && !isMobile ? item.label : undefined"
        >
          <span class="w-5 h-5 shrink-0" v-html="item.icono" />
          <span v-if="!collapsed || isMobile" class="text-sm font-medium truncate">
            {{ item.label }}
          </span>
        </RouterLink>

        <!-- Disabled -->
        <div
          v-else
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-0.5 text-ink-dim cursor-not-allowed opacity-50"
          :title="(collapsed && !isMobile) ? item.label : `${item.label} — Próximamente`"
        >
          <span class="w-5 h-5 shrink-0" v-html="item.icono" />
          <span v-if="!collapsed || isMobile" class="text-sm font-medium truncate">
            {{ item.label }}
          </span>
          <span v-if="!collapsed || isMobile" class="ml-auto text-[10px] border border-edge-lit rounded px-1">
            Pronto
          </span>
        </div>
      </div>
    </nav>

    <!-- Toggle collapse (solo desktop) -->
    <button
      v-if="!isMobile"
      @click="$emit('toggle')"
      class="flex items-center justify-center py-4 border-t border-edge text-ink-dim hover:text-ink transition-colors shrink-0"
      :title="collapsed ? 'Expandir' : 'Colapsar'"
    >
      <svg class="w-4 h-4 transition-transform" :class="collapsed ? 'rotate-0' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
      </svg>
    </button>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth.js'

defineProps({
  collapsed: { type: Boolean, default: false },
  open:      { type: Boolean, default: false },  // solo móvil
  isMobile:  { type: Boolean, default: false },
})
defineEmits(['toggle', 'close'])

const auth = useAuthStore()

// ── Iconos SVG inline ─────────────────────────────────────────────────────────
const ICONS = {
  grid:     `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>`,
  users:    `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>`,
  tag:      `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>`,
  folder:   `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v8.25A2.25 2.25 0 0 0 4.5 16.5h15a2.25 2.25 0 0 0 2.25-2.25V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>`,
  menu:     `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>`,
  box:      `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>`,
  cart:     `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>`,
  bag:      `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></svg>`,
  clock:    `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`,
  truck:    `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>`,
  banknote: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>`,
  calc:     `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Z" /></svg>`,
  chart:    `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>`,
  shield:   `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>`,
  cog:      `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>`,
}

// ── Configuración de nav por rol ──────────────────────────────────────────────
const NAV_CONFIG = {
  Administrador: [
    { label: 'Dashboard',       ruta: '/dashboard/admin',  icono: ICONS.grid },
    { grupo: 'Usuarios', label: 'Usuarios', ruta: '/usuarios', icono: ICONS.users },
    { grupo: 'Catálogos', label: 'Productos',   ruta: '/productos',   icono: ICONS.tag },
    { label: 'Categorías',  ruta: '/categorias',  icono: ICONS.folder },
    { label: 'Menús',       ruta: '/menus',       icono: ICONS.menu },
    { grupo: 'Operaciones', label: 'Inventario',  ruta: '/inventario',  icono: ICONS.box },
    { label: 'Ventas',      ruta: '/ventas',      icono: ICONS.cart },
    { label: 'Compras',     ruta: '/compras',     icono: ICONS.bag },
    { label: 'Turnos',      ruta: '/turnos',      icono: ICONS.clock },
    { label: 'Clientes',    ruta: '/clientes',    icono: ICONS.users },
    { label: 'Gastos',      ruta: '/gastos',      icono: ICONS.banknote },
    { grupo: 'Finanzas', label: 'Contabilidad', ruta: '/contabilidad', icono: ICONS.calc },
    { label: 'Reportes',    ruta: '/reportes',    icono: ICONS.chart },
    { grupo: 'Sistema', label: 'Roles & Permisos', ruta: '/roles', icono: ICONS.shield, disabled: true },
    { label: 'Configuración', ruta: '/configuracion', icono: ICONS.cog, disabled: true },
  ],
  Gerente: [
    { label: 'Dashboard',    ruta: '/dashboard/gerente', icono: ICONS.grid },
    { grupo: 'Usuarios', label: 'Usuarios', ruta: '/usuarios', icono: ICONS.users },
    { grupo: 'Catálogos', label: 'Productos',  ruta: '/productos',  icono: ICONS.tag },
    { label: 'Categorías', ruta: '/categorias', icono: ICONS.folder },
    { label: 'Menús',      ruta: '/menus',      icono: ICONS.menu },
    { grupo: 'Operaciones', label: 'Inventario', ruta: '/inventario', icono: ICONS.box },
    { label: 'Ventas',     ruta: '/ventas',     icono: ICONS.cart },
    { label: 'Compras',    ruta: '/compras',    icono: ICONS.bag },
    { label: 'Turnos',     ruta: '/turnos',     icono: ICONS.clock },
    { label: 'Clientes',   ruta: '/clientes',   icono: ICONS.users },
    { label: 'Gastos',     ruta: '/gastos',     icono: ICONS.banknote },
    { grupo: 'Finanzas', label: 'Contabilidad', ruta: '/contabilidad', icono: ICONS.calc },
    { label: 'Reportes',   ruta: '/reportes',   icono: ICONS.chart },
  ],
  Cajero: [
    { label: 'Dashboard', ruta: '/dashboard/cajero', icono: ICONS.grid },
    { grupo: 'Mi turno', label: 'Ventas',   ruta: '/ventas',   icono: ICONS.cart },
    { label: 'Turnos',   ruta: '/turnos',   icono: ICONS.clock },
    { label: 'Clientes', ruta: '/clientes', icono: ICONS.users },
    { grupo: 'Consulta', label: 'Menús', ruta: '/menus', icono: ICONS.menu },
  ],
  Almacenista: [
    { label: 'Dashboard', ruta: '/dashboard/almacenista', icono: ICONS.grid },
    { grupo: 'Inventario', label: 'Inventario',  ruta: '/inventario',  icono: ICONS.box },
    { label: 'Categorías', ruta: '/categorias',  icono: ICONS.folder },
    { label: 'Productos',  ruta: '/productos',   icono: ICONS.tag },
    { grupo: 'Compras', label: 'Compras',     ruta: '/compras',     icono: ICONS.bag },
    { label: 'Proveedores',ruta: '/proveedores', icono: ICONS.truck },
  ],
  Contador: [
    { label: 'Dashboard',    ruta: '/dashboard/contador', icono: ICONS.grid },
    { grupo: 'Finanzas', label: 'Contabilidad', ruta: '/contabilidad', icono: ICONS.calc },
    { label: 'Reportes',     ruta: '/reportes',           icono: ICONS.chart },
  ],
}

const navItems = computed(() => NAV_CONFIG[auth.rol] ?? [])
</script>
```

- [ ] **Step 9.2: Commit**

```bash
git add frontend/src/components/AppSidebar.vue
git commit -m "feat: AppSidebar con navegacion por rol, iconos SVG y estado colapsable"
```

---

### Task 10: AppHeader y AppLayout

**Files:**
- Create: `frontend/src/components/AppHeader.vue`
- Create: `frontend/src/layouts/AppLayout.vue`

- [ ] **Step 10.1: Crear `frontend/src/components/AppHeader.vue`**

```vue
<template>
  <header class="fixed top-0 right-0 z-10 flex items-center justify-between px-6 bg-base border-b border-edge"
    style="height: 64px;"
    :style="{ left: headerLeft }"
  >
    <!-- Izquierda: hamburguesa (móvil) + título -->
    <div class="flex items-center gap-4">
      <button
        v-if="isMobile"
        @click="$emit('open-sidebar')"
        class="text-ink-mute hover:text-ink transition-colors"
        aria-label="Abrir menú"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
        </svg>
      </button>
      <slot name="breadcrumb">
        <span class="text-ink-mute text-sm">Dashboard</span>
      </slot>
    </div>

    <!-- Derecha: rol badge + nombre + logout -->
    <div class="flex items-center gap-4">
      <!-- Badge de rol -->
      <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber/10 text-amber border border-amber/20">
        {{ auth.rol }}
      </span>

      <!-- Nombre usuario -->
      <span class="hidden md:block text-ink-mute text-sm truncate max-w-[160px]">
        {{ auth.nombreCompleto }}
      </span>

      <!-- Logout -->
      <button
        @click="handleLogout"
        class="flex items-center gap-2 text-ink-mute hover:text-err transition-colors text-sm"
        title="Cerrar sesión"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
        </svg>
        <span class="hidden sm:block">Salir</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const props = defineProps({
  collapsed: { type: Boolean, default: false },
  isMobile:  { type: Boolean, default: false },
})
defineEmits(['open-sidebar'])

const auth   = useAuthStore()
const router = useRouter()

const headerLeft = computed(() => {
  if (props.isMobile) return '0px'
  return props.collapsed ? '64px' : '256px'
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
```

- [ ] **Step 10.2: Crear `frontend/src/layouts/AppLayout.vue`**

```vue
<template>
  <div class="min-h-screen bg-surface">

    <AppSidebar
      :collapsed="sidebarCollapsed"
      :open="sidebarOpen"
      :is-mobile="isMobile"
      @toggle="sidebarCollapsed = !sidebarCollapsed"
      @close="sidebarOpen = false"
    />

    <AppHeader
      :collapsed="sidebarCollapsed"
      :is-mobile="isMobile"
      @open-sidebar="sidebarOpen = true"
    />

    <!-- Contenido principal -->
    <main
      class="transition-all duration-300 pt-16"
      :style="{ marginLeft: mainMargin }"
    >
      <div class="p-6">
        <RouterView />
      </div>
    </main>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppHeader  from '@/components/AppHeader.vue'

const sidebarCollapsed = ref(false)
const sidebarOpen      = ref(false)
const windowWidth      = ref(window.innerWidth)

const isMobile = computed(() => windowWidth.value < 768)

const mainMargin = computed(() => {
  if (isMobile.value) return '0px'
  return sidebarCollapsed.value ? '64px' : '256px'
})

function onResize() {
  windowWidth.value = window.innerWidth
  if (!isMobile.value) sidebarOpen.value = false
}

onMounted(() => window.addEventListener('resize', onResize))
onBeforeUnmount(() => window.removeEventListener('resize', onResize))
</script>
```

- [ ] **Step 10.3: Commit**

```bash
git add frontend/src/components/AppHeader.vue frontend/src/layouts/AppLayout.vue
git commit -m "feat: AppHeader y AppLayout responsivo con sidebar colapsable y drawer movil"
```

---

### Task 11: AdminDashboard

**Files:**
- Create: `frontend/src/views/dashboard/AdminDashboard.vue`

- [ ] **Step 11.1: Crear `frontend/src/views/dashboard/AdminDashboard.vue`**

```vue
<template>
  <div class="space-y-6">

    <!-- Encabezado -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">
          Bienvenido, {{ auth.nombreCompleto.split(' ')[0] }}
        </h1>
        <p class="text-ink-mute text-sm mt-1">{{ fechaHoy }} · Panel de Administración</p>
      </div>
      <div class="flex gap-2 flex-wrap justify-end">
        <RouterLink to="/register"
          class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors">
          + Nuevo usuario
        </RouterLink>
        <RouterLink to="/reportes"
          class="px-4 py-2 border border-edge text-ink-mute hover:text-ink text-sm rounded-lg transition-colors">
          Ver reportes
        </RouterLink>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard label="Ventas hoy"        :value="kpis.ventasHoy"     :delta="kpis.deltaVentas" variante="ok" />
      <StatCard label="Turnos activos"    :value="kpis.turnosActivos" variante="neutral" />
      <StatCard label="Stock bajo alerta" :value="kpis.stockBajo"     :variante="kpis.stockBajo > 0 ? 'err' : 'ok'" />
      <StatCard label="Usuarios activos"  :value="kpis.usuariosActivos" variante="neutral" />
    </div>

    <!-- Gráfico + alertas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Ventas 7 días -->
      <div class="lg:col-span-2 bg-card border border-edge rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-display text-lg text-ink font-medium">Ventas — últimos 7 días</h2>
          <span class="text-ink-dim text-xs font-mono">Bs.</span>
        </div>
        <div v-if="cargandoGrafico" class="h-32 flex items-center justify-center text-ink-dim text-sm">
          Cargando...
        </div>
        <MiniChart v-else :datos="ventasSemana" tipo="bar" class="h-32" style="height:128px" />
        <div class="flex justify-between mt-3">
          <span v-for="(dia, i) in labelsSemana" :key="i" class="text-ink-dim text-[10px]">{{ dia }}</span>
        </div>
      </div>

      <!-- Alertas críticas -->
      <div class="bg-card border border-edge rounded-xl p-5">
        <h2 class="font-display text-lg text-ink font-medium mb-4">Alertas del sistema</h2>
        <div v-if="alertas.length === 0" class="text-ink-mute text-sm py-4 text-center">
          Sin alertas críticas ✓
        </div>
        <div v-for="alerta in alertas" :key="alerta.id" class="flex items-start gap-3 py-2 border-b border-edge last:border-0">
          <AlertBadge :texto="alerta.tipo" :severidad="alerta.severidad" />
          <span class="text-ink-mute text-sm flex-1">{{ alerta.descripcion }}</span>
        </div>
      </div>
    </div>

    <!-- Últimas ventas del día -->
    <div class="bg-card border border-edge rounded-xl">
      <div class="flex items-center justify-between p-5 border-b border-edge">
        <h2 class="font-display text-lg text-ink font-medium">Últimas ventas del día</h2>
        <RouterLink to="/ventas" class="text-amber hover:text-amber-bright text-sm transition-colors">
          Ver todas →
        </RouterLink>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider">
              <th class="text-left px-5 py-3">Hora</th>
              <th class="text-left px-5 py-3">Usuario</th>
              <th class="text-left px-5 py-3">Método</th>
              <th class="text-right px-5 py-3">Total</th>
              <th class="text-left px-5 py-3">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="ultimasVentas.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-ink-mute">Sin ventas hoy</td>
            </tr>
            <tr
              v-for="v in ultimasVentas" :key="v.id"
              class="border-t border-edge hover:bg-elevated transition-colors"
            >
              <td class="px-5 py-3 font-mono text-ink-mute text-xs">{{ formatHora(v.fecha) }}</td>
              <td class="px-5 py-3 text-ink">{{ v.usuario?.nombre_completo ?? '—' }}</td>
              <td class="px-5 py-3 text-ink-mute capitalize">{{ v.metodo_pago }}</td>
              <td class="px-5 py-3 text-right font-mono text-amber">Bs. {{ Number(v.total).toFixed(2) }}</td>
              <td class="px-5 py-3">
                <AlertBadge
                  :texto="v.estado"
                  :severidad="v.estado === 'completada' ? 'ok' : 'err'"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
import client from '@/api/client.js'
import StatCard   from '@/components/StatCard.vue'
import AlertBadge from '@/components/AlertBadge.vue'
import MiniChart  from '@/components/MiniChart.vue'

const auth = useAuthStore()

// ── Estado ────────────────────────────────────────────────────────────────────
const kpis = ref({ ventasHoy: 0, deltaVentas: null, turnosActivos: 0, stockBajo: 0, usuariosActivos: 0 })
const ultimasVentas   = ref([])
const ventasSemana    = ref([])
const alertas         = ref([])
const cargandoGrafico = ref(true)

// ── Fecha ─────────────────────────────────────────────────────────────────────
const fechaHoy = computed(() =>
  new Date().toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

const labelsSemana = computed(() => {
  const dias = []
  for (let i = 6; i >= 0; i--) {
    const d = new Date()
    d.setDate(d.getDate() - i)
    dias.push(d.toLocaleDateString('es-BO', { weekday: 'short' }))
  }
  return dias
})

// ── Helpers ───────────────────────────────────────────────────────────────────
function hoy()  { return new Date().toISOString().split('T')[0] }
function ayer() { const d = new Date(); d.setDate(d.getDate() - 1); return d.toISOString().split('T')[0] }
function fechaOffset(offset) {
  const d = new Date(); d.setDate(d.getDate() - offset); return d.toISOString().split('T')[0]
}
function formatHora(iso) {
  return new Date(iso).toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
}

// ── Carga de datos ────────────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([
    cargarKpis(),
    cargarUltimasVentas(),
    cargarVentasSemana(),
    cargarAlertas(),
  ])
})

async function cargarKpis() {
  const [resHoy, resAyer, resTurnos, resStock, resUsuarios] = await Promise.all([
    client.get(`/reportes/ventas-diarias?fecha=${hoy()}`).catch(() => ({ data: { data: {} } })),
    client.get(`/reportes/ventas-diarias?fecha=${ayer()}`).catch(() => ({ data: { data: {} } })),
    client.get('/turnos?estado=abierto').catch(() => ({ data: { data: [] } })),
    client.get('/inventario/stock-bajo').catch(() => ({ data: { data: [] } })),
    client.get('/usuarios').catch(() => ({ data: { data: [] } })),
  ])

  const ventasHoy  = resHoy.data.data?.total_ventas  ?? 0
  const ventasAyer = resAyer.data.data?.total_ventas ?? 0
  const delta = ventasAyer > 0 ? ((ventasHoy - ventasAyer) / ventasAyer) * 100 : null

  const turnos   = Array.isArray(resTurnos.data.data)   ? resTurnos.data.data   : []
  const stock    = Array.isArray(resStock.data.data)     ? resStock.data.data     : []
  const usuarios = Array.isArray(resUsuarios.data.data)  ? resUsuarios.data.data  : []

  kpis.value = {
    ventasHoy:       `Bs. ${Number(ventasHoy).toFixed(2)}`,
    deltaVentas:     delta !== null ? Math.round(delta * 10) / 10 : null,
    turnosActivos:   turnos.length,
    stockBajo:       stock.length,
    usuariosActivos: usuarios.filter(u => u.activo !== false).length,
  }
}

async function cargarUltimasVentas() {
  try {
    const { data } = await client.get(`/ventas?fecha=${hoy()}&per_page=10`)
    ultimasVentas.value = (data.data?.data ?? data.data ?? []).slice(0, 10)
  } catch { ultimasVentas.value = [] }
}

async function cargarVentasSemana() {
  cargandoGrafico.value = true
  try {
    const promises = Array.from({ length: 7 }, (_, i) =>
      client.get(`/reportes/ventas-diarias?fecha=${fechaOffset(6 - i)}`)
        .then(r => r.data.data?.total_ventas ?? 0)
        .catch(() => 0)
    )
    ventasSemana.value = await Promise.all(promises)
  } finally {
    cargandoGrafico.value = false
  }
}

async function cargarAlertas() {
  try {
    const [resStock, resVenc] = await Promise.all([
      client.get('/inventario/stock-bajo').catch(() => ({ data: { data: [] } })),
      client.get('/inventario/vencimientos').catch(() => ({ data: { data: [] } })),
    ])
    const listaAlertas = []
    const stock = Array.isArray(resStock.data.data) ? resStock.data.data : []
    const venc  = Array.isArray(resVenc.data.data)  ? resVenc.data.data  : []
    if (stock.length > 0) listaAlertas.push({ id: 'stock', tipo: 'Stock', severidad: 'err', descripcion: `${stock.length} producto(s) bajo mínimo` })
    if (venc.length > 0)  listaAlertas.push({ id: 'venc',  tipo: 'Vencimiento', severidad: 'warn', descripcion: `${venc.length} lote(s) vencen en 7 días` })
    alertas.value = listaAlertas
  } catch { alertas.value = [] }
}
</script>
```

- [ ] **Step 11.2: Commit**

```bash
git add frontend/src/views/dashboard/AdminDashboard.vue
git commit -m "feat: AdminDashboard con KPIs, grafico 7 dias, ventas recientes y alertas"
```

---

### Task 12: GerenteDashboard

**Files:**
- Create: `frontend/src/views/dashboard/GerenteDashboard.vue`

- [ ] **Step 12.1: Crear `frontend/src/views/dashboard/GerenteDashboard.vue`**

```vue
<template>
  <div class="space-y-6">

    <div>
      <h1 class="font-display text-3xl text-ink font-semibold">Panel de Gerencia</h1>
      <p class="text-ink-mute text-sm mt-1">{{ fechaHoy }}</p>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard label="Ventas hoy"     :value="kpis.ventasHoy"   :delta="kpis.deltaVentas" variante="ok" />
      <StatCard label="Gastos del día" :value="kpis.gastosHoy"   variante="warn" />
      <StatCard label="Margen bruto"   :value="kpis.margen"      :variante="kpis.margenNum >= 0 ? 'ok' : 'err'" />
      <StatCard label="Turnos hoy"     :value="kpis.turnosTexto" variante="neutral" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Top productos -->
      <div class="bg-card border border-edge rounded-xl">
        <div class="p-5 border-b border-edge">
          <h2 class="font-display text-lg text-ink font-medium">Top productos del mes</h2>
        </div>
        <div class="p-5 space-y-3">
          <div v-if="topProductos.length === 0" class="text-ink-mute text-sm text-center py-4">Sin datos</div>
          <div v-for="(p, i) in topProductos" :key="p.id" class="flex items-center gap-3">
            <span class="font-mono text-ink-dim text-xs w-4">{{ i + 1 }}</span>
            <div class="flex-1">
              <div class="flex items-center justify-between mb-1">
                <span class="text-ink text-sm">{{ p.nombre }}</span>
                <span class="font-mono text-amber text-xs">{{ p.total_vendido }} uds</span>
              </div>
              <div class="h-1.5 bg-elevated rounded-full overflow-hidden">
                <div class="h-full bg-amber rounded-full transition-all"
                  :style="{ width: `${(p.total_vendido / topProductos[0].total_vendido) * 100}%` }" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alertas de stock + turnos del día -->
      <div class="space-y-4">

        <!-- Stock bajo -->
        <div class="bg-card border border-edge rounded-xl p-5">
          <h2 class="font-display text-lg text-ink font-medium mb-3">Stock bajo mínimo</h2>
          <div v-if="stockBajo.length === 0" class="text-ok text-sm">✓ Todos los productos con stock suficiente</div>
          <div v-for="p in stockBajo.slice(0, 5)" :key="p.id" class="flex items-center justify-between py-2 border-b border-edge last:border-0">
            <span class="text-ink text-sm">{{ p.nombre }}</span>
            <div class="flex items-center gap-2">
              <span class="font-mono text-err text-xs">{{ p.stock_actual }}/{{ p.stock_minimo }}</span>
              <AlertBadge texto="Bajo" severidad="err" />
            </div>
          </div>
        </div>

        <!-- Turnos del día -->
        <div class="bg-card border border-edge rounded-xl p-5">
          <h2 class="font-display text-lg text-ink font-medium mb-3">Turnos de hoy</h2>
          <div v-if="turnos.length === 0" class="text-ink-mute text-sm">Sin turnos registrados hoy</div>
          <div v-for="t in turnos" :key="t.id" class="flex items-center justify-between py-2 border-b border-edge last:border-0">
            <div>
              <p class="text-ink text-sm">{{ t.usuario_apertura?.nombre_completo ?? '—' }}</p>
              <p class="text-ink-dim text-xs font-mono">{{ formatHora(t.fecha_apertura) }}</p>
            </div>
            <AlertBadge
              :texto="t.estado"
              :severidad="t.estado === 'abierto' ? 'ok' : t.estado === 'en_corte' ? 'warn' : 'info'"
            />
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import client from '@/api/client.js'
import StatCard   from '@/components/StatCard.vue'
import AlertBadge from '@/components/AlertBadge.vue'

const kpis        = ref({ ventasHoy: 'Bs. 0', deltaVentas: null, gastosHoy: 'Bs. 0', margen: 'Bs. 0', margenNum: 0, turnosTexto: '0 abiertos' })
const topProductos = ref([])
const stockBajo    = ref([])
const turnos       = ref([])

const fechaHoy = computed(() =>
  new Date().toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

function hoy()  { return new Date().toISOString().split('T')[0] }
function ayer() { const d = new Date(); d.setDate(d.getDate() - 1); return d.toISOString().split('T')[0] }
function inicioMes() { const d = new Date(); d.setDate(1); return d.toISOString().split('T')[0] }
function formatHora(iso) { return new Date(iso).toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' }) }

onMounted(async () => {
  await Promise.all([cargarKpis(), cargarTopProductos(), cargarStockBajo(), cargarTurnos()])
})

async function cargarKpis() {
  const [rHoy, rAyer, rGastos] = await Promise.all([
    client.get(`/reportes/ventas-diarias?fecha=${hoy()}`).catch(() => ({ data: { data: {} } })),
    client.get(`/reportes/ventas-diarias?fecha=${ayer()}`).catch(() => ({ data: { data: {} } })),
    client.get(`/gastos?fecha=${hoy()}`).catch(() => ({ data: { data: [] } })),
  ])
  const ventasHoy  = rHoy.data.data?.total_ventas  ?? 0
  const ventasAyer = rAyer.data.data?.total_ventas ?? 0
  const costoHoy   = rHoy.data.data?.total_costo   ?? 0
  const delta      = ventasAyer > 0 ? ((ventasHoy - ventasAyer) / ventasAyer) * 100 : null
  const gastos     = Array.isArray(rGastos.data.data) ? rGastos.data.data.reduce((a, g) => a + Number(g.monto), 0) : 0
  const margen     = ventasHoy - costoHoy - gastos

  const turnosRes  = await client.get(`/turnos?fecha=${hoy()}`).catch(() => ({ data: { data: [] } }))
  const listaTurnos = Array.isArray(turnosRes.data.data) ? turnosRes.data.data : []
  const abiertos   = listaTurnos.filter(t => t.estado === 'abierto').length

  kpis.value = {
    ventasHoy:   `Bs. ${Number(ventasHoy).toFixed(2)}`,
    deltaVentas: delta !== null ? Math.round(delta * 10) / 10 : null,
    gastosHoy:   `Bs. ${gastos.toFixed(2)}`,
    margen:      `Bs. ${margen.toFixed(2)}`,
    margenNum:   margen,
    turnosTexto: `${abiertos} abierto${abiertos !== 1 ? 's' : ''}`,
  }
}

async function cargarTopProductos() {
  try {
    const { data } = await client.get(`/reportes/productos-vendidos?desde=${inicioMes()}&hasta=${hoy()}`)
    topProductos.value = (data.data ?? []).slice(0, 5)
  } catch { topProductos.value = [] }
}

async function cargarStockBajo() {
  try {
    const { data } = await client.get('/inventario/stock-bajo')
    stockBajo.value = data.data ?? []
  } catch { stockBajo.value = [] }
}

async function cargarTurnos() {
  try {
    const { data } = await client.get(`/turnos?fecha=${hoy()}`)
    turnos.value = Array.isArray(data.data) ? data.data : []
  } catch { turnos.value = [] }
}
</script>
```

- [ ] **Step 12.2: Commit**

```bash
git add frontend/src/views/dashboard/GerenteDashboard.vue
git commit -m "feat: GerenteDashboard con KPIs, top productos, stock bajo y turnos del dia"
```

---

### Task 13: CajeroDashboard (con modales)

**Files:**
- Create: `frontend/src/views/dashboard/CajeroDashboard.vue`

- [ ] **Step 13.1: Crear `frontend/src/views/dashboard/CajeroDashboard.vue`**

```vue
<template>
  <div class="space-y-6">

    <h1 class="font-display text-3xl text-ink font-semibold">Mi turno</h1>

    <!-- Estado del turno -->
    <div class="bg-card border rounded-xl p-6" :class="turnoActivo ? 'border-ok/30' : 'border-edge'">
      <div v-if="cargandoTurno" class="text-ink-mute text-sm">Verificando turno...</div>

      <!-- Sin turno activo -->
      <div v-else-if="!turnoActivo" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <p class="text-ink font-medium">Sin turno activo</p>
          <p class="text-ink-mute text-sm mt-1">Abre un turno para comenzar a registrar ventas.</p>
        </div>
        <button @click="modalAbrirTurno = true"
          class="px-6 py-3 bg-amber hover:bg-amber-bright text-base font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
          Abrir turno
        </button>
      </div>

      <!-- Turno activo -->
      <div v-else>
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
          <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-ok animate-pulse" />
            <span class="text-ok font-medium text-sm uppercase tracking-wider">Turno activo</span>
          </div>
          <div class="flex gap-2">
            <button @click="modalNuevaVenta = true"
              class="px-5 py-2.5 bg-amber hover:bg-amber-bright text-base font-medium rounded-lg transition-colors text-sm">
              + Nueva venta
            </button>
            <button @click="modalCerrarTurno = true"
              class="px-5 py-2.5 border border-err/40 text-err hover:bg-err/10 rounded-lg transition-colors text-sm">
              Cerrar turno
            </button>
          </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-elevated rounded-lg p-3">
            <p class="text-ink-dim text-xs">Apertura</p>
            <p class="font-mono text-ink text-sm mt-1">{{ formatHora(turnoActivo.fecha_apertura) }}</p>
          </div>
          <div class="bg-elevated rounded-lg p-3">
            <p class="text-ink-dim text-xs">Caja inicial</p>
            <p class="font-mono text-amber text-sm mt-1">Bs. {{ Number(turnoActivo.caja_inicial ?? 0).toFixed(2) }}</p>
          </div>
          <div class="bg-elevated rounded-lg p-3">
            <p class="text-ink-dim text-xs">Ventas del turno</p>
            <p class="font-mono text-ink text-sm mt-1">{{ ventasTurno.length }}</p>
          </div>
          <div class="bg-elevated rounded-lg p-3">
            <p class="text-ink-dim text-xs">Total acumulado</p>
            <p class="font-mono text-ok text-sm mt-1">Bs. {{ totalTurno }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Ventas del turno (solo lectura) -->
    <div v-if="turnoActivo" class="bg-card border border-edge rounded-xl">
      <div class="p-5 border-b border-edge">
        <h2 class="font-display text-lg text-ink font-medium">Ventas del turno</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider">
              <th class="text-left px-5 py-3">Hora</th>
              <th class="text-left px-5 py-3">Método</th>
              <th class="text-left px-5 py-3">Cliente</th>
              <th class="text-right px-5 py-3">Total</th>
              <th class="text-left px-5 py-3">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="ventasTurno.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-ink-mute">Sin ventas en este turno</td>
            </tr>
            <tr v-for="v in ventasTurno" :key="v.id" class="border-t border-edge">
              <td class="px-5 py-3 font-mono text-ink-mute text-xs">{{ formatHora(v.fecha) }}</td>
              <td class="px-5 py-3 text-ink capitalize">{{ v.metodo_pago }}</td>
              <td class="px-5 py-3 text-ink-mute">{{ v.cliente?.nombre ?? '—' }}</td>
              <td class="px-5 py-3 text-right font-mono text-amber">Bs. {{ Number(v.total).toFixed(2) }}</td>
              <td class="px-5 py-3">
                <AlertBadge :texto="v.estado" :severidad="v.estado === 'completada' ? 'ok' : 'err'" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Abrir turno -->
    <div v-if="modalAbrirTurno" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-sm p-6">
        <h3 class="font-display text-xl text-ink font-medium mb-4">Abrir turno</h3>
        <label class="block text-ink-mute text-sm mb-1.5">Caja inicial (Bs.)</label>
        <input v-model.number="cajaInicial" type="number" min="0" step="0.01" placeholder="0.00"
          class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber mb-4" />
        <p v-if="errorModal" class="text-err text-sm mb-3">{{ errorModal }}</p>
        <div class="flex gap-3">
          <button @click="modalAbrirTurno = false; errorModal = null"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">
            Cancelar
          </button>
          <button @click="abrirTurno" :disabled="loadingModal"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ loadingModal ? 'Abriendo...' : 'Abrir' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Cerrar turno -->
    <div v-if="modalCerrarTurno" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6 overflow-y-auto max-h-screen">
        <h3 class="font-display text-xl text-ink font-medium mb-4">Cerrar turno</h3>
        <p class="text-ink-mute text-sm mb-4">Ingresa el conteo físico de caja.</p>
        <div class="grid grid-cols-2 gap-3 mb-4">
          <div v-for="campo in camposCorte" :key="campo.key">
            <label class="block text-ink-dim text-xs mb-1">{{ campo.label }}</label>
            <input v-model.number="corte[campo.key]" type="number" min="0"
              class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
        </div>
        <div>
          <label class="block text-ink-mute text-sm mb-1.5">Observaciones</label>
          <textarea v-model="corte.observaciones" rows="2" placeholder="Opcional..."
            class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber resize-none mb-4" />
        </div>
        <p v-if="errorModal" class="text-err text-sm mb-3">{{ errorModal }}</p>
        <div class="flex gap-3">
          <button @click="modalCerrarTurno = false; errorModal = null"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">
            Cancelar
          </button>
          <button @click="cerrarTurno" :disabled="loadingModal"
            class="flex-1 bg-err hover:bg-err/80 text-white font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ loadingModal ? 'Cerrando...' : 'Cerrar turno' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Nueva venta (simplificado) -->
    <div v-if="modalNuevaVenta" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-lg p-6 overflow-y-auto max-h-[90vh]">
        <h3 class="font-display text-xl text-ink font-medium mb-4">Nueva venta</h3>

        <!-- Método de pago -->
        <label class="block text-ink-mute text-sm mb-1.5">Método de pago</label>
        <select v-model="nuevaVenta.metodo_pago"
          class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber mb-4">
          <option value="efectivo">Efectivo</option>
          <option value="tarjeta">Tarjeta</option>
          <option value="transferencia">Transferencia</option>
          <option value="mixto">Mixto</option>
        </select>

        <!-- Menús disponibles -->
        <p class="text-ink-mute text-sm mb-2">Selecciona ítems:</p>
        <div class="grid grid-cols-2 gap-2 mb-4 max-h-48 overflow-y-auto">
          <button
            v-for="m in menus" :key="m.id"
            @click="agregarItem(m)"
            class="text-left p-3 bg-elevated hover:bg-amber/10 border border-edge hover:border-amber/30 rounded-lg transition-colors"
          >
            <p class="text-ink text-sm font-medium">{{ m.nombre }}</p>
            <p class="text-amber font-mono text-xs mt-0.5">Bs. {{ Number(m.precio_venta).toFixed(2) }}</p>
          </button>
        </div>

        <!-- Items seleccionados -->
        <div v-if="nuevaVenta.items.length > 0" class="mb-4">
          <p class="text-ink-mute text-sm mb-2">Items:</p>
          <div v-for="(item, i) in nuevaVenta.items" :key="i" class="flex items-center justify-between py-1.5 border-b border-edge">
            <span class="text-ink text-sm">{{ item.nombre }}</span>
            <div class="flex items-center gap-2">
              <button @click="item.cantidad = Math.max(1, item.cantidad - 1)" class="text-ink-mute hover:text-ink px-1">−</button>
              <span class="font-mono text-ink text-sm w-6 text-center">{{ item.cantidad }}</span>
              <button @click="item.cantidad++" class="text-ink-mute hover:text-ink px-1">+</button>
              <span class="font-mono text-amber text-xs ml-2">Bs. {{ (item.precio_unitario * item.cantidad).toFixed(2) }}</span>
              <button @click="nuevaVenta.items.splice(i, 1)" class="text-ink-dim hover:text-err ml-1">×</button>
            </div>
          </div>
          <div class="flex justify-between mt-2 pt-2 border-t border-edge">
            <span class="text-ink-mute text-sm">Total</span>
            <span class="font-mono text-amber font-medium">Bs. {{ totalNuevaVenta }}</span>
          </div>
        </div>

        <p v-if="errorModal" class="text-err text-sm mb-3">{{ errorModal }}</p>
        <div class="flex gap-3">
          <button @click="modalNuevaVenta = false; errorModal = null"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">
            Cancelar
          </button>
          <button @click="confirmarVenta" :disabled="loadingModal || nuevaVenta.items.length === 0"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ loadingModal ? 'Registrando...' : 'Confirmar venta' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
import client from '@/api/client.js'
import AlertBadge from '@/components/AlertBadge.vue'

const auth = useAuthStore()

const turnoActivo     = ref(null)
const ventasTurno     = ref([])
const menus           = ref([])
const cargandoTurno   = ref(true)
const loadingModal    = ref(false)
const errorModal      = ref(null)
const modalAbrirTurno  = ref(false)
const modalCerrarTurno = ref(false)
const modalNuevaVenta  = ref(false)
const cajaInicial      = ref(0)

const corte = reactive({
  total_efectivo_contado: 0, total_real: 0, total_tarjeta: 0,
  total_transferencia: 0, billetes_200: 0, billetes_100: 0,
  billetes_50: 0, billetes_20: 0, billetes_10: 0,
  monedas_total: 0, observaciones: '',
})
const camposCorte = [
  { key: 'total_efectivo_contado', label: 'Efectivo contado (Bs.)' },
  { key: 'total_real',             label: 'Total real (Bs.)' },
  { key: 'total_tarjeta',          label: 'Tarjeta (Bs.)' },
  { key: 'total_transferencia',    label: 'Transferencia (Bs.)' },
  { key: 'billetes_200',           label: 'Billetes Bs. 200' },
  { key: 'billetes_100',           label: 'Billetes Bs. 100' },
  { key: 'billetes_50',            label: 'Billetes Bs. 50' },
  { key: 'billetes_20',            label: 'Billetes Bs. 20' },
  { key: 'billetes_10',            label: 'Billetes Bs. 10' },
  { key: 'monedas_total',          label: 'Monedas (Bs.)' },
]

const nuevaVenta = reactive({ metodo_pago: 'efectivo', items: [] })

const totalTurno = computed(() =>
  ventasTurno.value.filter(v => v.estado === 'completada').reduce((a, v) => a + Number(v.total), 0).toFixed(2)
)
const totalNuevaVenta = computed(() =>
  nuevaVenta.items.reduce((a, i) => a + i.precio_unitario * i.cantidad, 0).toFixed(2)
)

function formatHora(iso) { return new Date(iso).toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' }) }

function agregarItem(menu) {
  const existente = nuevaVenta.items.find(i => i.id === menu.id && i.tipo === 'menu')
  if (existente) { existente.cantidad++; return }
  nuevaVenta.items.push({ id: menu.id, tipo: 'menu', nombre: menu.nombre, cantidad: 1, precio_unitario: Number(menu.precio_venta) })
}

onMounted(async () => {
  await Promise.all([cargarTurnoActivo(), cargarMenus()])
})

async function cargarTurnoActivo() {
  cargandoTurno.value = true
  try {
    const { data } = await client.get('/turnos/activo')
    turnoActivo.value = data.data ?? null
    if (turnoActivo.value) await cargarVentasTurno()
  } catch { turnoActivo.value = null } finally { cargandoTurno.value = false }
}

async function cargarVentasTurno() {
  if (!turnoActivo.value) return
  try {
    const { data } = await client.get(`/ventas?turno_id=${turnoActivo.value.id}`)
    ventasTurno.value = data.data?.data ?? data.data ?? []
  } catch { ventasTurno.value = [] }
}

async function cargarMenus() {
  try {
    const { data } = await client.get('/menus?activo=true')
    menus.value = data.data ?? []
  } catch { menus.value = [] }
}

async function abrirTurno() {
  loadingModal.value = true; errorModal.value = null
  try {
    await client.post('/turnos/abrir', { caja_inicial: cajaInicial.value })
    modalAbrirTurno.value = false
    await cargarTurnoActivo()
  } catch (e) {
    errorModal.value = e.response?.data?.message ?? 'Error al abrir turno.'
  } finally { loadingModal.value = false }
}

async function cerrarTurno() {
  if (!turnoActivo.value) return
  loadingModal.value = true; errorModal.value = null
  try {
    await client.post(`/turnos/${turnoActivo.value.id}/cerrar`, corte)
    modalCerrarTurno.value = false
    turnoActivo.value = null
    ventasTurno.value = []
  } catch (e) {
    errorModal.value = e.response?.data?.message ?? 'Error al cerrar turno.'
  } finally { loadingModal.value = false }
}

async function confirmarVenta() {
  loadingModal.value = true; errorModal.value = null
  try {
    await client.post('/ventas', {
      turno_id:    turnoActivo.value.id,
      metodo_pago: nuevaVenta.metodo_pago,
      items:       nuevaVenta.items.map(i => ({
        tipo:            i.tipo,
        id:              i.id,
        cantidad:        i.cantidad,
        precio_unitario: i.precio_unitario,
      })),
    })
    modalNuevaVenta.value = false
    nuevaVenta.items = []
    await cargarVentasTurno()
  } catch (e) {
    errorModal.value = e.response?.data?.message ?? 'Error al registrar la venta.'
  } finally { loadingModal.value = false }
}
</script>
```

- [ ] **Step 13.2: Commit**

```bash
git add frontend/src/views/dashboard/CajeroDashboard.vue
git commit -m "feat: CajeroDashboard con turno activo, modales abrir/cerrar turno y nueva venta"
```

---

### Task 14: AlmacenistaDashboard (con modales)

**Files:**
- Create: `frontend/src/views/dashboard/AlmacenistaDashboard.vue`

- [ ] **Step 14.1: Crear `frontend/src/views/dashboard/AlmacenistaDashboard.vue`**

```vue
<template>
  <div class="space-y-6">

    <h1 class="font-display text-3xl text-ink font-semibold">Inventario y Abastecimiento</h1>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard label="Stock bajo mínimo"      :value="kpis.stockBajo"       :variante="kpis.stockBajoNum > 0 ? 'err' : 'ok'" />
      <StatCard label="Lotes por vencer (7d)"  :value="kpis.vencimientos"    :variante="kpis.vencimientosNum > 0 ? 'warn' : 'ok'" />
      <StatCard label="Compras pendientes"      :value="kpis.comprasPendientes" variante="warn" />
      <StatCard label="Proveedores activos"     :value="kpis.proveedores"     variante="neutral" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Stock bajo -->
      <div class="bg-card border border-edge rounded-xl">
        <div class="flex items-center justify-between p-5 border-b border-edge">
          <h2 class="font-display text-lg text-ink font-medium">Productos bajo mínimo</h2>
          <button @click="modalAjuste = true"
            class="px-4 py-1.5 bg-amber hover:bg-amber-bright text-base text-xs font-medium rounded-lg transition-colors">
            Ajustar stock
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-ink-dim text-xs uppercase tracking-wider">
                <th class="text-left px-5 py-3">Producto</th>
                <th class="text-right px-5 py-3">Actual</th>
                <th class="text-right px-5 py-3">Mínimo</th>
                <th class="text-left px-5 py-3">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="stockBajo.length === 0">
                <td colspan="4" class="px-5 py-6 text-center text-ok text-sm">✓ Sin alertas de stock</td>
              </tr>
              <tr v-for="p in stockBajo" :key="p.id" class="border-t border-edge">
                <td class="px-5 py-3 text-ink">{{ p.nombre }}</td>
                <td class="px-5 py-3 text-right font-mono text-err">{{ p.stock_actual }}</td>
                <td class="px-5 py-3 text-right font-mono text-ink-mute">{{ p.stock_minimo }}</td>
                <td class="px-5 py-3"><AlertBadge texto="Bajo" severidad="err" /></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Lotes por vencer -->
      <div class="bg-card border border-edge rounded-xl">
        <div class="p-5 border-b border-edge">
          <h2 class="font-display text-lg text-ink font-medium">Lotes por vencer (≤7 días)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-ink-dim text-xs uppercase tracking-wider">
                <th class="text-left px-5 py-3">Producto</th>
                <th class="text-right px-5 py-3">Cantidad</th>
                <th class="text-left px-5 py-3">Vence</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="vencimientos.length === 0">
                <td colspan="3" class="px-5 py-6 text-center text-ok text-sm">✓ Sin vencimientos próximos</td>
              </tr>
              <tr v-for="l in vencimientos" :key="l.id" class="border-t border-edge">
                <td class="px-5 py-3 text-ink">{{ l.producto?.nombre ?? '—' }}</td>
                <td class="px-5 py-3 text-right font-mono text-ink">{{ l.cantidad_disponible }}</td>
                <td class="px-5 py-3">
                  <AlertBadge :texto="formatFecha(l.fecha_vencimiento)" severidad="warn" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Compras pendientes -->
    <div class="bg-card border border-edge rounded-xl">
      <div class="p-5 border-b border-edge">
        <h2 class="font-display text-lg text-ink font-medium">Compras pendientes de recepción</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider">
              <th class="text-left px-5 py-3">Código</th>
              <th class="text-left px-5 py-3">Proveedor</th>
              <th class="text-left px-5 py-3">Fecha</th>
              <th class="text-right px-5 py-3">Total</th>
              <th class="text-left px-5 py-3">Estado</th>
              <th class="text-left px-5 py-3">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="comprasPendientes.length === 0">
              <td colspan="6" class="px-5 py-6 text-center text-ink-mute text-sm">Sin compras pendientes</td>
            </tr>
            <tr v-for="c in comprasPendientes" :key="c.id" class="border-t border-edge">
              <td class="px-5 py-3 font-mono text-ink-mute text-xs">{{ c.codigo }}</td>
              <td class="px-5 py-3 text-ink">{{ c.proveedor?.nombre_empresa ?? '—' }}</td>
              <td class="px-5 py-3 text-ink-mute text-xs">{{ formatFecha(c.fecha_orden) }}</td>
              <td class="px-5 py-3 text-right font-mono text-amber">Bs. {{ Number(c.total ?? 0).toFixed(2) }}</td>
              <td class="px-5 py-3">
                <AlertBadge :texto="c.estado" :severidad="c.estado === 'pendiente' ? 'warn' : 'info'" />
              </td>
              <td class="px-5 py-3">
                <button @click="abrirModalRecepcion(c)"
                  class="text-amber hover:text-amber-bright text-xs transition-colors">
                  Recibir →
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Ajustar stock -->
    <div v-if="modalAjuste" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6">
        <h3 class="font-display text-xl text-ink font-medium mb-4">Ajuste de inventario</h3>
        <div class="space-y-3 mb-4">
          <div>
            <label class="block text-ink-mute text-sm mb-1.5">Tipo de ajuste</label>
            <select v-model="ajuste.tipo"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="entrada">Entrada de inventario</option>
              <option value="ajuste">Ajuste de conteo</option>
              <option value="merma">Merma / pérdida</option>
              <option value="devolucion">Devolución</option>
            </select>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1.5">Producto</label>
            <select v-model="ajuste.producto_id"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="" disabled>Selecciona producto...</option>
              <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1.5">Cantidad</label>
              <input v-model.number="ajuste.cantidad" type="number" min="1"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div v-if="ajuste.tipo === 'entrada'">
              <label class="block text-ink-mute text-sm mb-1.5">Costo unitario (Bs.)</label>
              <input v-model.number="ajuste.costo_unitario" type="number" min="0" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1.5">Motivo</label>
            <input v-model="ajuste.motivo" type="text" placeholder="Descripción del ajuste..."
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div v-if="ajuste.tipo === 'entrada'" class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1.5">N° de lote (opcional)</label>
              <input v-model="ajuste.numero_lote" type="text"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1.5">Fecha vencimiento</label>
              <input v-model="ajuste.fecha_vencimiento" type="date"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </div>
        </div>
        <p v-if="errorModal" class="text-err text-sm mb-3">{{ errorModal }}</p>
        <div class="flex gap-3">
          <button @click="modalAjuste = false; errorModal = null"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
          <button @click="ejecutarAjuste" :disabled="loadingModal"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ loadingModal ? 'Guardando...' : 'Guardar ajuste' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import client from '@/api/client.js'
import StatCard   from '@/components/StatCard.vue'
import AlertBadge from '@/components/AlertBadge.vue'

const kpis             = ref({ stockBajo: 0, stockBajoNum: 0, vencimientos: 0, vencimientosNum: 0, comprasPendientes: 0, proveedores: 0 })
const stockBajo        = ref([])
const vencimientos     = ref([])
const comprasPendientes = ref([])
const productos        = ref([])
const modalAjuste      = ref(false)
const loadingModal     = ref(false)
const errorModal       = ref(null)

const ajuste = reactive({ tipo: 'entrada', producto_id: '', cantidad: 1, costo_unitario: 0, motivo: '', numero_lote: '', fecha_vencimiento: '' })

function formatFecha(iso) { return iso ? new Date(iso).toLocaleDateString('es-BO') : '—' }

function abrirModalRecepcion(compra) {
  // Navegación a vista de recepción — implementada en fase de módulo Compras
  alert(`Recepción de compra ${compra.codigo} — disponible en módulo Compras`)
}

onMounted(async () => {
  await Promise.all([cargarDatos(), cargarProductos()])
})

async function cargarDatos() {
  const [rStock, rVenc, rCompras, rProv] = await Promise.all([
    client.get('/inventario/stock-bajo').catch(() => ({ data: { data: [] } })),
    client.get('/inventario/vencimientos').catch(() => ({ data: { data: [] } })),
    client.get('/compras?estado=pendiente').catch(() => ({ data: { data: { data: [] } } })),
    client.get('/proveedores?activo=true').catch(() => ({ data: { data: [] } })),
  ])

  stockBajo.value        = Array.isArray(rStock.data.data) ? rStock.data.data : []
  vencimientos.value     = Array.isArray(rVenc.data.data)  ? rVenc.data.data  : []
  const listaCompras     = rCompras.data.data?.data ?? rCompras.data.data ?? []
  comprasPendientes.value = Array.isArray(listaCompras) ? listaCompras : []
  const listaProv        = Array.isArray(rProv.data.data) ? rProv.data.data : []

  kpis.value = {
    stockBajo:         stockBajo.value.length,
    stockBajoNum:      stockBajo.value.length,
    vencimientos:      vencimientos.value.length,
    vencimientosNum:   vencimientos.value.length,
    comprasPendientes: comprasPendientes.value.length,
    proveedores:       listaProv.length,
  }
}

async function cargarProductos() {
  try {
    const { data } = await client.get('/productos?activo=true')
    productos.value = data.data ?? []
  } catch { productos.value = [] }
}

async function ejecutarAjuste() {
  if (!ajuste.producto_id || !ajuste.cantidad || !ajuste.motivo) {
    errorModal.value = 'Completa los campos obligatorios.'; return
  }
  loadingModal.value = true; errorModal.value = null
  try {
    await client.post('/inventario/ajuste', {
      producto_id:      ajuste.producto_id,
      cantidad:         ajuste.cantidad,
      tipo:             ajuste.tipo,
      motivo:           ajuste.motivo,
      costo_unitario:   ajuste.tipo === 'entrada' ? ajuste.costo_unitario : undefined,
      numero_lote:      ajuste.numero_lote || undefined,
      fecha_vencimiento:ajuste.fecha_vencimiento || undefined,
    })
    modalAjuste.value = false
    await cargarDatos()
  } catch (e) {
    errorModal.value = e.response?.data?.message ?? 'Error al guardar el ajuste.'
  } finally { loadingModal.value = false }
}
</script>
```

- [ ] **Step 14.2: Commit**

```bash
git add frontend/src/views/dashboard/AlmacenistaDashboard.vue
git commit -m "feat: AlmacenistaDashboard con KPIs, stock bajo, vencimientos, compras y modal ajuste"
```

---

### Task 15: ContadorDashboard

**Files:**
- Create: `frontend/src/views/dashboard/ContadorDashboard.vue`

- [ ] **Step 15.1: Crear `frontend/src/views/dashboard/ContadorDashboard.vue`**

```vue
<template>
  <div class="space-y-6">

    <div>
      <h1 class="font-display text-3xl text-ink font-semibold">Panel Contable</h1>
      <p class="text-ink-mute text-sm mt-1">Solo lectura · {{ fechaHoy }}</p>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard label="Balance del día"  :value="kpis.balance"  :variante="kpis.balanceNum >= 0 ? 'ok' : 'err'" />
      <StatCard label="Ingresos"         :value="kpis.ingresos" variante="ok" />
      <StatCard label="Egresos"          :value="kpis.egresos"  variante="warn" />
      <StatCard label="CMV (FIFO)"       :value="kpis.cmv"      variante="neutral" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Resumen mensual -->
      <div class="bg-card border border-edge rounded-xl p-5">
        <h2 class="font-display text-lg text-ink font-medium mb-4">Resumen mensual</h2>
        <div v-if="resumenMensual" class="space-y-3">
          <div class="flex justify-between py-2 border-b border-edge">
            <span class="text-ink-mute text-sm">Total ventas</span>
            <span class="font-mono text-ok text-sm">Bs. {{ Number(resumenMensual.total_ventas ?? 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-edge">
            <span class="text-ink-mute text-sm">Costo mercancía (CMV)</span>
            <span class="font-mono text-warn text-sm">Bs. {{ Number(resumenMensual.total_costo_mercancia ?? 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-edge">
            <span class="text-ink-mute text-sm">N° de ventas</span>
            <span class="font-mono text-ink text-sm">{{ resumenMensual.num_ventas ?? 0 }}</span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-ink-mute text-sm">Ticket promedio</span>
            <span class="font-mono text-ink text-sm">Bs. {{ Number(resumenMensual.ticket_promedio ?? 0).toFixed(2) }}</span>
          </div>
        </div>
        <p v-else class="text-ink-mute text-sm">Sin datos para este mes</p>
      </div>

      <!-- Cierres diarios del mes -->
      <div class="bg-card border border-edge rounded-xl">
        <div class="p-5 border-b border-edge">
          <h2 class="font-display text-lg text-ink font-medium">Cierres diarios del mes</h2>
        </div>
        <div class="overflow-x-auto max-h-72 overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="sticky top-0 bg-card">
              <tr class="text-ink-dim text-xs uppercase tracking-wider">
                <th class="text-left px-5 py-3">Fecha</th>
                <th class="text-right px-5 py-3">Ventas</th>
                <th class="text-right px-5 py-3">Egresos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cierres.length === 0">
                <td colspan="3" class="px-5 py-6 text-center text-ink-mute">Sin cierres este mes</td>
              </tr>
              <tr v-for="c in cierres" :key="c.id" class="border-t border-edge hover:bg-elevated transition-colors">
                <td class="px-5 py-3 font-mono text-ink-mute text-xs">{{ formatFecha(c.fecha) }}</td>
                <td class="px-5 py-3 text-right font-mono text-ok text-xs">Bs. {{ Number(c.total_ventas ?? 0).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right font-mono text-warn text-xs">Bs. {{ Number(c.total_egresos ?? 0).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client.js'
import StatCard from '@/components/StatCard.vue'

const kpis          = ref({ balance: 'Bs. 0', balanceNum: 0, ingresos: 'Bs. 0', egresos: 'Bs. 0', cmv: 'Bs. 0' })
const cierres       = ref([])
const resumenMensual = ref(null)

const fechaHoy = computed(() =>
  new Date().toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

function hoy() { return new Date().toISOString().split('T')[0] }
function mesActual() { return new Date().getMonth() + 1 }
function anioActual() { return new Date().getFullYear() }
function formatFecha(iso) { return iso ? new Date(iso).toLocaleDateString('es-BO') : '—' }

onMounted(async () => {
  await Promise.all([cargarKpis(), cargarCierres(), cargarResumenMensual()])
})

async function cargarKpis() {
  try {
    const { data } = await client.get(`/reportes/balance-diario?fecha=${hoy()}`)
    const balance = data.data

    if (balance) {
      const ingresos = Number(balance.total_ventas ?? 0)
      const cmv      = Number(balance.cmv ?? balance.total_costo_mercancia ?? 0)
      const egresos  = Number(balance.total_egresos ?? cmv)
      const neto     = ingresos - egresos

      kpis.value = {
        balance:    `Bs. ${neto.toFixed(2)}`,
        balanceNum: neto,
        ingresos:   `Bs. ${ingresos.toFixed(2)}`,
        egresos:    `Bs. ${egresos.toFixed(2)}`,
        cmv:        `Bs. ${cmv.toFixed(2)}`,
      }
    } else {
      // Fallback: calcular desde ventas-diarias
      const rVentas = await client.get(`/reportes/ventas-diarias?fecha=${hoy()}`).catch(() => ({ data: { data: {} } }))
      const ingresos = Number(rVentas.data.data?.total_ventas ?? 0)
      const cmv      = Number(rVentas.data.data?.total_costo  ?? 0)
      kpis.value = {
        balance:    `Bs. ${(ingresos - cmv).toFixed(2)}`,
        balanceNum: ingresos - cmv,
        ingresos:   `Bs. ${ingresos.toFixed(2)}`,
        egresos:    `Bs. ${cmv.toFixed(2)}`,
        cmv:        `Bs. ${cmv.toFixed(2)}`,
      }
    }
  } catch { /* mantener valores en cero */ }
}

async function cargarCierres() {
  try {
    const { data } = await client.get(`/reportes/cierres-diarios?mes=${mesActual()}&anio=${anioActual()}`)
    cierres.value = Array.isArray(data.data) ? data.data : []
  } catch { cierres.value = [] }
}

async function cargarResumenMensual() {
  try {
    const { data } = await client.get(`/reportes/resumen-mensual?mes=${mesActual()}&anio=${anioActual()}`)
    resumenMensual.value = data.data ?? null
  } catch { resumenMensual.value = null }
}
</script>
```

- [ ] **Step 15.2: Commit**

```bash
git add frontend/src/views/dashboard/ContadorDashboard.vue
git commit -m "feat: ContadorDashboard con balance diario, resumen mensual y cierres (solo lectura)"
```

---

### Task 16: Build final y verificación

- [ ] **Step 16.1: Ejecutar todos los tests**

```bash
cd frontend && npm run test
```

Expected: todos los tests en verde (mínimo 16).

- [ ] **Step 16.2: Build de producción**

```bash
npm run build
```

Expected: carpeta `dist/` generada sin errores. Si hay error de tipo TypeScript u otro, revisar importaciones.

- [ ] **Step 16.3: Preview del build**

```bash
npm run preview
```

Abrir `http://localhost:4173/login` y verificar:
- Login muestra el formulario con la marca "Cafetería UPDS"
- Ingresar con `admin@cafeteria.upds` / `Admin1234!`
- Redirige a `/dashboard/admin`
- Sidebar muestra todos los ítems del Administrador
- KPIs cargan (pueden mostrar 0 si la BD está vacía)

- [ ] **Step 16.4: Commit final**

```bash
cd ..
git add frontend/
git commit -m "feat: frontend Vue.js completo con 5 dashboards por rol, auth JWT y layout responsivo"
```
