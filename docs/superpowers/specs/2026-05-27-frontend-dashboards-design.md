# Spec: Frontend Dashboards Vue.js — Cafetería UPDS

**Fecha:** 2026-05-27  
**Estado:** Aprobado  
**Autor:** Sesión de brainstorming con usuario

---

## 1. Contexto del proyecto

Sistema de gestión integral de cafetería universitaria (UPDS) con backend Laravel 12 + MySQL ya completado. El frontend Vue.js es la próxima fase. El backend expone 58 endpoints REST bajo `/api/...` protegidos por JWT (`tymon/jwt-auth`) con middleware de permisos granulares por rol.

**Backend existente:**
- Auth: `POST /api/auth/login`, `GET /api/auth/me`, `POST /api/auth/logout`
- Módulos: categorías, clientes, proveedores, productos, menús, usuarios, inventario, turnos, ventas, compras, gastos, reportes
- 5 roles: Administrador, Gerente, Cajero, Almacenista, Contador

---

## 2. Decisiones de arquitectura

| Decisión | Elección | Razón |
|---|---|---|
| Integración | Frontend separado en `/frontend/` | Separación total API / UI, despliegue independiente |
| UI Framework | Vue 3 (Composition API + `<script setup>`) | Especificado por el usuario |
| Estado global | Pinia | Estándar oficial Vue 3 |
| Routing | Vue Router 4 | Estándar oficial Vue 3 |
| Estilos | Tailwind CSS puro (sin component library) | Máximo control visual |
| HTTP client | Axios con interceptors JWT | Ya en el proyecto |
| Charts | Chart.js 4 | Ligero, sin dependencias |
| Tema | Dark Warm "Café Roast" | Identidad de cafetería, aprobado por usuario |

---

## 3. Estructura de archivos

```
frontend/
├── index.html
├── package.json
├── vite.config.js
├── tailwind.config.js
└── src/
    ├── main.js                      # entry point: app + pinia + router
    ├── App.vue                      # root con <RouterView>
    ├── api/
    │   └── client.js                # axios instance + interceptors JWT
    ├── stores/
    │   └── auth.js                  # Pinia: user, rol, token, login(), logout()
    ├── router/
    │   └── index.js                 # rutas + beforeEach guard de rol
    ├── layouts/
    │   └── AppLayout.vue            # shell: sidebar + header + <RouterView>
    ├── components/
    │   ├── AppSidebar.vue           # nav contextual por rol, colapsable
    │   ├── AppHeader.vue            # barra superior fija, notificaciones, user menu
    │   ├── StatCard.vue             # tarjeta de KPI reutilizable
    │   ├── AlertBadge.vue           # badge de alerta (stock bajo, vencimiento)
    │   └── MiniChart.vue            # sparkline Chart.js (línea o barra)
    └── views/
        ├── auth/
        │   ├── LoginView.vue        # formulario de login con JWT
        │   └── RegisterView.vue     # registro de usuario (acceso controlado)
        └── dashboard/
            ├── AdminDashboard.vue
            ├── GerenteDashboard.vue
            ├── CajeroDashboard.vue
            ├── AlmacenistaDashboard.vue
            └── ContadorDashboard.vue
```

---

## 4. Sistema de diseño — Tema "Dark Warm Café Roast"

### Paleta de colores (CSS custom properties)

```css
--bg-base:      #0F0D0C;   /* fondo más profundo */
--bg-surface:   #1A1614;   /* fondo principal app */
--bg-card:      #221E1B;   /* tarjetas y paneles */
--bg-elevated:  #2C2724;   /* hover, dropdowns */
--border:       #38302A;   /* bordes sutiles */

--amber:        #D4821E;   /* acento primario */
--amber-bright: #F0A030;   /* hover de acento */
--amber-glow:   #FFB84D;   /* highlights, badges activos */

--text-primary:   #EDE8E3; /* texto principal */
--text-secondary: #9E8E82; /* labels, subtítulos */
--text-muted:     #6B5A52; /* placeholders */

--green:  #22A77A;         /* éxito / activo */
--red:    #D94040;         /* error / alerta crítica */
--blue:   #3A7BD5;         /* información */
--yellow: #D4A017;         /* advertencia */
```

> El usuario indicó que el sistema visual se revisará y puede cambiar en el futuro. Los valores están centralizados en `tailwind.config.js` y como CSS vars para facilitar el cambio.

### Tipografía (Google Fonts)

| Rol | Fuente | Uso |
|---|---|---|
| Display | `Cormorant Garamond` | Títulos de sección, headings H1–H2 |
| Body/UI | `DM Sans` | Texto general, botones, labels |
| Datos | `JetBrains Mono` | Números, importes, timestamps |

### Componentes base

- **StatCard:** ícono + label + valor grande + delta (% cambio vs ayer). Variante: `positive`, `negative`, `neutral`, `alert`
- **AlertBadge:** píldora de color con texto. Variante por severidad: `critical` (rojo), `warning` (amarillo), `info` (azul)
- **MiniChart:** canvas Chart.js 120×48px. Tipos: `line` (ventas), `bar` (productos)

---

## 5. Autenticación y routing

### Flujo completo

```
GET /login  →  LoginView (público)
               ↓ POST /api/auth/login
               ↓ JWT token + datos de usuario
               ↓ authStore.setUser(user, token)
               ↓ router.push según user.rol
                 → /dashboard/admin
                 → /dashboard/gerente
                 → /dashboard/cajero
                 → /dashboard/almacenista
                 → /dashboard/contador
```

### Router guard (`beforeEach`)

```js
// Si no autenticado → /login
// Si autenticado en /login → redirigir a su dashboard
// Si ruta requiere rol diferente → 403 o dashboard propio
```

### Token management (axios interceptors)

- **Request interceptor:** adjunta `Authorization: Bearer <token>` a cada request
- **Response interceptor:** si recibe `401` → `authStore.logout()` → `router.push('/login')`

### Registro de usuarios

- Ruta: `GET /register` — visible, pero el backend requiere permiso `permisos:usuarios,crear`
- Si el usuario no tiene permiso, el endpoint devuelve 403
- El formulario de registro es usado principalmente por el Administrador para crear nuevas cuentas

---

## 6. Layout shell — AppLayout.vue

Tres zonas:

| Zona | Comportamiento |
|---|---|
| **Sidebar** | Desktop (≥1280px): visible, 260px. Tablet (768–1279px): colapsado a íconos 64px. Móvil (<768px): drawer con overlay oscuro |
| **Header** | Fijo en top, altura 64px. Breadcrumb izquierda, notificaciones + avatar + menú derecha |
| **Content** | `<RouterView>` con padding responsivo. Scroll independiente |

---

## 7. Navegación por rol — AppSidebar.vue

### Administrador (control total)
Dashboard · Usuarios · **Roles & Permisos** · Productos · Menús · Inventario · Ventas · Compras · Turnos · Clientes · Gastos · Contabilidad · Reportes · **⚙ Configuración**

> El Administrador tiene `es_superadmin=true` — bypass total de middleware de permisos en el backend. En el frontend ve todos los módulos más la sección de Configuración exclusiva.

### Gerente
Dashboard · Usuarios · Productos · Menús · Inventario · Ventas · Compras · Turnos · Clientes · Gastos · Contabilidad · Reportes

### Cajero
Dashboard · Ventas · Turnos · Clientes · Menús *(solo lectura)*

### Almacenista
Dashboard · Inventario · Compras · Proveedores · Productos

### Contador
Dashboard · Contabilidad · Reportes *(solo lectura)*

---

## 8. Contenido de cada dashboard

### AdminDashboard.vue

**KPIs (fila superior):**
- Total ventas del día (vs ayer)
- Turnos activos ahora
- Productos con stock bajo (alerta)
- Usuarios activos en el sistema

**Widgets secundarios:**
- Gráfico de línea: ventas de los últimos 7 días (`GET /api/reportes/ventas-diarias`)
- Tabla: log de actividad reciente (`GET /api/auth/me` + actividad)
- Panel: alertas críticas del sistema (stock bajo, lotes por vencer)
- Acciones rápidas: [Nuevo Usuario] [Ver Reportes] [Gestionar Roles]

**Endpoint principal:** `GET /api/reportes/balance-diario`

---

### GerenteDashboard.vue

**KPIs:**
- Ventas hoy vs ayer (con % diferencia)
- Total gastos del día
- Margen bruto estimado
- Turnos abiertos / cerrados

**Widgets:**
- Top 5 productos más vendidos hoy (`GET /api/reportes/productos-vendidos`)
- Alertas de stock bajo (`GET /api/inventario/stock-bajo`)
- Tabla de turnos del día con totales (`GET /api/turnos`)
- Gráfico de barras: ventas por hora del día

**Endpoint principal:** `GET /api/reportes/ventas-diarias` + `GET /api/reportes/balance-diario`

---

### CajeroDashboard.vue

**Enfoque: operativo, no analítico. Acción inmediata.**

**Estado de turno (bloque prominente):**
- Si turno abierto: badge verde "TURNO ACTIVO" + hora apertura + total acumulado
- Si sin turno: mensaje "Sin turno activo" + botón [Abrir Turno]
- Botones de acción grandes: [Nueva Venta] [Cerrar Turno]

**Widgets:**
- Tabla: últimas 10 ventas del turno con botón anular (`GET /api/ventas`)
- Rejilla: menús disponibles del día (`GET /api/menus`)
- Buscador rápido de clientes para asociar venta (`GET /api/clientes`)

**Endpoint principal:** `GET /api/turnos/activo`

---

### AlmacenistaDashboard.vue

**KPIs:**
- Productos con stock bajo (< mínimo)
- Lotes que vencen en los próximos 7 días
- Compras pendientes de recibir
- Proveedores activos

**Widgets:**
- Tabla de alertas de stock con botón [Ver Lotes] (`GET /api/inventario/stock-bajo`)
- Tabla de lotes próximos a vencer (`GET /api/inventario/vencimientos`)
- Tabla de compras en tránsito con botón [Registrar Recepción] (`GET /api/compras`)
- Lista de proveedores recientes (`GET /api/proveedores`)

**Endpoint principal:** `GET /api/inventario/stock-bajo` + `GET /api/inventario/vencimientos`

---

### ContadorDashboard.vue

**Solo lectura — análisis financiero.**

**KPIs:**
- Balance del día (ingresos − egresos)
- Total ingresos
- Total egresos (gastos + CMV)
- CMV del día (costo de mercancía vendida por FIFO)

**Widgets:**
- Gráfico de línea: ingresos vs egresos del mes (`GET /api/reportes/resumen-mensual`)
- Tabla de cierres diarios del mes (`GET /api/reportes/cierres-diarios`)
- Resumen de asientos contables del día (`GET /api/reportes/balance-diario`)
- Panel de resumen mensual con comparativo (`GET /api/reportes/resumen-mensual`)

**Endpoint principal:** `GET /api/reportes/balance-diario`

---

## 9. Responsive breakpoints

| Breakpoint | Sidebar | Cards grid | Tablas |
|---|---|---|---|
| `≥1280px` (desktop) | 260px visible | 4 columnas | Completas |
| `1024–1279px` (laptop) | 260px visible | 3 columnas | Completas |
| `768–1023px` (tablet) | 64px íconos | 2 columnas | Scroll horizontal |
| `<768px` (móvil) | Drawer overlay | 1 columna | Scroll horizontal |

---

## 10. Matriz de acciones CRUD por rol en dashboards

Las siguientes acciones (botones/formularios) aparecen en cada dashboard según los permisos del backend (`routes/api.php`):

| Acción | Admin | Gerente | Cajero | Almacenista | Contador |
|---|:---:|:---:|:---:|:---:|:---:|
| **VENTAS** | | | | | |
| Ver ventas | ✅ | ✅ | ✅ solo su turno | ❌ | ❌ |
| Crear nueva venta | ✅ | ✅ | ✅ | ❌ | ❌ |
| Cancelar venta | ✅ | ✅ | ✅ | ❌ | ❌ |
| **TURNOS** | | | | | |
| Ver turnos | ✅ | ✅ | ✅ solo el propio | ❌ | ❌ |
| Abrir turno | ✅ | ✅ | ✅ | ❌ | ❌ |
| Cerrar turno | ✅ | ✅ | ✅ | ❌ | ❌ |
| **INVENTARIO** | | | | | |
| Ver stock / alertas / vencimientos | ✅ | ✅ | 👁 solo lectura | ✅ | ❌ |
| Ajustar stock | ✅ | ✅ | ❌ | ✅ | ❌ |
| **COMPRAS** | | | | | |
| Ver compras | ✅ | ✅ | ❌ | ✅ | ❌ |
| Crear orden de compra | ✅ | ✅ | ❌ | ✅ | ❌ |
| Recibir / aprobar compra | ✅ | ✅ | ❌ | ✅ | ❌ |
| **CLIENTES** | | | | | |
| Ver clientes | ✅ | ✅ | ✅ | ❌ | ❌ |
| Crear / editar cliente | ✅ | ✅ | ✅ | ❌ | ❌ |
| Canjear puntos | ✅ | ✅ | ✅ | ❌ | ❌ |
| Eliminar cliente | ❌ | ❌ | ❌ | ❌ | ❌ |
| **USUARIOS** | | | | | |
| Ver usuarios | ✅ | ✅ | ❌ | ❌ | ❌ |
| Crear / editar usuario | ✅ | ✅ | ❌ | ❌ | ❌ |
| Eliminar usuario | ✅ | ✅ | ❌ | ❌ | ❌ |
| **GASTOS** | | | | | |
| Ver / crear / editar / eliminar gasto | ✅ | ✅ | ❌ | ❌ | ❌ |
| **REPORTES / CONTABILIDAD** | | | | | |
| Ver reportes y balances | ✅ | ✅ | ❌ | ❌ | ✅ solo lectura |
| Crear / editar asientos contables | ✅ | ✅ | ❌ | ❌ | ❌ |
| **CONFIGURACIÓN** | | | | | |
| Gestionar roles y permisos | ✅ | ❌ | ❌ | ❌ | ❌ |

**Notas técnicas:**
- `clientes` no tiene `destroy` en el backend — nadie puede eliminar clientes (soft-delete via `activo=false`)
- El Cajero solo ve ventas de **su turno activo**, no el historial global
- El Contador es **estrictamente solo lectura** — ningún botón de creación/edición en su dashboard
- El Administrador bypasea todos los middlewares (`es_superadmin=true`) — ve y puede hacer todo

---

## 12. Lo que NO está en scope de esta fase

- Vistas CRUD de cada módulo (Productos, Ventas, Compras, etc.) — solo dashboards
- Formulario de nueva venta completo — solo acceso desde CajeroDashboard
- Exportación PDF/Excel
- Tests unitarios de componentes
- PWA / modo offline
- Roles & Permisos CRUD (RolController pendiente en backend)

---

## 13. Dependencias del proyecto frontend

```json
{
  "dependencies": {
    "vue": "^3.5.0",
    "vue-router": "^4.5.0",
    "pinia": "^2.3.0",
    "axios": "^1.11.0",
    "chart.js": "^4.4.7"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.2.3",
    "vite": "^7.0.0",
    "tailwindcss": "^4.0.0",
    "@tailwindcss/vite": "^4.0.0"
  }
}
```
