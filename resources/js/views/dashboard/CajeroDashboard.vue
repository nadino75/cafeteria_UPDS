<template>
  <div class="space-y-6">

    <h1 class="font-display text-3xl text-ink font-semibold">Mi turno</h1>

    <!-- Estado del turno -->
    <div class="bg-card border rounded-xl p-6" :class="turnoActivo ? 'border-ok/30' : 'border-edge'">
      <div v-if="cargandoTurno" class="text-ink-mute text-sm">Verificando turno...</div>

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

      <div v-else>
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
          <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-ok animate-pulse" />
            <span class="text-ok font-medium text-sm uppercase tracking-wider">Turno activo</span>
            <span class="text-ink-dim text-xs font-mono ml-2">{{ turnoActivo.codigo }}</span>
          </div>
          <div class="flex gap-2">
            <button @click="abrirPOS"
              class="px-6 py-3 bg-amber hover:bg-amber-bright text-base font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
              + Nueva venta
            </button>
            <button @click="modalCerrarTurno = true"
              class="px-5 py-3 border border-err/40 text-err hover:bg-err/10 rounded-lg transition-colors text-sm">
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

    <!-- Ventas del turno (colapsable) -->
    <div v-if="turnoActivo">
      <button @click="verVentas = !verVentas"
        class="flex items-center gap-2 text-ink-mute hover:text-ink text-sm transition-colors">
        <span class="inline-block transition-transform" :class="verVentas ? 'rotate-90' : ''">▶</span>
        Ventas del turno ({{ ventasTurno.length }})
      </button>
      <div v-if="verVentas" class="mt-3 bg-card border border-edge rounded-xl overflow-hidden">
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

    <!-- Pantalla completa de venta (POS) -->
    <Teleport to="body">
      <div v-if="modoVenta" class="fixed inset-0 z-50 bg-base flex flex-col">

        <!-- Barra superior -->
        <div class="flex items-center justify-between px-6 py-3 border-b border-edge bg-card shrink-0">
          <button @click="cerrarPOS" class="flex items-center gap-2 text-ink-mute hover:text-ink transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Volver
          </button>
          <div class="flex items-center gap-4">
            <span class="text-ink-dim text-xs font-mono">{{ turnoActivo?.codigo }}</span>
            <select v-model="nuevaVenta.metodo_pago"
              class="bg-elevated border border-edge rounded-lg px-3 py-1.5 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="efectivo">Efectivo</option>
              <option value="tarjeta">Tarjeta</option>
              <option value="transferencia">Transferencia</option>
              <option value="mixto">Mixto</option>
            </select>
            <AlertBadge texto="Abierto" severidad="ok" />
          </div>
        </div>

        <!-- Cuerpo: split menús + carrito -->
        <div class="flex flex-1 overflow-hidden">
          <!-- Menús -->
          <div class="flex-1 flex flex-col p-4 overflow-hidden">
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 flex-1 overflow-y-auto pr-2 content-start">
              <MenuCard v-for="m in menusFiltrados" :key="m.id" :menu="m" @click="agregarItem(m)" />
              <div v-if="menusFiltrados.length === 0"
                class="col-span-full py-16 text-center text-ink-dim text-sm">Sin resultados</div>
            </div>
            <!-- Buscador -->
            <div class="relative mt-4 shrink-0">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-dim" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input v-model="busquedaMenu" type="text" placeholder="Buscar menú..."
                class="w-full bg-elevated border border-edge rounded-lg pl-10 pr-4 py-3 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </div>

          <!-- Carrito -->
          <div class="w-80 border-l border-edge bg-card flex flex-col shrink-0">
            <div class="p-4 border-b border-edge">
              <h3 class="text-ink font-medium text-sm">Items seleccionados</h3>
            </div>

            <div v-if="nuevaVenta.items.length === 0" class="flex-1 flex items-center justify-center p-4">
              <p class="text-ink-dim text-sm text-center">Selecciona un menú de la lista</p>
            </div>

            <div v-else class="flex-1 overflow-y-auto p-4 space-y-3">
              <div v-for="(item, i) in nuevaVenta.items" :key="i"
                class="flex items-start justify-between gap-2 pb-3 border-b border-edge last:border-0">
                <div class="flex-1 min-w-0">
                  <p class="text-ink text-sm font-medium truncate">{{ item.nombre }}</p>
                  <div class="flex items-center gap-1 mt-1.5">
                    <button @click="item.cantidad = Math.max(1, item.cantidad - 1)"
                      class="w-7 h-7 flex items-center justify-center rounded bg-elevated border border-edge text-ink-mute hover:text-ink text-sm transition-colors">−</button>
                    <span class="font-mono text-ink text-sm w-6 text-center tabular-nums">{{ item.cantidad }}</span>
                    <button @click="item.cantidad++"
                      class="w-7 h-7 flex items-center justify-center rounded bg-elevated border border-edge text-ink-mute hover:text-ink text-sm transition-colors">+</button>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <p class="font-mono text-amber text-sm">Bs. {{ (item.precio_unitario * item.cantidad).toFixed(2) }}</p>
                  <button @click="nuevaVenta.items.splice(i, 1)"
                    class="text-ink-dim hover:text-err text-xs mt-1 transition-colors">Eliminar</button>
                </div>
              </div>
            </div>

            <div v-if="nuevaVenta.items.length > 0" class="p-4 border-t border-edge space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-ink font-medium">Total</span>
                <span class="font-mono text-amber text-xl font-bold">Bs. {{ totalNuevaVenta }}</span>
              </div>
              <p v-if="errorModal" class="text-err text-xs">{{ errorModal }}</p>
              <button @click="confirmarVenta" :disabled="loadingModal || nuevaVenta.items.length === 0"
                class="w-full py-3 bg-amber hover:bg-amber-bright text-base font-medium rounded-lg text-sm disabled:opacity-50 transition-colors">
                {{ loadingModal ? 'Registrando...' : 'Confirmar venta' }}
              </button>
            </div>
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
import MenuCard from '@/components/MenuCard.vue'

const turnoActivo    = ref(null)
const ventasTurno    = ref([])
const menus          = ref([])
const cargandoTurno  = ref(true)
const loadingModal   = ref(false)
const errorModal     = ref(null)
const modalAbrirTurno  = ref(false)
const modalCerrarTurno = ref(false)
const modoVenta      = ref(false)
const verVentas      = ref(false)
const busquedaMenu   = ref('')
const cajaInicial    = ref(0)

const menusFiltrados = computed(() => {
  const q = busquedaMenu.value.toLowerCase().trim()
  if (!q) return menus.value
  return menus.value.filter(m => m.nombre.toLowerCase().includes(q))
})

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

function abrirPOS() {
  busquedaMenu.value = ''
  nuevaVenta.metodo_pago = 'efectivo'
  nuevaVenta.items = []
  errorModal.value = null
  modoVenta.value = true
}

function cerrarPOS() {
  modoVenta.value = false
  errorModal.value = null
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
    nuevaVenta.items = []
    await cargarVentasTurno()
  } catch (e) { errorModal.value = e.response?.data?.message ?? 'Error al registrar la venta.' }
  finally { loadingModal.value = false }
}
</script>
