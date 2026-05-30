# Productos Module — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build CRUD management page for Productos (list, create, edit, deactivate) with Vue + existing API.

**Architecture:** Single `ProductosView.vue` under `/dashboard/productos` route. Table shows all products; modal handles create/edit; confirm dialog handles deactivate. Follows the same inline pattern as all 5 dashboard views (script setup, ref, onMounted, direct client.get/post).

**Tech Stack:** Vue 3 (Composition API), Vue Router 4, Tailwind v4 (Café Roast theme), Axios (client.js)

**Spec:** `docs/superpowers/specs/2026-05-30-productos-module-design.md`

---

## File Structure

| File | Action | Responsibility |
|------|--------|---------------|
| `resources/js/router/index.js` | Modify | Add child route `/dashboard/productos` |
| `resources/js/components/AppSidebar.vue` | Modify | Fix 3 sidebar links from `/productos` → `/dashboard/productos` |
| `resources/js/views/productos/ProductosView.vue` | Create | Full page: table + modal + deactivate |

---

### Task 1: Routing + Sidebar Fix

**Files:**
- Modify: `resources/js/router/index.js`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Add `/dashboard/productos` route in router**

Add this child route inside the `/dashboard` parent (after the contador route):

```js
{
  path: 'productos',
  component: () => import('@/views/productos/ProductosView.vue'),
  meta: { rolRequerido: 'Administrador' },
},
```

Edit `resources/js/router/index.js`. Find the last child route (contador), add the new route after it, before the closing `]` of the `children` array.

- [ ] **Step 2: Fix sidebar links from `/productos` to `/dashboard/productos`**

In `resources/js/components/AppSidebar.vue`, find all sidebar entries with `ruta: '/productos'`:

Three roles reference this:
- `Administrador` — line 120: `label: 'Productos', ruta: '/productos'`
- `Gerente` — line 138: `label: 'Productos', ruta: '/productos'`
- `Almacenista` — line 160: `label: 'Productos', ruta: '/productos'`

Change each from `'/productos'` to `'/dashboard/productos'`.

- [ ] **Step 3: Verify routing works**

Run the dev server, login as admin, navigate to `http://127.0.0.1:8000/dashboard/productos`. Should see the page (empty — no component content yet).

---

### Task 2: Create ProductosView.vue — Template

**Files:**
- Create: `resources/js/views/productos/ProductosView.vue`

- [ ] **Step 1: Create file with template (header, filters, table)**

```vue
<template>
  <div class="space-y-6">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Productos</h1>
        <p class="text-ink-mute text-sm mt-1">Gestión de productos del catálogo</p>
      </div>
      <button @click="abrirModal()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nuevo producto
      </button>
    </div>

    <!-- Filtros -->
    <div class="flex flex-col sm:flex-row gap-3">
      <input v-model="filtroNombre" type="text" placeholder="Buscar por nombre..."
        class="w-full sm:w-64 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
      <select v-model="filtroCategoria"
        class="w-full sm:w-48 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
        <option value="">Todas las categorías</option>
        <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
      </select>
    </div>

    <!-- Tabla -->
    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Nombre</th>
              <th class="text-left px-5 py-3">Código</th>
              <th class="text-left px-5 py-3">Categoría</th>
              <th class="text-right px-5 py-3">Precio Venta</th>
              <th class="text-right px-5 py-3">Costo Unit.</th>
              <th class="text-right px-5 py-3">Stock</th>
              <th class="text-center px-5 py-3">Activo</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="productosFiltrados.length === 0">
              <td colspan="8" class="px-5 py-8 text-center text-ink-mute">Sin productos registrados</td>
            </tr>
            <tr v-for="p in productosFiltrados" :key="p.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-medium">{{ p.nombre }}</td>
              <td class="px-5 py-3 font-mono text-ink-dim text-xs">{{ p.codigo || '—' }}</td>
              <td class="px-5 py-3">
                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-elevated text-ink-mute border border-edge">
                  {{ p.categoria?.nombre || '—' }}
                </span>
              </td>
              <td class="px-5 py-3 text-right font-mono text-amber">Bs. {{ Number(p.precio_venta).toFixed(2) }}</td>
              <td class="px-5 py-3 text-right font-mono text-ink-mute">Bs. {{ Number(p.costo_unitario).toFixed(2) }}</td>
              <td class="px-5 py-3 text-right font-mono"
                :class="Number(p.stock_actual) <= Number(p.stock_minimo) ? 'text-err' : 'text-ink'">
                {{ p.stock_actual }}
              </td>
              <td class="px-5 py-3 text-center">
                <span v-if="p.activo" class="inline-flex items-center gap-1 text-ok text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-ok" /> Sí
                </span>
                <span v-else class="inline-flex items-center gap-1 text-err text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-err" /> No
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button @click="abrirModal(p)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">
                    Editar
                  </button>
                  <span class="text-edge-lit">|</span>
                  <button @click="desactivar(p)"
                    class="text-ink-mute hover:text-err text-xs font-medium transition-colors">
                    Desactivar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>
```

- [ ] **Step 2: Add script section with data fetching + filtering logic**

```vue
<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client.js'

const productos      = ref([])
const categorias     = ref([])
const filtroNombre   = ref('')
const filtroCategoria = ref('')
const cargando       = ref(true)

const productosFiltrados = computed(() => {
  return productos.value.filter(p => {
    const matchNombre = p.nombre.toLowerCase().includes(filtroNombre.value.toLowerCase())
    const matchCat = !filtroCategoria.value || p.categoria_id === Number(filtroCategoria.value)
    return matchNombre && matchCat
  })
})

onMounted(() => Promise.all([cargarProductos(), cargarCategorias()]))

async function cargarProductos() {
  cargando.value = true
  try {
    const { data } = await client.get('/productos')
    productos.value = data.data ?? []
  } catch { productos.value = [] }
  finally { cargando.value = false }
}

async function cargarCategorias() {
  try {
    const { data } = await client.get('/categorias')
    categorias.value = data.data ?? []
  } catch { categorias.value = [] }
}
</script>
```

- [ ] **Step 3: Quick smoke test**

Load `http://127.0.0.1:8000/dashboard/productos`. Table should render with products (if seed data exists) or "Sin productos registrados". Filter should work client-side.

---

### Task 3: Add Create/Edit Modal

**Files:**
- Modify: `resources/js/views/productos/ProductosView.vue`

- [ ] **Step 1: Add reactive state for modal**

After the existing refs, add:

```vue
const modalAbierto  = ref(false)
const modoEdicion   = ref(false)
const guardando     = ref(false)
const errorForm     = ref(null)
const form          = ref({
  nombre: '',
  codigo: '',
  categoria_id: '',
  precio_venta: null,
  costo_unitario: null,
  stock_minimo: 0,
  unidad_medida: 'unidad',
  requiere_lote: false,
})
```

- [ ] **Step 2: Add `abrirModal` function**

```vue
function abrirModal(producto = null) {
  errorForm.value = null
  if (producto) {
    modoEdicion.value = true
    form.value = {
      nombre: producto.nombre,
      codigo: producto.codigo || '',
      categoria_id: producto.categoria_id,
      precio_venta: producto.precio_venta,
      costo_unitario: producto.costo_unitario,
      stock_minimo: producto.stock_minimo || 0,
      unidad_medida: producto.unidad_medida || 'unidad',
      requiere_lote: producto.requiere_lote || false,
    }
    form.value._id = producto.id
  } else {
    modoEdicion.value = false
    form.value = { nombre: '', codigo: '', categoria_id: '', precio_venta: null, costo_unitario: null, stock_minimo: 0, unidad_medida: 'unidad', requiere_lote: false }
  }
  modalAbierto.value = true
}
```

- [ ] **Step 3: Add `guardar` function**

```vue
async function guardar() {
  guardando.value = true; errorForm.value = null
  try {
    if (modoEdicion.value) {
      await client.put(`/productos/${form.value._id}`, form.value)
    } else {
      await client.post('/productos', form.value)
    }
    modalAbierto.value = false
    await cargarProductos()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorForm.value = Object.values(errs).flat().join('. ')
    } else {
      errorForm.value = e.response?.data?.message || 'Error al guardar el producto.'
    }
  }
  finally { guardando.value = false }
}
```

- [ ] **Step 4: Add modal template (inside `<div class="space-y-6">` at the end, before closing tag)**

```vue
  <!-- Modal Crear / Editar -->
  <Teleport to="body">
    <div v-if="modalAbierto" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6">
        <h3 class="font-display text-xl text-ink font-medium mb-4">
          {{ modoEdicion ? 'Editar producto' : 'Nuevo producto' }}
        </h3>

        <div class="space-y-3">
          <div>
            <label class="block text-ink-mute text-sm mb-1">Nombre *</label>
            <input v-model="form.nombre" type="text"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Código</label>
            <input v-model="form.codigo" type="text" placeholder="Ej: PROD-001"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Categoría *</label>
            <select v-model="form.categoria_id"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="" disabled>Selecciona categoría...</option>
              <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1">Precio venta *</label>
              <input v-model.number="form.precio_venta" type="number" min="0" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Costo unitario *</label>
              <input v-model.number="form.costo_unitario" type="number" min="0" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1">Stock mínimo</label>
              <input v-model.number="form.stock_minimo" type="number" min="0"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Unidad medida</label>
              <select v-model="form.unidad_medida"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
                <option value="unidad">Unidad</option>
                <option value="kg">Kilogramo (kg)</option>
                <option value="lt">Litro (lt)</option>
                <option value="g">Gramo (g)</option>
                <option value="ml">Mililitro (ml)</option>
              </select>
            </div>
          </div>
          <label class="flex items-center gap-2">
            <input v-model="form.requiere_lote" type="checkbox"
              class="rounded border-edge bg-elevated text-amber focus:ring-amber" />
            <span class="text-ink-mute text-sm">Requiere lote (control FIFO)</span>
          </label>
        </div>

        <p v-if="errorForm" class="text-err text-sm mt-4">{{ errorForm }}</p>

        <div class="flex gap-3 mt-5">
          <button @click="modalAbierto = false; errorForm = null"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">
            Cancelar
          </button>
          <button @click="guardar" :disabled="guardando"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
```

- [ ] **Step 5: Test create flow**

Login as admin, go to `/dashboard/productos`, click "+ Nuevo producto", fill form, click "Guardar". Product should appear in table.

- [ ] **Step 6: Test edit flow**

Click "Editar" on any product row. Modal should open pre-filled. Change a field, save. Table should reflect changes.

---

### Task 4: Add Deactivate

**Files:**
- Modify: `resources/js/views/productos/ProductosView.vue`

- [ ] **Step 1: Add `desactivar` function**

```vue
async function desactivar(producto) {
  if (!confirm(`¿Estás seguro de desactivar "${producto.nombre}"?`)) return
  try {
    await client.delete(`/productos/${producto.id}`)
    await cargarProductos()
  } catch {
    alert('Error al desactivar el producto.')
  }
}
```

- [ ] **Step 2: Test deactivate**

Click "Desactivar" on a product. Confirm dialog appears. Accept. Product should disappear from table (or show activo = No if the API returns it with activo=false — the controller sets `activo = false` but keeps the product in DB).

**Note:** The API returns all products by default (no `?activo=true` filter) in the index, so deactivated products will appear with "No" badge in the activo column.

---

## Self-Review

- [ ] **Spec coverage:** Every spec requirement has a task — routing (Task 1), table with columns (Task 2), modal form (Task 3), deactivate (Task 4), filters (Task 2 table header), error handling (Task 3 guard function). No gaps.
- [ ] **Placeholder scan:** No TBD, TODO, or "implement later" patterns. Every code block is complete.
- [ ] **Type consistency:** `form.value` field names match ProductoController validation rules (`nombre`, `codigo`, `categoria_id`, `precio_venta`, `costo_unitario`, `stock_minimo`, `unidad_medida`, `requiere_lote`). Method names (`abrirModal`, `guardar`, `desactivar`) are consistent.
