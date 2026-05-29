<template>
  <div class="space-y-6">

    <h1 class="font-display text-3xl text-ink font-semibold">Inventario y Abastecimiento</h1>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard label="Stock bajo mínimo"     :value="kpis.stockBajo"         :variante="kpis.stockBajoNum > 0 ? 'err' : 'ok'" />
      <StatCard label="Lotes por vencer (7d)" :value="kpis.vencimientos"      :variante="kpis.vencimientosNum > 0 ? 'warn' : 'ok'" />
      <StatCard label="Compras pendientes"    :value="kpis.comprasPendientes" variante="warn" />
      <StatCard label="Proveedores activos"   :value="kpis.proveedores"       variante="neutral" />
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
            </tr>
          </thead>
          <tbody>
            <tr v-if="comprasPendientes.length === 0">
              <td colspan="5" class="px-5 py-6 text-center text-ink-mute">Sin compras pendientes</td>
            </tr>
            <tr v-for="c in comprasPendientes" :key="c.id" class="border-t border-edge">
              <td class="px-5 py-3 font-mono text-ink-mute text-xs">{{ c.codigo }}</td>
              <td class="px-5 py-3 text-ink">{{ c.proveedor?.nombre_empresa ?? '—' }}</td>
              <td class="px-5 py-3 text-ink-mute text-xs">{{ formatFecha(c.fecha_orden) }}</td>
              <td class="px-5 py-3 text-right font-mono text-amber">Bs. {{ Number(c.total ?? 0).toFixed(2) }}</td>
              <td class="px-5 py-3">
                <AlertBadge :texto="c.estado" :severidad="c.estado === 'pendiente' ? 'warn' : 'info'" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Ajustar stock -->
    <Teleport to="body">
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
                <label class="block text-ink-mute text-sm mb-1.5">N° lote (opcional)</label>
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
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import client from '@/api/client.js'
import StatCard   from '@/components/StatCard.vue'
import AlertBadge from '@/components/AlertBadge.vue'

const kpis              = ref({ stockBajo: 0, stockBajoNum: 0, vencimientos: 0, vencimientosNum: 0, comprasPendientes: 0, proveedores: 0 })
const stockBajo         = ref([])
const vencimientos      = ref([])
const comprasPendientes = ref([])
const productos         = ref([])
const modalAjuste       = ref(false)
const loadingModal      = ref(false)
const errorModal        = ref(null)

const ajuste = reactive({ tipo: 'entrada', producto_id: '', cantidad: 1, costo_unitario: 0, motivo: '', numero_lote: '', fecha_vencimiento: '' })

function formatFecha(iso) { return iso ? new Date(iso).toLocaleDateString('es-BO') : '—' }

onMounted(() => Promise.all([cargarDatos(), cargarProductos()]))

async function cargarDatos() {
  const [rStock, rVenc, rCompras, rProv] = await Promise.allSettled([
    client.get('/inventario/stock-bajo'),
    client.get('/inventario/vencimientos'),
    client.get('/compras?estado=pendiente'),
    client.get('/proveedores?activo=true'),
  ])

  stockBajo.value    = rStock.status   === 'fulfilled' ? (rStock.value.data.data   ?? []) : []
  vencimientos.value = rVenc.status    === 'fulfilled' ? (rVenc.value.data.data    ?? []) : []
  const listaCompras = rCompras.status === 'fulfilled' ? (rCompras.value.data.data?.data ?? rCompras.value.data.data ?? []) : []
  comprasPendientes.value = Array.isArray(listaCompras) ? listaCompras : []
  const listaProv    = rProv.status    === 'fulfilled' ? (rProv.value.data.data    ?? []) : []

  kpis.value = {
    stockBajo:         stockBajo.value.length,
    stockBajoNum:      stockBajo.value.length,
    vencimientos:      vencimientos.value.length,
    vencimientosNum:   vencimientos.value.length,
    comprasPendientes: comprasPendientes.value.length,
    proveedores:       Array.isArray(listaProv) ? listaProv.length : 0,
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
      producto_id: ajuste.producto_id,
      cantidad:    ajuste.cantidad,
      tipo:        ajuste.tipo,
      motivo:      ajuste.motivo,
      ...(ajuste.tipo === 'entrada' && ajuste.costo_unitario ? { costo_unitario: ajuste.costo_unitario } : {}),
      ...(ajuste.numero_lote       ? { numero_lote: ajuste.numero_lote }             : {}),
      ...(ajuste.fecha_vencimiento ? { fecha_vencimiento: ajuste.fecha_vencimiento } : {}),
    })
    modalAjuste.value = false
    await cargarDatos()
  } catch (e) { errorModal.value = e.response?.data?.message ?? 'Error al guardar el ajuste.' }
  finally { loadingModal.value = false }
}
</script>
