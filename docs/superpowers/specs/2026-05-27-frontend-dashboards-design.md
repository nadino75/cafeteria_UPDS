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
GET /login  →  LoginView (público, sin auth)
               ↓ POST /api/auth/login
               ↓ JWT token + datos de usuario (incluye rol)
               ↓ authStore.setUser(user, token)  → localStorage
               ↓ router.push según user.rol
                 → /dashboard/admin
                 → /dashboard/gerente
                 → /dashboard/cajero
                 → /dashboard/almacenista
                 → /dashboard/contador
```

### Router guard (`beforeEach`)

```js
// Rutas públicas: /login únicamente
// Si no autenticado → /login
// Si autenticado intentando /login → redirigir a su dashboard
// Si ruta tiene meta.rol y el rol no coincide → redirigir a su dashboard propio
// Al iniciar app: verificar token en localStorage → GET /api/auth/me para rehidratar store
```

### Token management (axios interceptors)

- **Request interceptor:** adjunta `Authorization: Bearer <token>` a cada request
- **Response interceptor:** si recibe `401` → `authStore.logout()` → `router.push('/login')`

### Registro / creación de usuarios

- Ruta: `/register` — **protegida**, solo accesible para rol `Administrador` (router guard con `meta.rol = 'Administrador'`)
- El formulario envía a `POST /api/usuarios` (requiere autenticación + permiso del backend)
- Si un usuario con otro rol intenta acceder a `/register` → redirigido a su dashboard
- El formulario incluye: nombre completo, email, password, selección de rol

---

## 6. Layout shell — AppLayout.vue

Tres zonas:

| Zona | Comportamiento |
|---|---|
| **Sidebar** | `≥1024px`: visible, 260px fijo. `768–1023px`: colapsado a íconos 64px (toggle manual). `<768px`: oculto por defecto, drawer overlay con botón hamburguesa en header |
| **Header** | Fijo en top, altura 64px. Botón hamburguesa (solo móvil) + breadcrumb izquierda · notificaciones + avatar + menú derecha |
| **Content** | `<RouterView>` con padding responsivo (`p-6` desktop, `p-4` tablet/móvil). Scroll independiente del sidebar |

---

## 7. Navegación por rol — AppSidebar.vue

### Administrador (control total)
Dashboard · Usuarios · Productos · Categorías · Menús · Inventario · Ventas · Compras · Turnos · Clientes · Gastos · Contabilidad · Reportes · **⚙ Roles & Permisos** *(deshabilitado — RolController pendiente en backend)* · **⚙ Configuración**

> El Administrador tiene `es_superadmin=true` — bypass total de middleware de permisos en el backend. "Roles & Permisos" aparece en el sidebar pero con estado `disabled` y tooltip "Próximamente" hasta que se implemente el `RolController` en el backend.

### Gerente
Dashboard · Usuarios *(solo lectura)* · Productos · Categorías · Menús · Inventario · Ventas · Compras · Turnos · Clientes · Gastos · Contabilidad · Reportes

### Cajero
Dashboard · Ventas · Turnos · Clientes · Menús *(solo lectura)*

### Almacenista
Dashboard · Inventario · Categorías · Productos · Compras · Proveedores

> "Categorías" aparece bajo el grupo "Inventario" en el sidebar del Almacenista (puede crear/editar pero no eliminar).

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
- Gráfico de línea: ventas de los últimos 7 días — llamar `GET /api/reportes/ventas-diarias` 7 veces (una por fecha) en paralelo con `Promise.all`
- Tabla: últimas 10 ventas del día (`GET /api/ventas?fecha=hoy&per_page=10`) — reemplaza log de actividad (no existe endpoint de log en la API)
- Panel: alertas críticas del sistema (`GET /api/inventario/stock-bajo` + `GET /api/inventario/vencimientos`)
- Acciones rápidas: [Nuevo Usuario → /register] [Ver Reportes → /reportes] [Roles & Permisos → disabled]

**Endpoints requeridos:**
- `GET /api/reportes/balance-diario` — KPIs financieros (puede retornar `null` si no hay cierre; fallback: usar datos de `ventas-diarias`)
- `GET /api/turnos?estado=abierto` — count de turnos activos
- `GET /api/inventario/stock-bajo` — count de productos bajo mínimo
- `GET /api/usuarios` — count de usuarios activos (`activo=true`)

---

### GerenteDashboard.vue

**KPIs:**
- Ventas hoy vs ayer (con % diferencia)
- Total gastos del día
- Margen bruto estimado
- Turnos abiertos / cerrados

**Widgets:**
- Top 5 productos más vendidos del mes (`GET /api/reportes/productos-vendidos?desde=inicio_mes&hasta=hoy`)
- Alertas de stock bajo (`GET /api/inventario/stock-bajo`)
- Tabla de turnos del día con totales (`GET /api/turnos?fecha=hoy`)
- Gráfico de barras: ventas agrupadas por hora — obtener con `GET /api/ventas?fecha=hoy` y agrupar por `hora(fecha)` en el cliente (el backend no tiene endpoint por hora)

**Endpoints requeridos:**
- `GET /api/reportes/ventas-diarias?fecha=hoy` y `?fecha=ayer` — para calcular % de cambio
- `GET /api/reportes/balance-diario?fecha=hoy` — margen bruto (fallback a cálculo manual si null)
- `GET /api/gastos?fecha=hoy` — total gastos del día
- `GET /api/turnos?fecha=hoy` — estado de turnos

---

### CajeroDashboard.vue

**Enfoque: operativo, no analítico. Acción inmediata.**

**Estado de turno (bloque prominente):**
- Si turno abierto: badge verde "TURNO ACTIVO" + hora apertura + total acumulado del turno
- Si sin turno: mensaje "Sin turno activo" + botón [Abrir Turno] (abre modal con campo `caja_inicial`)
- Botones de acción condicionales: [Nueva Venta] *(visible solo si hay turno activo)* · [Cerrar Turno] *(visible solo si hay turno activo, abre modal con formulario de corte de caja — billetes, totales)*

**Modal "Nueva Venta"** *(en scope — simplificado)*:
- Seleccionar ítems de menú o productos de una lista
- Cantidad y precio unitario
- Seleccionar cliente (opcional) con buscador
- Método de pago: efectivo / tarjeta / transferencia / mixto
- Botón [Confirmar Venta] → `POST /api/ventas`

**Modal "Abrir Turno"**: campo numérico `caja_inicial` → `POST /api/turnos/abrir`

**Modal "Cerrar Turno"**: campos de billetes (200, 100, 50, 20, 10), monedas, observaciones → `POST /api/turnos/{id}/cerrar`

**Widgets:**
- Tabla: últimas 10 ventas del turno activo — **solo lectura**, sin botón cancelar (Cajero no tiene `ventas,editar`) → `GET /api/ventas?turno_id=X`
- Rejilla de tarjetas: menús disponibles (`GET /api/menus?activo=true`) — solo lectura, permite seleccionar para nueva venta
- Buscador rápido de clientes (`GET /api/clientes?search=X`) — para asociar a la venta

**Endpoints requeridos:**
- `GET /api/turnos/activo` — estado del turno del usuario actual
- `GET /api/ventas?turno_id=X` — ventas del turno activo
- `GET /api/menus?activo=true` — menús disponibles
- `GET /api/clientes?search=X` — búsqueda de clientes

---

### AlmacenistaDashboard.vue

**KPIs:**
- Productos con stock bajo (< mínimo)
- Lotes que vencen en los próximos 7 días
- Compras pendientes de recibir
- Proveedores activos

**Widgets:**
- Tabla de productos con stock bajo + botón [Ajustar Stock] (abre modal de ajuste) → `GET /api/inventario/stock-bajo`
- Tabla de lotes próximos a vencer ≤7 días → `GET /api/inventario/vencimientos`
- Tabla de compras pendientes/parciales + botón [Registrar Recepción] → `GET /api/compras?estado=pendiente`
- Lista de proveedores activos → `GET /api/proveedores?activo=true`

**Modal "Ajustar Stock"**: producto, cantidad, tipo (entrada/ajuste/merma/devolución), motivo, lote, fecha vencimiento → `POST /api/inventario/ajuste`

**Modal "Registrar Recepción"**: por cada ítem de la compra: cantidad recibida, número de lote, fecha vencimiento → `POST /api/compras/{id}/recibir`

**Endpoints requeridos:**
- `GET /api/inventario/stock-bajo` — productos bajo mínimo
- `GET /api/inventario/vencimientos` — lotes venciendo en 7 días
- `GET /api/compras?estado=pendiente` — compras por recibir (también `?estado=parcial`)
- `GET /api/proveedores?activo=true` — proveedores activos

---

### ContadorDashboard.vue

**Solo lectura — análisis financiero.**

**KPIs:**
- Balance del día (ingresos − egresos)
- Total ingresos
- Total egresos (gastos + CMV)
- CMV del día (costo de mercancía vendida por FIFO)

**Widgets:**
- Gráfico de línea: ingresos vs egresos — llamar `GET /api/reportes/ventas-diarias` para los últimos 30 días (agrupado por semana en cliente) y `GET /api/gastos?fecha=X` para egresos
- Tabla de cierres diarios del mes → `GET /api/reportes/cierres-diarios?mes=X&anio=Y`
- Panel de resumen mensual → `GET /api/reportes/resumen-mensual?mes=X&anio=Y`
- KPI de balance del día → `GET /api/reportes/balance-diario?fecha=hoy` *(puede ser null si no hay cierre diario; en ese caso calcular: ingresos de `ventas-diarias` − gastos del día)*

> **Nota:** No existe endpoint de "asientos contables" en la API actual. El ContadorDashboard muestra solo los datos de reportes disponibles. Los asientos contables quedan fuera de scope hasta que se implemente un módulo de contabilidad con endpoints propios.

**Endpoints requeridos:**
- `GET /api/reportes/balance-diario?fecha=hoy`
- `GET /api/reportes/ventas-diarias?fecha=hoy`
- `GET /api/reportes/cierres-diarios?mes=X&anio=Y`
- `GET /api/reportes/resumen-mensual?mes=X&anio=Y`

---

## 9. Responsive breakpoints

Definición única y consistente (corrige contradicción previa entre §6 y §9):

| Breakpoint | Sidebar | Grid KPIs | Tablas |
|---|---|---|---|
| `≥1024px` (desktop/laptop) | 260px fijo visible | 4 columnas | Completas con paginación |
| `768–1023px` (tablet) | 64px íconos (colapsado, toggle manual) | 2 columnas | Scroll horizontal con `overflow-x-auto` |
| `<768px` (móvil) | Drawer overlay (botón hamburguesa en header) | 1 columna | Scroll horizontal con `overflow-x-auto` |

**Reglas adicionales responsive:**
- Texto de sidebar oculto cuando colapsado (`<768px` y tablet colapsado) — solo íconos
- Header siempre visible a 64px en todos los breakpoints
- Modales: `max-w-lg w-full mx-4` en móvil, `max-w-xl` en desktop
- Cards de KPIs: altura mínima fija 120px para evitar colapso visual en móvil

---

## 10. Matriz exacta de acciones CRUD por rol (verificada contra controladores reales)

Basado en análisis de los 13 controladores + `RolPermisosSeeder.php` + `routes/api.php`.

### Notas técnicas del backend que afectan el frontend

- **Ventas, Turnos y Compras no tienen `update()` ni `destroy()`** — son registros inmutables por diseño
- **GastoOperativo no tiene `update()`** — solo crear y eliminar
- **`destroy()` es soft-delete en**: Producto (`activo=false`), Usuario (`activo=false`), Menú (`activo=false`), Proveedor (`activo=false`)
- **`destroy()` es DELETE físico en**: GastoOperativo, Categoría (con validación de integridad referencial)
- **Cajero NO puede cancelar ventas** — seeder le asigna solo `ventas:[crear,leer]`, sin `editar`; la ruta `cancelar` requiere `ventas,editar`
- **Gerente es solo lectura en Usuarios** — seeder le asigna solo `usuarios,leer`; el backend no impide crear/editar (gap de seguridad en rutas), el frontend NO muestra esos botones
- **Almacenista tiene CRUD completo en Proveedores** — la ruta usa `permisos:compras,leer` para todas las operaciones de proveedores
- **Productos usan `inventario,leer` para todo el CRUD** — el Almacenista (que tiene `inventario,leer`) puede crear/editar/desactivar productos
- **Registro de usuarios** — no hay ruta pública `/register`; el frontend usa `POST /api/usuarios` que requiere autenticación. La vista `RegisterView.vue` es para uso del Administrador

### AdminDashboard — bypass total (`es_superadmin=true`)

| Módulo | Ver | Crear | Editar | Cancelar / Desactivar / Eliminar |
|---|:---:|:---:|:---:|:---:|
| Ventas | ✅ global | ✅ | — | ✅ cancelar (`ventas,editar`) |
| Turnos | ✅ todos | ✅ abrir | — | ✅ cerrar (`turnos,aprobar`) |
| Inventario lotes | ✅ | ✅ entrada FIFO | ✅ ajuste/merma | — |
| Productos | ✅ | ✅ | ✅ precio/stock\_min | ✅ desactivar (soft) |
| Categorías | ✅ | ✅ | ✅ | ✅ eliminar físico |
| Menús + ingredientes | ✅ | ✅ | ✅ reemplaza ingredientes | ✅ desactivar (soft) |
| Compras | ✅ | ✅ orden de compra | — | ✅ recibir/aprobar |
| Proveedores | ✅ | ✅ | ✅ | ✅ desactivar (soft) |
| Clientes | ✅ | ✅ | ✅ + canjear puntos | ❌ no expuesto en backend |
| Gastos operativos | ✅ | ✅ | ❌ no existe update | ✅ eliminar físico |
| Usuarios | ✅ | ✅ + asigna rol | ✅ + resetea password | ✅ desactivar (soft) |
| Reportes | ✅ todos | — | — | — |

### GerenteDashboard — todos los módulos excepto gestión de usuarios

| Módulo | Ver | Crear | Editar | Cancelar / Desactivar / Eliminar |
|---|:---:|:---:|:---:|:---:|
| Ventas | ✅ global | ✅ | — | ✅ cancelar |
| Turnos | ✅ todos | ✅ abrir | — | ✅ cerrar |
| Inventario lotes | ✅ | ✅ entrada FIFO | ✅ ajuste/merma | — |
| Productos | ✅ | ✅ | ✅ | ✅ desactivar |
| Categorías | ✅ | ✅ | ✅ | ✅ eliminar |
| Menús | ✅ | ✅ | ✅ | ✅ desactivar |
| Compras | ✅ | ✅ | — | ✅ recibir |
| Proveedores | ✅ | ✅ | ✅ | ✅ desactivar |
| Clientes | ✅ | ✅ | ✅ + canjear puntos | ❌ |
| Gastos | ✅ | ✅ | ❌ no existe | ✅ eliminar |
| **Usuarios** | ✅ solo ver | ❌ | ❌ | ❌ |
| Reportes | ✅ todos | — | — | — |

### CajeroDashboard — punto de venta operativo

| Módulo | Ver | Crear | Editar | Cancelar / Cerrar |
|---|:---:|:---:|:---:|:---:|
| Ventas | ✅ solo su turno | ✅ | ❌ sin permiso | ❌ sin permiso (`ventas,editar` requerido) |
| Turnos | ✅ solo su turno | ✅ abrir | — | ✅ cerrar |
| Clientes | ✅ búsqueda | ✅ | ✅ + canjear puntos | ❌ |
| Inventario | ✅ solo lectura | ❌ | ❌ | — |
| Menús | ✅ solo lectura | ❌ | ❌ | — |

### AlmacenistaDashboard — inventario y abastecimiento

| Módulo | Ver | Crear | Editar / Ajustar | Desactivar / Aprobar |
|---|:---:|:---:|:---:|:---:|
| Inventario lotes | ✅ | ✅ entrada FIFO | ✅ ajuste/merma | — |
| Productos | ✅ | ✅ | ✅ | ✅ desactivar |
| Categorías | ✅ | ✅ | ✅ | ❌ sin `inventario,eliminar` |
| Compras | ✅ | ✅ orden | — | ✅ recibir |
| Proveedores | ✅ | ✅ | ✅ | ✅ desactivar |
| Clientes | ✅ solo lista | ❌ | ❌ | — |

### ContadorDashboard — exclusivamente lectura y exportación

| Módulo | Ver | Crear | Editar | Eliminar |
|---|:---:|:---:|:---:|:---:|
| Reportes (todos) | ✅ | ❌ | ❌ | ❌ |
| Contabilidad | ✅ | ❌ | ❌ | ❌ |

> El Contador tiene `exportar` en contabilidad y reportes — se agrega botón [Exportar] en su dashboard cuando se implemente esa funcionalidad.

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

> **Nota Tailwind v4:** No se usa `tailwind.config.js`. La configuración del tema (colores, fuentes) se define con la directiva `@theme {}` dentro del archivo CSS principal (`src/assets/main.css`). Ejemplo:
> ```css
> @import "tailwindcss";
> @theme {
>   --color-amber: #D4821E;
>   --color-bg-card: #221E1B;
>   --font-display: "Cormorant Garamond", serif;
> }
> ```
