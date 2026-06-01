<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Inventario</h1>
        <p class="text-ink-mute text-sm mt-1">Control de lotes, movimientos y alertas</p>
      </div>
      <button @click="abrirModalAjuste()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Ajustar stock
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-elevated/50 border border-edge rounded-lg p-1 w-fit">
      <button v-for="t in tabs" :key="t.key" @click="tabActivo = t.key; cargarTab(t.key)"
        class="px-4 py-2 text-sm rounded-md transition-colors"
        :class="tabActivo === t.key ? 'bg-card text-ink font-medium shadow-sm border border-edge' : 'text-ink-mute hover:text-ink'">
        {{ t.label }}
      </button>
    </div>

    <!-- Stock Bajo -->
    <div v-if="tabActivo === 'stock-bajo'" class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Producto</th>
              <th class="text-left px-5 py-3">Categoría</th>
              <th class="text-right px-5 py-3">Stock Actual</th>
              <th class="text-right px-5 py-3">Stock Mínimo</th>
              <th class="text-right px-5 py-3">Diferencia</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="stockBajo.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-ink-mute">Todo en stock suficiente</td>
            </tr>
            <tr v-for="p in stockBajo" :key="p.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-medium">{{ p.nombre }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ p.categoria?.nombre || '—' }}</td>
              <td class="px-5 py-3 text-right font-mono" :class="Number(p.stock_actual) <= 0 ? 'text-err' : 'text-ink'">{{ p.stock_actual }}</td>
              <td class="px-5 py-3 text-right font-mono text-ink-dim">{{ p.stock_minimo }}</td>
              <td class="px-5 py-3 text-right font-mono" :class="Number(p.stock_actual) - Number(p.stock_minimo) < 0 ? 'text-err' : 'text-ok'">
                {{ Number(p.stock_actual) - Number(p.stock_minimo) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Lotes -->
    <div v-if="tabActivo === 'lotes'">
      <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <select v-model="filtroLoteProducto"
          class="w-full sm:w-56 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
          <option value="">Todos los productos</option>
          <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
        </select>
        <select v-model="filtroLoteEstado"
          class="w-full sm:w-40 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
          <option value="">Todos los estados</option>
          <option value="disponible">Disponible</option>
          <option value="agotado">Agotado</option>
          <option value="vencido">Vencido</option>
        </select>
      </div>
      <div class="bg-card border border-edge rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
                <th class="text-left px-5 py-3">Lote</th>
                <th class="text-left px-5 py-3">Producto</th>
                <th class="text-right px-5 py-3">Cantidad</th>
                <th class="text-center px-5 py-3">Estado</th>
                <th class="text-left px-5 py-3">Ingreso</th>
                <th class="text-left px-5 py-3">Vencimiento</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="lotesFiltrados.length === 0">
                <td colspan="6" class="px-5 py-8 text-center text-ink-mute">Sin lotes registrados</td>
              </tr>
              <tr v-for="l in lotesFiltrados" :key="l.id"
                class="border-t border-edge hover:bg-elevated/30 transition-colors">
                <td class="px-5 py-3 font-mono text-ink-dim text-xs">{{ l.numero_lote || '—' }}</td>
                <td class="px-5 py-3 text-ink text-xs">{{ l.producto?.nombre || '—' }}</td>
                <td class="px-5 py-3 text-right font-mono text-ink-dim text-xs">{{ l.cantidad_disponible ?? l.cantidad_inicial }}</td>
                <td class="px-5 py-3 text-center">
                  <span class="inline-flex px-2 py-0.5 rounded text-xs"
                    :class="{
                      'bg-ok/10 text-ok border border-ok/30': l.estado === 'disponible',
                      'bg-ink-dim/10 text-ink-dim border border-edge': l.estado === 'agotado',
                      'bg-err/10 text-err border border-err/30': l.estado === 'vencido',
                    }">
                    {{ l.estado }}
                  </span>
                </td>
                <td class="px-5 py-3 text-ink-dim text-xs">{{ l.fecha_entrada ? new Date(l.fecha_entrada).toLocaleDateString('es-BO') : '—' }}</td>
                <td class="px-5 py-3 text-ink-dim text-xs" :class="l.fecha_vencimiento && new Date(l.fecha_vencimiento) <= new Date() ? 'text-err' : ''">
                  {{ l.fecha_vencimiento ? new Date(l.fecha_vencimiento).toLocaleDateString('es-BO') : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Movimientos -->
    <div v-if="tabActivo === 'movimientos'">
      <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <select v-model="filtroMovTipo"
          class="w-full sm:w-40 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
          <option value="">Todos los tipos</option>
          <option value="entrada">Entrada</option>
          <option value="salida">Salida</option>
          <option value="ajuste">Ajuste</option>
          <option value="merma">Merma</option>
          <option value="devolucion">Devolución</option>
        </select>
        <input v-model="filtroMovFecha" type="date"
          class="w-full sm:w-44 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
      </div>
      <div class="bg-card border border-edge rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
                <th class="text-left px-5 py-3">Fecha</th>
                <th class="text-left px-5 py-3">Tipo</th>
                <th class="text-left px-5 py-3">Producto</th>
                <th class="text-right px-5 py-3">Cantidad</th>
                <th class="text-left px-5 py-3">Motivo</th>
                <th class="text-left px-5 py-3">Usuario</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="movimientos.length === 0">
                <td colspan="6" class="px-5 py-8 text-center text-ink-mute">Sin movimientos</td>
              </tr>
              <tr v-for="m in movimientos" :key="m.id"
                class="border-t border-edge hover:bg-elevated/30 transition-colors">
                <td class="px-5 py-3 text-ink-dim text-xs">{{ m.fecha ? new Date(m.fecha).toLocaleString('es-BO') : '—' }}</td>
                <td class="px-5 py-3">
                  <span class="inline-flex px-2 py-0.5 rounded text-xs"
                    :class="{
                      'text-ok': m.tipo === 'entrada',
                      'text-amber': m.tipo === 'ajuste',
                      'text-err': m.tipo === 'merma',
                      'text-ink-dim': m.tipo === 'devolucion',
                    }">
                    {{ m.tipo }}
                  </span>
                </td>
                <td class="px-5 py-3 text-ink text-xs">{{ m.producto?.nombre || '—' }}</td>
                <td class="px-5 py-3 text-right font-mono text-xs" :class="m.tipo === 'merma' ? 'text-err' : 'text-ink-dim'">
                  {{ m.cantidad > 0 ? '+' : '' }}{{ m.cantidad }}
                </td>
                <td class="px-5 py-3 text-ink-dim text-xs">{{ m.motivo || '—' }}</td>
                <td class="px-5 py-3 text-ink-dim text-xs">{{ m.usuario?.nombre_completo || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <button v-if="movimientosNextPage" @click="cargarMovimientos(true)"
        class="mt-4 w-full py-3 border border-edge rounded-lg text-sm text-ink-mute hover:text-ink transition-colors">
        Cargar más
      </button>
    </div>

    <!-- Alertas Vencimiento -->
    <div v-if="tabActivo === 'alertas'" class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Producto</th>
              <th class="text-left px-5 py-3">Lote</th>
              <th class="text-right px-5 py-3">Cantidad</th>
              <th class="text-left px-5 py-3">Vencimiento</th>
              <th class="text-left px-5 py-3">Días restantes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="alertas.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-ink-mute">Sin alertas de vencimiento</td>
            </tr>
            <tr v-for="a in alertas" :key="a.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-medium text-xs">{{ a.producto?.nombre || '—' }}</td>
              <td class="px-5 py-3 font-mono text-ink-dim text-xs">{{ a.numero_lote || '—' }}</td>
              <td class="px-5 py-3 text-right font-mono text-ink-dim text-xs">{{ a.cantidad_disponible }}</td>
              <td class="px-5 py-3 text-xs" :class="new Date(a.fecha_vencimiento) <= new Date() ? 'text-err font-medium' : 'text-ink-dim'">
                {{ a.fecha_vencimiento ? new Date(a.fecha_vencimiento).toLocaleDateString('es-BO') : '—' }}
              </td>
              <td class="px-5 py-3 text-xs" :class="diasRestantes(a.fecha_vencimiento) <= 3 ? 'text-err font-medium' : 'text-amber'">
                {{ diasRestantes(a.fecha_vencimiento) }} días
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  <!-- Modal Ajustar Stock -->
  <Teleport to="body">
    <div v-if="modalAjuste" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6">
        <h3 class="font-display text-xl text-ink font-medium mb-4">Ajustar stock</h3>
        <div class="space-y-3">
          <div>
            <label class="block text-ink-mute text-sm mb-1">Producto *</label>
            <select v-model="ajuste.producto_id"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="" disabled>Selecciona producto...</option>
              <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Tipo *</label>
            <select v-model="ajuste.tipo"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="entrada">Entrada</option>
              <option value="ajuste">Ajuste</option>
              <option value="merma">Merma</option>
              <option value="devolucion">Devolución</option>
            </select>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Cantidad *</label>
            <input v-model.number="ajuste.cantidad" type="number" min="1"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Motivo *</label>
            <input v-model="ajuste.motivo" type="text"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <template v-if="ajuste.tipo === 'entrada'">
            <div>
              <label class="block text-ink-mute text-sm mb-1">Costo unitario</label>
              <input v-model.number="ajuste.costo_unitario" type="number" min="0" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Número de lote</label>
              <input v-model="ajuste.numero_lote" type="text"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Fecha vencimiento</label>
              <input v-model="ajuste.fecha_vencimiento" type="date"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </template>
        </div>
        <p v-if="errorAjuste" class="text-err text-sm mt-4">{{ errorAjuste }}</p>
        <div class="flex gap-3 mt-5">
          <button @click="cerrarModalAjuste"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
          <button @click="ejecutarAjuste" :disabled="ajustando"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ ajustando ? 'Ajustando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client.js'

const tabs = [
  { key: 'stock-bajo', label: 'Stock Bajo' },
  { key: 'lotes',      label: 'Lotes' },
  { key: 'movimientos',label: 'Movimientos' },
  { key: 'alertas',    label: 'Alertas' },
]

const tabActivo = ref('stock-bajo')

const stockBajo = ref([])
const lotes     = ref([])
const movimientos = ref([])
const alertas   = ref([])
const productos = ref([])

const filtroLoteProducto = ref('')
const filtroLoteEstado   = ref('')
const filtroMovTipo      = ref('')
const filtroMovFecha     = ref('')

const movimientosNextPage = ref(null)
const movimientosLoading  = ref(false)

const lotesFiltrados = computed(() => {
  return lotes.value.filter(l => {
    if (filtroLoteProducto.value && l.producto_id !== Number(filtroLoteProducto.value)) return false
    if (filtroLoteEstado.value && l.estado !== filtroLoteEstado.value) return false
    return true
  })
})

function diasRestantes(fecha) {
  if (!fecha) return 0
  const diff = new Date(fecha) - new Date()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}

onMounted(() => {
  cargarProductos()
  cargarTab('stock-bajo')
})

function cargarTab(tab) {
  if (tab === 'stock-bajo') cargarStockBajo()
  else if (tab === 'lotes') cargarLotes()
  else if (tab === 'movimientos') { movimientos.value = []; movimientosNextPage.value = null; cargarMovimientos() }
  else if (tab === 'alertas') cargarAlertas()
}

async function cargarProductos() {
  try {
    const { data } = await client.get('/productos')
    productos.value = data.data ?? []
  } catch { productos.value = [] }
}

async function cargarStockBajo() {
  try {
    const { data } = await client.get('/inventario/stock-bajo')
    stockBajo.value = data.data ?? []
  } catch { stockBajo.value = [] }
}

async function cargarLotes() {
  try {
    const { data } = await client.get('/inventario/lotes')
    lotes.value = data.data ?? []
  } catch { lotes.value = [] }
}

async function cargarMovimientos(append = false) {
  if (movimientosLoading.value) return
  movimientosLoading.value = true
  try {
    const params = {}
    if (filtroMovTipo.value) params.tipo = filtroMovTipo.value
    if (filtroMovFecha.value) params.fecha = filtroMovFecha.value
    if (append && movimientosNextPage.value) params.page = movimientosNextPage.value

    const { data } = await client.get('/inventario/movimientos', { params })
    const res = data.data ?? data
    const list = res.data ?? res

    if (append) {
      movimientos.value = [...movimientos.value, ...list]
    } else {
      movimientos.value = list
    }

    movimientosNextPage.value = res.next_page_url ? (res.current_page + 1) : null
  } catch { movimientos.value = [] }
  finally { movimientosLoading.value = false }
}

async function cargarAlertas() {
  try {
    const { data } = await client.get('/inventario/vencimientos')
    alertas.value = data.data ?? []
  } catch { alertas.value = [] }
}

// Ajustar stock
const modalAjuste = ref(false)
const ajustando   = ref(false)
const errorAjuste = ref(null)
const ajuste      = ref({
  producto_id: '',
  tipo: 'entrada',
  cantidad: null,
  motivo: '',
  costo_unitario: null,
  numero_lote: '',
  fecha_vencimiento: '',
})

function abrirModalAjuste() {
  errorAjuste.value = null
  ajuste.value = { producto_id: '', tipo: 'entrada', cantidad: null, motivo: '', costo_unitario: null, numero_lote: '', fecha_vencimiento: '' }
  modalAjuste.value = true
}

function cerrarModalAjuste() {
  modalAjuste.value = false
  errorAjuste.value = null
}

async function ejecutarAjuste() {
  ajustando.value = true; errorAjuste.value = null
  try {
    const payload = { ...ajuste.value }
    if (payload.tipo !== 'entrada') {
      delete payload.costo_unitario
      delete payload.numero_lote
      delete payload.fecha_vencimiento
    }
    await client.post('/inventario/ajuste', payload)
    cerrarModalAjuste()
    await Promise.all([cargarStockBajo(), cargarLotes()])
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorAjuste.value = Object.values(errs).flat().join('. ')
    } else {
      errorAjuste.value = e.response?.data?.message || 'Error al ajustar stock.'
    }
  } finally { ajustando.value = false }
}
</script>
