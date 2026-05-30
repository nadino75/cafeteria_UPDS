<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Compras</h1>
        <p class="text-ink-mute text-sm mt-1">Órdenes de compra y recepción de inventario</p>
      </div>
      <button @click="abrirModalNueva()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nueva compra
      </button>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <input v-model="filtro" type="text" placeholder="Buscar por código..."
        class="w-full sm:w-64 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
      <select v-model="filtroEstado"
        class="w-full sm:w-44 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="parcial">Parcial</option>
        <option value="recibida">Recibida</option>
        <option value="cancelada">Cancelada</option>
      </select>
      <select v-model="filtroProveedor"
        class="w-full sm:w-56 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
        <option value="">Todos los proveedores</option>
        <option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.nombre_empresa }}</option>
      </select>
    </div>

    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Código</th>
              <th class="text-left px-5 py-3">Proveedor</th>
              <th class="text-left px-5 py-3">Fecha</th>
              <th class="text-center px-5 py-3">Estado</th>
              <th class="text-right px-5 py-3">Total</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="compras.length === 0">
              <td colspan="6" class="px-5 py-8 text-center text-ink-mute">Sin órdenes de compra</td>
            </tr>
            <tr v-for="c in compras" :key="c.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-mono text-xs">{{ c.codigo }}</td>
              <td class="px-5 py-3 text-ink">{{ c.proveedor?.nombre_empresa || '—' }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ c.fecha_orden ? c.fecha_orden.slice(0, 10) : '—' }}</td>
              <td class="px-5 py-3 text-center">
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="estadoClass(c.estado)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="estadoDotClass(c.estado)" />
                  {{ c.estado }}
                </span>
              </td>
              <td class="px-5 py-3 text-right font-mono text-amber">Bs. {{ Number(c.total).toFixed(2) }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button @click="verDetalle(c)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">Ver</button>
                  <span v-if="c.estado === 'pendiente' || c.estado === 'parcial'" class="text-edge-lit">|</span>
                  <button v-if="c.estado === 'pendiente' || c.estado === 'parcial'" @click="abrirRecibir(c)"
                    class="text-ink-mute hover:text-ok text-xs font-medium transition-colors">Recibir</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2">
      <button @click="pagina--" :disabled="pagina <= 1"
        class="px-3 py-1.5 border border-edge rounded-lg text-xs text-ink-mute hover:text-ink disabled:opacity-40 transition-colors">
        Anterior
      </button>
      <span class="text-xs text-ink-mute">Pág. {{ pagina }} de {{ totalPages }}</span>
      <button @click="pagina++" :disabled="pagina >= totalPages"
        class="px-3 py-1.5 border border-edge rounded-lg text-xs text-ink-mute hover:text-ink disabled:opacity-40 transition-colors">
        Siguiente
      </button>
    </div>

    <!-- Modal: Nueva compra -->
    <Teleport to="body">
      <div v-if="modalNueva" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-xl p-6 my-4">
          <h3 class="font-display text-xl text-ink font-medium mb-4">Nueva orden de compra</h3>
          <div class="space-y-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1">Proveedor *</label>
              <select v-model="formNueva.proveedor_id"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
                <option value="">Seleccionar...</option>
                <option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.nombre_empresa }}</option>
              </select>
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Nota</label>
              <textarea v-model="formNueva.nota" rows="2"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber"></textarea>
            </div>

            <div class="pt-3 border-t border-edge">
              <div class="flex items-center justify-between mb-2">
                <label class="text-ink-mute text-sm font-medium">Productos *</label>
                <button @click="agregarItem" type="button"
                  class="text-xs text-amber hover:text-amber-bright font-medium transition-colors">+ Agregar</button>
              </div>
              <div v-for="(item, i) in formNueva.items" :key="i"
                class="flex items-start gap-2 mb-2 bg-elevated/50 p-2 rounded-lg">
                <select v-model="item.producto_id"
                  class="flex-1 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber">
                  <option value="" disabled>Producto...</option>
                  <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
                <input v-model.number="item.cantidad_ordenada" type="number" min="1" step="1" placeholder="Cant."
                  class="w-20 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
                <input v-model.number="item.costo_unitario" type="number" min="0" step="0.01" placeholder="Costo"
                  class="w-24 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
                <button @click="formNueva.items.splice(i, 1)" type="button"
                  class="text-err hover:text-err/70 p-1" title="Eliminar">✕</button>
              </div>
              <p v-if="formNueva.items.length === 0" class="text-ink-dim text-xs italic">Agrega al menos un producto</p>
            </div>
          </div>

          <p v-if="errorForm" class="text-err text-sm mt-4">{{ errorForm }}</p>

          <div class="flex gap-3 mt-5">
            <button @click="modalNueva = false"
              class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
            <button @click="guardarNueva" :disabled="guardando"
              class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ guardando ? 'Guardando...' : 'Crear orden' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Recibir compra -->
    <Teleport to="body">
      <div v-if="modalRecibir" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-2xl p-6 my-4">
          <h3 class="font-display text-xl text-ink font-medium mb-4">
            Recibir: {{ compraRecibir?.codigo }}
          </h3>
          <p class="text-ink-mute text-sm mb-4">Registra las cantidades recibidas para cada producto</p>

          <div class="space-y-3">
            <div v-for="(item, i) in formRecibir.items" :key="i"
              class="bg-elevated/50 border border-edge rounded-lg p-3 space-y-2">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-sm text-ink font-medium">{{ item.producto_nombre }}</p>
                  <p class="text-xs text-ink-dim">Ordenado: {{ item.cantidad_ordenada }} | Recibido antes: {{ item.cantidad_recibida_anterior }}</p>
                </div>
              </div>
              <div class="grid grid-cols-3 gap-2">
                <div>
                  <label class="block text-ink-mute text-xs mb-1">Recibir *</label>
                  <input v-model.number="item.cantidad_recibida" type="number" min="1"
                    class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
                </div>
                <div>
                  <label class="block text-ink-mute text-xs mb-1">N° Lote</label>
                  <input v-model="item.numero_lote" type="text" placeholder="Opcional"
                    class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
                </div>
                <div>
                  <label class="block text-ink-mute text-xs mb-1">Vencimiento</label>
                  <input v-model="item.fecha_vencimiento" type="date"
                    class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
                </div>
              </div>
            </div>
          </div>

          <p v-if="errorRecibir" class="text-err text-sm mt-4">{{ errorRecibir }}</p>

          <div class="flex gap-3 mt-5">
            <button @click="cerrarRecibir"
              class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
            <button @click="enviarRecibir" :disabled="guardandoRecibir"
              class="flex-1 bg-ok hover:bg-ok/80 text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ guardandoRecibir ? 'Guardando...' : 'Confirmar recepción' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Ver detalle -->
    <Teleport to="body">
      <div v-if="modalDetalle" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-2xl p-6 my-4">
          <h3 class="font-display text-xl text-ink font-medium mb-1">Detalle de compra</h3>
          <p class="font-mono text-xs text-ink-dim mb-4">{{ detalle?.codigo }}</p>

          <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
            <div>
              <span class="text-ink-mute">Proveedor:</span>
              <p class="text-ink font-medium">{{ detalle?.proveedor?.nombre_empresa || '—' }}</p>
            </div>
            <div>
              <span class="text-ink-mute">Fecha:</span>
              <p class="text-ink">{{ detalle?.fecha_orden?.slice(0, 10) || '—' }}</p>
            </div>
            <div>
              <span class="text-ink-mute">Estado:</span>
              <p>
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="estadoClass(detalle?.estado)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="estadoDotClass(detalle?.estado)" />
                  {{ detalle?.estado }}
                </span>
              </p>
            </div>
            <div v-if="detalle?.fecha_recepcion">
              <span class="text-ink-mute">Recibido:</span>
              <p class="text-ink">{{ detalle.fecha_recepcion.slice(0, 10) }}</p>
            </div>
          </div>

          <p v-if="detalle?.nota" class="text-sm text-ink-mute mb-4 italic">"{{ detalle.nota }}"</p>

          <div class="border-t border-edge pt-3">
            <table class="w-full text-xs">
              <thead>
                <tr class="text-ink-dim uppercase tracking-wider">
                  <th class="text-left pb-2">Producto</th>
                  <th class="text-right pb-2">Ord.</th>
                  <th class="text-right pb-2">Rec.</th>
                  <th class="text-right pb-2">Costo</th>
                  <th class="text-right pb-2">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in detalle?.detalles" :key="d.id" class="border-t border-edge">
                  <td class="py-2 text-ink">{{ d.producto?.nombre || '—' }}</td>
                  <td class="py-2 text-right text-ink-dim">{{ d.cantidad_ordenada }}</td>
                  <td class="py-2 text-right text-ink-dim">{{ d.cantidad_recibida || 0 }}</td>
                  <td class="py-2 text-right text-ink-dim">Bs. {{ Number(d.costo_unitario).toFixed(2) }}</td>
                  <td class="py-2 text-right font-mono text-amber">Bs. {{ Number(d.subtotal).toFixed(2) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="border-t border-edge">
                  <td colspan="4" class="py-2 text-right text-ink font-medium">Total</td>
                  <td class="py-2 text-right font-mono text-amber font-medium">Bs. {{ Number(detalle?.total).toFixed(2) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div class="flex gap-3 mt-5">
            <button @click="modalDetalle = false"
              class="w-full border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cerrar</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import client from '@/api/client.js'

const compras          = ref([])
const proveedores      = ref([])
const productos        = ref([])
const filtro           = ref('')
const filtroEstado     = ref('')
const filtroProveedor  = ref('')
const pagina           = ref(1)
const totalPages       = ref(1)

const comprasFiltradas = computed(() => {
  let items = compras.value
  const q = filtro.value.toLowerCase()
  if (q) items = items.filter(c => c.codigo.toLowerCase().includes(q))
  return items
})

async function cargarCompras() {
  try {
    const params = { page: pagina.value }
    if (filtroEstado.value) params.estado = filtroEstado.value
    if (filtroProveedor.value) params.proveedor_id = filtroProveedor.value
    const { data } = await client.get('/compras', { params })
    compras.value = data.data?.data ?? []
    totalPages.value = data.data?.last_page ?? 1
  } catch { compras.value = [] }
}

async function cargarProveedores() {
  try {
    const { data } = await client.get('/proveedores')
    proveedores.value = data.data ?? []
  } catch { proveedores.value = [] }
}

async function cargarProductos() {
  try {
    const { data } = await client.get('/productos')
    productos.value = data.data ?? []
  } catch { productos.value = [] }
}

watch([filtroEstado, filtroProveedor, pagina], () => { cargarCompras() })

onMounted(() => Promise.all([cargarCompras(), cargarProveedores(), cargarProductos()]))

function estadoClass(estado) {
  const map = { pendiente: 'text-warn border border-warn/30', parcial: 'text-warn border border-warn/30', recibida: 'text-ok border border-ok/30', cancelada: 'text-err border border-err/30' }
  return map[estado] || 'text-ink-dim border border-edge'
}
function estadoDotClass(estado) {
  const map = { pendiente: 'bg-warn', parcial: 'bg-warn', recibida: 'bg-ok', cancelada: 'bg-err' }
  return map[estado] || 'bg-ink-dim'
}

// ── Modal: Nueva compra ──────────────────────────────────────────────────────
const modalNueva  = ref(false)
const guardando   = ref(false)
const errorForm   = ref(null)
const formNueva   = ref({ proveedor_id: '', nota: '', items: [] })

function agregarItem() {
  formNueva.value.items.push({ producto_id: '', cantidad_ordenada: null, costo_unitario: null })
}

function abrirModalNueva() {
  errorForm.value = null
  formNueva.value = { proveedor_id: '', nota: '', items: [] }
  modalNueva.value = true
}

async function guardarNueva() {
  guardando.value = true; errorForm.value = null
  try {
    await client.post('/compras', formNueva.value)
    modalNueva.value = false
    pagina.value = 1
    await cargarCompras()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) errorForm.value = Object.values(errs).flat().join('. ')
    else errorForm.value = e.response?.data?.message || 'Error al crear la compra.'
  } finally { guardando.value = false }
}

// ── Modal: Recibir compra ────────────────────────────────────────────────────
const modalRecibir      = ref(false)
const guardandoRecibir  = ref(false)
const errorRecibir      = ref(null)
const compraRecibir     = ref(null)
const formRecibir       = ref({ items: [] })

async function abrirRecibir(compra) {
  errorRecibir.value = null
  guardandoRecibir.value = false
  try {
    const { data } = await client.get(`/compras/${compra.id}`)
    compraRecibir.value = data.data ?? compra

    const detalles = compraRecibir.value.detalles || []
    formRecibir.value.items = detalles.map(d => ({
      detalle_compra_id: d.id,
      producto_nombre: d.producto?.nombre || `Producto #${d.producto_id}`,
      cantidad_ordenada: d.cantidad_ordenada,
      cantidad_recibida_anterior: d.cantidad_recibida || 0,
      cantidad_recibida: null,
      numero_lote: '',
      fecha_vencimiento: '',
    }))
    modalRecibir.value = true
  } catch {
    errorRecibir.value = 'Error al cargar detalle de la compra.'
  }
}

function cerrarRecibir() {
  modalRecibir.value = false
  compraRecibir.value = null
  errorRecibir.value = null
}

async function enviarRecibir() {
  guardandoRecibir.value = true; errorRecibir.value = null
  try {
    const payload = {
      items: formRecibir.value.items.map(i => ({
        detalle_compra_id: i.detalle_compra_id,
        cantidad_recibida: i.cantidad_recibida,
        numero_lote: i.numero_lote || null,
        fecha_vencimiento: i.fecha_vencimiento || null,
      })),
    }
    await client.post(`/compras/${compraRecibir.value.id}/recibir`, payload)
    cerrarRecibir()
    await cargarCompras()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) errorRecibir.value = Object.values(errs).flat().join('. ')
    else errorRecibir.value = e.response?.data?.message || 'Error al recibir la compra.'
  } finally { guardandoRecibir.value = false }
}

// ── Modal: Ver detalle ──────────────────────────────────────────────────────
const modalDetalle = ref(false)
const detalle      = ref(null)

async function verDetalle(compra) {
  try {
    const { data } = await client.get(`/compras/${compra.id}`)
    detalle.value = data.data ?? compra
    modalDetalle.value = true
  } catch {
    alert('Error al cargar detalle.')
  }
}
</script>
