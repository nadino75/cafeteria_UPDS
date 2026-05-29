<template>
  <div class="space-y-6">

    <h1 class="font-display text-3xl text-ink font-semibold">Mi turno</h1>

    <!-- Estado del turno -->
    <div class="bg-card border rounded-xl p-6" :class="turnoActivo ? 'border-ok/30' : 'border-edge'">
      <div v-if="cargandoTurno" class="text-ink-mute text-sm">Verificando turno...</div>

      <!-- Sin turno -->
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
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="bg-elevated rounded-lg p-3">
            <p class="text-ink-dim text-xs">Apertura</p>
            <p class="font-mono text-ink text-sm mt-1">{{ formatHora(turnoActivo.fecha_apertura) }}</p>
          </div>
          <div class="bg-elevated rounded-lg p-3">
            <p class="text-ink-dim text-xs">Caja inicial</p>
            <p class="font-mono text-amber text-sm mt-1">Bs. {{ Number(turnoActivo.caja_inicial ?? 0).toFixed(2) }}</p>
          </div>
          <div class="bg-elevated rounded-lg p-3">
            <p class="text-ink-dim text-xs">Ventas</p>
            <p class="font-mono text-ink text-sm mt-1">{{ ventasTurno.length }}</p>
          </div>
          <div class="bg-elevated rounded-lg p-3">
            <p class="text-ink-dim text-xs">Total acumulado</p>
            <p class="font-mono text-ok text-sm mt-1">Bs. {{ totalTurno }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla ventas del turno (solo lectura) -->
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
    <Teleport to="body">
      <div v-if="modalAbrirTurno" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-sm p-6">
          <h3 class="font-display text-xl text-ink font-medium mb-4">Abrir turno</h3>
          <label class="block text-ink-mute text-sm mb-1.5">Caja inicial (Bs.)</label>
          <input v-model.number="cajaInicial" type="number" min="0" step="0.01" placeholder="0.00"
            class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber mb-4" />
          <p v-if="errorModal" class="text-err text-sm mb-3">{{ errorModal }}</p>
          <div class="flex gap-3">
            <button @click="modalAbrirTurno = false; errorModal = null"
              class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
            <button @click="abrirTurno" :disabled="loadingModal"
              class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ loadingModal ? 'Abriendo...' : 'Abrir' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Cerrar turno -->
    <Teleport to="body">
      <div v-if="modalCerrarTurno" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6 overflow-y-auto max-h-[90vh]">
          <h3 class="font-display text-xl text-ink font-medium mb-4">Cerrar turno</h3>
          <p class="text-ink-mute text-sm mb-4">Ingresa el conteo físico de caja.</p>
          <div class="grid grid-cols-2 gap-3 mb-4">
            <div v-for="campo in camposCorte" :key="campo.key">
              <label class="block text-ink-dim text-xs mb-1">{{ campo.label }}</label>
              <input v-model.number="corte[campo.key]" type="number" min="0"
                class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </div>
          <label class="block text-ink-mute text-sm mb-1.5">Observaciones</label>
          <textarea v-model="corte.observaciones" rows="2" placeholder="Opcional..."
            class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber resize-none mb-4" />
          <p v-if="errorModal" class="text-err text-sm mb-3">{{ errorModal }}</p>
          <div class="flex gap-3">
            <button @click="modalCerrarTurno = false; errorModal = null"
              class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
            <button @click="cerrarTurno" :disabled="loadingModal"
              class="flex-1 bg-err hover:bg-err/80 text-white font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ loadingModal ? 'Cerrando...' : 'Cerrar turno' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Nueva venta -->
    <Teleport to="body">
      <div v-if="modalNuevaVenta" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-lg p-6 overflow-y-auto max-h-[90vh]">
          <h3 class="font-display text-xl text-ink font-medium mb-4">Nueva venta</h3>
          <label class="block text-ink-mute text-sm mb-1.5">Método de pago</label>
          <select v-model="nuevaVenta.metodo_pago"
            class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber mb-4">
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
            <option value="mixto">Mixto</option>
          </select>
          <p class="text-ink-mute text-sm mb-2">Selecciona ítems:</p>
          <div class="grid grid-cols-2 gap-2 mb-4 max-h-48 overflow-y-auto">
            <button v-for="m in menus" :key="m.id" @click="agregarItem(m)"
              class="text-left p-3 bg-elevated hover:bg-amber/10 border border-edge hover:border-amber/30 rounded-lg transition-colors">
              <p class="text-ink text-sm font-medium truncate">{{ m.nombre }}</p>
              <p class="text-amber font-mono text-xs mt-0.5">Bs. {{ Number(m.precio_venta).toFixed(2) }}</p>
            </button>
          </div>
          <div v-if="nuevaVenta.items.length > 0" class="mb-4">
            <p class="text-ink-mute text-sm mb-2">Items seleccionados:</p>
            <div v-for="(item, i) in nuevaVenta.items" :key="i"
              class="flex items-center justify-between py-1.5 border-b border-edge">
              <span class="text-ink text-sm truncate mr-2">{{ item.nombre }}</span>
              <div class="flex items-center gap-1 shrink-0">
                <button @click="item.cantidad = Math.max(1, item.cantidad - 1)" class="text-ink-mute hover:text-ink px-1.5 py-0.5 rounded">−</button>
                <span class="font-mono text-ink text-sm w-6 text-center">{{ item.cantidad }}</span>
                <button @click="item.cantidad++" class="text-ink-mute hover:text-ink px-1.5 py-0.5 rounded">+</button>
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
            <button @click="modalNuevaVenta = false; errorModal = null; nuevaVenta.items = []"
              class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
            <button @click="confirmarVenta" :disabled="loadingModal || nuevaVenta.items.length === 0"
              class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ loadingModal ? 'Registrando...' : 'Confirmar venta' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import client from '@/api/client.js'
import AlertBadge from '@/components/AlertBadge.vue'

const turnoActivo    = ref(null)
const ventasTurno    = ref([])
const menus          = ref([])
const cargandoTurno  = ref(true)
const loadingModal   = ref(false)
const errorModal     = ref(null)
const modalAbrirTurno  = ref(false)
const modalCerrarTurno = ref(false)
const modalNuevaVenta  = ref(false)
const cajaInicial      = ref(0)

const corte = reactive({
  total_efectivo_contado: 0, total_real: 0, total_tarjeta: 0, total_transferencia: 0,
  billetes_200: 0, billetes_100: 0, billetes_50: 0, billetes_20: 0, billetes_10: 0,
  monedas_total: 0, observaciones: '',
})
const camposCorte = [
  { key: 'total_efectivo_contado', label: 'Efectivo contado (Bs.)' },
  { key: 'total_real',             label: 'Total real (Bs.)' },
  { key: 'total_tarjeta',          label: 'Tarjeta (Bs.)' },
  { key: 'total_transferencia',    label: 'Transferencia (Bs.)' },
  { key: 'billetes_200', label: 'Billetes Bs. 200' },
  { key: 'billetes_100', label: 'Billetes Bs. 100' },
  { key: 'billetes_50',  label: 'Billetes Bs. 50' },
  { key: 'billetes_20',  label: 'Billetes Bs. 20' },
  { key: 'billetes_10',  label: 'Billetes Bs. 10' },
  { key: 'monedas_total', label: 'Monedas (Bs.)' },
]

const nuevaVenta = reactive({ metodo_pago: 'efectivo', items: [] })

const totalTurno = computed(() =>
  ventasTurno.value.filter(v => v.estado === 'completada').reduce((a, v) => a + Number(v.total), 0).toFixed(2)
)
const totalNuevaVenta = computed(() =>
  nuevaVenta.items.reduce((a, i) => a + i.precio_unitario * i.cantidad, 0).toFixed(2)
)

function formatHora(iso) {
  return new Date(iso).toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
}

function agregarItem(menu) {
  const ex = nuevaVenta.items.find(i => i.id === menu.id && i.tipo === 'menu')
  if (ex) { ex.cantidad++; return }
  nuevaVenta.items.push({ id: menu.id, tipo: 'menu', nombre: menu.nombre, cantidad: 1, precio_unitario: Number(menu.precio_venta) })
}

onMounted(() => Promise.all([cargarTurnoActivo(), cargarMenus()]))

async function cargarTurnoActivo() {
  cargandoTurno.value = true
  try {
    const { data } = await client.get('/turnos/activo')
    turnoActivo.value = data.data ?? null
    if (turnoActivo.value) await cargarVentasTurno()
  } catch { turnoActivo.value = null }
  finally { cargandoTurno.value = false }
}

async function cargarVentasTurno() {
  if (!turnoActivo.value) return
  try {
    const { data } = await client.get(`/ventas?turno_id=${turnoActivo.value.id}`)
    const lista = data.data?.data ?? data.data ?? []
    ventasTurno.value = Array.isArray(lista) ? lista : []
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
    modalAbrirTurno.value = false; cajaInicial.value = 0
    await cargarTurnoActivo()
  } catch (e) { errorModal.value = e.response?.data?.message ?? 'Error al abrir turno.' }
  finally { loadingModal.value = false }
}

async function cerrarTurno() {
  if (!turnoActivo.value) return
  loadingModal.value = true; errorModal.value = null
  try {
    await client.post(`/turnos/${turnoActivo.value.id}/cerrar`, { ...corte })
    modalCerrarTurno.value = false; turnoActivo.value = null; ventasTurno.value = []
  } catch (e) { errorModal.value = e.response?.data?.message ?? 'Error al cerrar turno.' }
  finally { loadingModal.value = false }
}

async function confirmarVenta() {
  loadingModal.value = true; errorModal.value = null
  try {
    await client.post('/ventas', {
      turno_id:    turnoActivo.value.id,
      metodo_pago: nuevaVenta.metodo_pago,
      items:       nuevaVenta.items.map(i => ({ tipo: i.tipo, id: i.id, cantidad: i.cantidad, precio_unitario: i.precio_unitario })),
    })
    modalNuevaVenta.value = false; nuevaVenta.items = []
    await cargarVentasTurno()
  } catch (e) { errorModal.value = e.response?.data?.message ?? 'Error al registrar la venta.' }
  finally { loadingModal.value = false }
}
</script>
