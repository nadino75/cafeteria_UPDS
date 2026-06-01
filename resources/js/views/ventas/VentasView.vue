<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Ventas</h1>
        <p class="text-ink-mute text-sm mt-1">Registro de ventas y transacciones</p>
      </div>
      <button @click="abrirModalNueva()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nueva venta
      </button>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <input v-model="filtroFecha" type="date"
        class="w-full sm:w-48 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
      <select v-model="filtroEstado"
        class="w-full sm:w-44 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
        <option value="">Todos los estados</option>
        <option value="completada">Completada</option>
        <option value="cancelada">Cancelada</option>
      </select>
    </div>

    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">#</th>
              <th class="text-left px-5 py-3">Fecha</th>
              <th class="text-left px-5 py-3">Cliente</th>
              <th class="text-center px-5 py-3">Método</th>
              <th class="text-center px-5 py-3">Items</th>
              <th class="text-center px-5 py-3">Estado</th>
              <th class="text-right px-5 py-3">Total</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="ventas.length === 0">
              <td colspan="8" class="px-5 py-8 text-center text-ink-mute">Sin ventas registradas</td>
            </tr>
            <tr v-for="v in ventas" :key="v.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-mono text-xs">{{ v.id }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ v.fecha ? v.fecha.slice(0, 16).replace('T', ' ') : '—' }}</td>
              <td class="px-5 py-3 text-ink">{{ v.cliente?.nombre || '—' }}</td>
              <td class="px-5 py-3 text-center">
                <span class="inline-flex text-xs px-2 py-0.5 rounded border border-edge text-ink-mute">
                  {{ v.metodo_pago }}
                </span>
              </td>
              <td class="px-5 py-3 text-center text-ink-dim text-xs">{{ v.detalles?.length ?? 0 }}</td>
              <td class="px-5 py-3 text-center">
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="v.estado === 'cancelada' ? 'text-err border border-err/30' : 'text-ok border border-ok/30'">
                  <span class="w-1.5 h-1.5 rounded-full" :class="v.estado === 'cancelada' ? 'bg-err' : 'bg-ok'" />
                  {{ v.estado }}
                </span>
              </td>
              <td class="px-5 py-3 text-right font-mono text-amber">Bs. {{ Number(v.total).toFixed(2) }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button @click="verDetalle(v)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">Ver</button>
                  <span v-if="v.estado === 'completada'" class="text-edge-lit">|</span>
                  <button v-if="v.estado === 'completada'" @click="cancelarVenta(v)"
                    class="text-ink-mute hover:text-err text-xs font-medium transition-colors">Cancelar</button>
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

    <!-- Modal: Nueva venta -->
    <Teleport to="body">
      <div v-if="modalNueva" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-xl p-6 my-4">
          <h3 class="font-display text-xl text-ink font-medium mb-4">Nueva venta</h3>
          <div class="space-y-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1">Turno *</label>
              <select v-model="formNueva.turno_id"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
                <option value="">Seleccionar...</option>
                <option v-for="t in turnos" :key="t.id" :value="t.id">
                  #{{ t.id }} — {{ t.fecha_apertura?.slice(0, 10) }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Método de pago *</label>
              <select v-model="formNueva.metodo_pago"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
                <option value="">Seleccionar...</option>
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
                <option value="qr">QR</option>
                <option value="mixto">Mixto</option>
              </select>
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Cliente</label>
              <select v-model="formNueva.cliente_id"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
                <option value="">Sin cliente</option>
                <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-ink-mute text-sm mb-1">Descuento (Bs.)</label>
                <input v-model.number="formNueva.descuento" type="number" min="0" step="0.01"
                  class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
              </div>
              <div>
                <label class="block text-ink-mute text-sm mb-1">Impuesto (Bs.)</label>
                <input v-model.number="formNueva.impuesto" type="number" min="0" step="0.01"
                  class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
              </div>
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Nota</label>
              <textarea v-model="formNueva.nota" rows="2"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber"></textarea>
            </div>

            <div class="pt-3 border-t border-edge">
              <div class="flex items-center justify-between mb-2">
                <label class="text-ink-mute text-sm font-medium">Items *</label>
                <button @click="agregarItem" type="button"
                  class="text-xs text-amber hover:text-amber-bright font-medium transition-colors">+ Agregar</button>
              </div>
              <div v-for="(item, i) in formNueva.items" :key="i"
                class="flex flex-wrap items-start gap-2 mb-2 bg-elevated/50 p-2 rounded-lg">
                <select v-model="item.tipo" @change="item.id = ''"
                  class="w-24 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber">
                  <option value="producto">Producto</option>
                  <option value="menu">Menú</option>
                </select>
                <select v-model="item.id"
                  class="flex-1 min-w-[120px] bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber">
                  <option value="" disabled>Seleccionar...</option>
                  <option v-for="p in item.tipo === 'producto' ? productos : menus" :key="p.id" :value="p.id">
                    {{ p.nombre }}
                  </option>
                </select>
                <input v-model.number="item.cantidad" type="number" min="1" step="1" placeholder="Cant."
                  class="w-16 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
                <input v-model.number="item.precio_unitario" type="number" min="0" step="0.01" placeholder="$"
                  class="w-20 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
                <input v-model.number="item.descuento" type="number" min="0" step="0.01" placeholder="Desc."
                  class="w-16 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
                <button @click="formNueva.items.splice(i, 1)" type="button"
                  class="text-err hover:text-err/70 p-1" title="Eliminar">✕</button>
              </div>
              <p v-if="formNueva.items.length === 0" class="text-ink-dim text-xs italic">Agrega al menos un item</p>
            </div>
          </div>

          <p v-if="errorForm" class="text-err text-sm mt-4">{{ errorForm }}</p>

          <div class="flex gap-3 mt-5">
            <button @click="modalNueva = false"
              class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
            <button @click="guardarNueva" :disabled="guardando"
              class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ guardando ? 'Guardando...' : 'Registrar venta' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Ver detalle -->
    <Teleport to="body">
      <div v-if="modalDetalle" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-2xl p-6 my-4">
          <h3 class="font-display text-xl text-ink font-medium mb-1">Venta #{{ detalle?.id }}</h3>
          <p class="text-ink-dim text-xs mb-4">{{ detalle?.fecha?.slice(0, 16).replace('T', ' ') }}</p>

          <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
            <div>
              <span class="text-ink-mute">Cliente:</span>
              <p class="text-ink font-medium">{{ detalle?.cliente?.nombre || '—' }}</p>
            </div>
            <div>
              <span class="text-ink-mute">Cajero:</span>
              <p class="text-ink">{{ detalle?.usuario?.nombre || '—' }}</p>
            </div>
            <div>
              <span class="text-ink-mute">Método:</span>
              <p class="text-ink">{{ detalle?.metodo_pago }}</p>
            </div>
            <div>
              <span class="text-ink-mute">Estado:</span>
              <p>
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="detalle?.estado === 'cancelada' ? 'text-err border border-err/30' : 'text-ok border border-ok/30'">
                  <span class="w-1.5 h-1.5 rounded-full" :class="detalle?.estado === 'cancelada' ? 'bg-err' : 'bg-ok'" />
                  {{ detalle?.estado }}
                </span>
              </p>
            </div>
          </div>

          <p v-if="detalle?.nota" class="text-sm text-ink-mute mb-4 italic">"{{ detalle.nota }}"</p>

          <div class="border-t border-edge pt-3">
            <table class="w-full text-xs">
              <thead>
                <tr class="text-ink-dim uppercase tracking-wider">
                  <th class="text-left pb-2">Item</th>
                  <th class="text-center pb-2">Tipo</th>
                  <th class="text-right pb-2">Cant.</th>
                  <th class="text-right pb-2">Precio</th>
                  <th class="text-right pb-2">Desc.</th>
                  <th class="text-right pb-2">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in detalle?.detalles" :key="d.id" class="border-t border-edge">
                  <td class="py-2 text-ink">{{ d.producto?.nombre || d.menu?.nombre || '—' }}</td>
                  <td class="py-2 text-center text-ink-dim">{{ d.tipo_item }}</td>
                  <td class="py-2 text-right text-ink-dim">{{ d.cantidad }}</td>
                  <td class="py-2 text-right text-ink-dim">Bs. {{ Number(d.precio_unitario).toFixed(2) }}</td>
                  <td class="py-2 text-right text-ink-dim">{{ Number(d.descuento_item).toFixed(2) }}</td>
                  <td class="py-2 text-right font-mono text-amber">Bs. {{ Number(d.subtotal).toFixed(2) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="border-t border-edge">
                  <td colspan="5" class="py-2 text-right text-ink-muted text-xs">Subtotal</td>
                  <td class="py-2 text-right text-ink-dim">Bs. {{ Number(detalle?.subtotal).toFixed(2) }}</td>
                </tr>
                <tr v-if="Number(detalle?.descuento) > 0">
                  <td colspan="5" class="py-1 text-right text-err text-xs">Descuento</td>
                  <td class="py-1 text-right text-err">- Bs. {{ Number(detalle?.descuento).toFixed(2) }}</td>
                </tr>
                <tr v-if="Number(detalle?.impuesto) > 0">
                  <td colspan="5" class="py-1 text-right text-ink-muted text-xs">Impuesto</td>
                  <td class="py-1 text-right text-ink-dim">+ Bs. {{ Number(detalle?.impuesto).toFixed(2) }}</td>
                </tr>
                <tr class="border-t border-edge">
                  <td colspan="5" class="py-2 text-right text-ink font-medium">Total</td>
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

const ventas        = ref([])
const turnos        = ref([])
const productos     = ref([])
const menus         = ref([])
const clientes      = ref([])
const filtroFecha   = ref('')
const filtroEstado  = ref('')
const pagina        = ref(1)
const totalPages    = ref(1)

async function cargarVentas() {
  try {
    const params = { page: pagina.value }
    if (filtroFecha.value) params.fecha = filtroFecha.value
    if (filtroEstado.value) params.estado = filtroEstado.value
    const { data } = await client.get('/ventas', { params })
    ventas.value = data.data?.data ?? []
    totalPages.value = data.data?.last_page ?? 1
  } catch { ventas.value = [] }
}

async function cargarTurnos() {
  try {
    const { data } = await client.get('/turnos')
    turnos.value = data.data ?? []
  } catch { turnos.value = [] }
}

async function cargarProductos() {
  try {
    const { data } = await client.get('/productos')
    productos.value = data.data ?? []
  } catch { productos.value = [] }
}

async function cargarMenus() {
  try {
    const { data } = await client.get('/menus')
    menus.value = data.data ?? []
  } catch { menus.value = [] }
}

async function cargarClientes() {
  try {
    const { data } = await client.get('/clientes')
    clientes.value = data.data ?? []
  } catch { clientes.value = [] }
}

watch([filtroFecha, filtroEstado, pagina], () => { cargarVentas() })

onMounted(() => Promise.all([cargarVentas(), cargarTurnos(), cargarProductos(), cargarMenus(), cargarClientes()]))

// ── Modal: Nueva venta ───────────────────────────────────────────────────────
const modalNueva = ref(false)
const guardando  = ref(false)
const errorForm  = ref(null)
const formNueva  = ref({
  turno_id: '', metodo_pago: '', cliente_id: '', descuento: 0, impuesto: 0, nota: '', items: [],
})

function agregarItem() {
  formNueva.value.items.push({ tipo: 'producto', id: '', cantidad: 1, precio_unitario: null, descuento: 0 })
}

function abrirModalNueva() {
  errorForm.value = null
  formNueva.value = { turno_id: '', metodo_pago: '', cliente_id: '', descuento: 0, impuesto: 0, nota: '', items: [] }
  modalNueva.value = true
}

async function guardarNueva() {
  guardando.value = true; errorForm.value = null
  try {
    await client.post('/ventas', formNueva.value)
    modalNueva.value = false
    pagina.value = 1
    await cargarVentas()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) errorForm.value = Object.values(errs).flat().join('. ')
    else errorForm.value = e.response?.data?.message || 'Error al registrar la venta.'
  } finally { guardando.value = false }
}

// ── Modal: Ver detalle + Cancelar ──────────────────────────────────────────
const modalDetalle = ref(false)
const detalle      = ref(null)

async function verDetalle(venta) {
  try {
    const { data } = await client.get(`/ventas/${venta.id}`)
    detalle.value = data.data ?? venta
    modalDetalle.value = true
  } catch {
    alert('Error al cargar detalle.')
  }
}

async function cancelarVenta(venta) {
  if (!confirm(`¿Estás seguro de cancelar la venta #${venta.id}?`)) return
  try {
    await client.post(`/ventas/${venta.id}/cancelar`)
    await cargarVentas()
  } catch (e) {
    alert(e.response?.data?.message || 'Error al cancelar la venta.')
  }
}
</script>
