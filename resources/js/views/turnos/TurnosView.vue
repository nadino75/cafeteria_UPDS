<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Turnos</h1>
        <p class="text-ink-mute text-sm mt-1">Gestión de turnos de caja</p>
      </div>
      <button v-if="!turnoActivo" @click="abrirModalAbrir()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Abrir turno
      </button>
      <div v-else class="px-4 py-2 bg-ok/10 border border-ok/30 rounded-lg text-ok text-sm font-medium">
        Turno activo: <span class="font-mono">{{ turnoActivo.codigo }}</span>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <select v-model="filtroEstado"
        class="w-full sm:w-44 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
        <option value="">Todos los estados</option>
        <option value="abierto">Abierto</option>
        <option value="cerrado">Cerrado</option>
      </select>
      <input v-model="filtroFecha" type="date"
        class="w-full sm:w-44 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
    </div>

    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Código</th>
              <th class="text-left px-5 py-3">Usuario</th>
              <th class="text-left px-5 py-3">Apertura</th>
              <th class="text-center px-5 py-3">Estado</th>
              <th class="text-right px-5 py-3">Caja Inicial</th>
              <th class="text-right px-5 py-3">Caja Final</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="turnosFiltrados.length === 0">
              <td colspan="7" class="px-5 py-8 text-center text-ink-mute">Sin turnos registrados</td>
            </tr>
            <tr v-for="t in turnosFiltrados" :key="t.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 font-mono text-ink text-xs">{{ t.codigo }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ t.usuarioApertura?.nombre_completo || '—' }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ t.fecha_apertura ? new Date(t.fecha_apertura).toLocaleString('es-BO') : '—' }}</td>
              <td class="px-5 py-3 text-center">
                <span v-if="t.estado === 'abierto'" class="inline-flex items-center gap-1 text-ok text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-ok" /> Abierto
                </span>
                <span v-else class="inline-flex items-center gap-1 text-ink-dim text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-ink-dim" /> Cerrado
                </span>
              </td>
              <td class="px-5 py-3 text-right font-mono text-ink-dim text-xs">Bs. {{ Number(t.caja_inicial).toFixed(2) }}</td>
              <td class="px-5 py-3 text-right font-mono text-ink-dim text-xs">{{ t.caja_final_real ? 'Bs. ' + Number(t.caja_final_real).toFixed(2) : '—' }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button v-if="t.estado === 'abierto'" @click="abrirModalCerrar(t)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">Cerrar</button>
                  <span v-if="t.estado === 'cerrado'" class="text-ink-dim text-xs">—</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  <!-- Modal Abrir Turno -->
  <Teleport to="body">
    <div v-if="modalAbrir" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-sm p-6">
        <h3 class="font-display text-xl text-ink font-medium mb-4">Abrir turno</h3>
        <div>
          <label class="block text-ink-mute text-sm mb-1">Caja inicial *</label>
          <input v-model.number="cajaInicial" type="number" min="0" step="0.01"
            class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
        </div>
        <p v-if="errorAbrir" class="text-err text-sm mt-3">{{ errorAbrir }}</p>
        <div class="flex gap-3 mt-5">
          <button @click="cerrarModalAbrir"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
          <button @click="abrirTurno" :disabled="abriendo"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ abriendo ? 'Abriendo...' : 'Abrir turno' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Modal Cerrar Turno -->
  <Teleport to="body">
    <div v-if="modalCerrar" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-lg p-6 my-4">
        <h3 class="font-display text-xl text-ink font-medium mb-1">Cerrar turno</h3>
        <p class="text-ink-dim text-xs mb-4">{{ turnoCerrar?.codigo }}</p>

        <div class="space-y-3">
          <h4 class="text-ink-mute text-sm font-medium border-b border-edge pb-1">Resumen de ventas</h4>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1">Total efectivo contado *</label>
              <input v-model.number="corte.total_efectivo_contado" type="number" min="0" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Total real *</label>
              <input v-model.number="corte.total_real" type="number" min="0" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Total tarjeta</label>
              <input v-model.number="corte.total_tarjeta" type="number" min="0" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Total transferencia</label>
              <input v-model.number="corte.total_transferencia" type="number" min="0" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </div>

          <h4 class="text-ink-mute text-sm font-medium border-b border-edge pb-1 pt-2">Conteo de billetes</h4>
          <div class="grid grid-cols-5 gap-2">
            <div>
              <label class="block text-ink-mute text-xs mb-1">Bs. 200</label>
              <input v-model.number="corte.billetes_200" type="number" min="0"
                class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-xs mb-1">Bs. 100</label>
              <input v-model.number="corte.billetes_100" type="number" min="0"
                class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-xs mb-1">Bs. 50</label>
              <input v-model.number="corte.billetes_50" type="number" min="0"
                class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-xs mb-1">Bs. 20</label>
              <input v-model.number="corte.billetes_20" type="number" min="0"
                class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-xs mb-1">Bs. 10</label>
              <input v-model.number="corte.billetes_10" type="number" min="0"
                class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
            </div>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Total monedas</label>
            <input v-model.number="corte.monedas_total" type="number" min="0" step="0.01"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Observaciones</label>
            <textarea v-model="corte.observaciones" rows="2"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber"></textarea>
          </div>
        </div>

        <p v-if="errorCerrar" class="text-err text-sm mt-4">{{ errorCerrar }}</p>

        <div class="flex gap-3 mt-5">
          <button @click="cerrarModalCerrar"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
          <button @click="cerrarTurno" :disabled="cerrando"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ cerrando ? 'Cerrando...' : 'Cerrar turno' }}
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

const turnos       = ref([])
const turnoActivo  = ref(null)
const filtroEstado = ref('')
const filtroFecha  = ref('')

const turnosFiltrados = computed(() => {
  return turnos.value.filter(t => {
    if (filtroEstado.value && t.estado !== filtroEstado.value) return false
    if (filtroFecha.value) {
      const dia = t.fecha_apertura ? t.fecha_apertura.slice(0, 10) : ''
      if (dia !== filtroFecha.value) return false
    }
    return true
  })
})

onMounted(() => Promise.all([cargarTurnos(), cargarTurnoActivo()]))

async function cargarTurnos() {
  try {
    const { data } = await client.get('/turnos')
    turnos.value = data.data ?? []
  } catch { turnos.value = [] }
}

async function cargarTurnoActivo() {
  try {
    const { data } = await client.get('/turnos/activo')
    turnoActivo.value = data.data ?? null
  } catch { turnoActivo.value = null }
}

// Abrir turno
const modalAbrir  = ref(false)
const abriendo    = ref(false)
const errorAbrir  = ref(null)
const cajaInicial = ref(null)

function abrirModalAbrir() {
  errorAbrir.value = null
  cajaInicial.value = null
  modalAbrir.value = true
}

function cerrarModalAbrir() {
  modalAbrir.value = false
  errorAbrir.value = null
}

async function abrirTurno() {
  abriendo.value = true; errorAbrir.value = null
  try {
    await client.post('/turnos/abrir', { caja_inicial: cajaInicial.value })
    cerrarModalAbrir()
    await Promise.all([cargarTurnos(), cargarTurnoActivo()])
  } catch (e) {
    errorAbrir.value = e.response?.data?.message || 'Error al abrir turno.'
  } finally { abriendo.value = false }
}

// Cerrar turno
const modalCerrar = ref(false)
const cerrando    = ref(false)
const errorCerrar = ref(null)
const turnoCerrar = ref(null)
const corte       = ref({
  total_efectivo_contado: null,
  total_real: null,
  total_tarjeta: 0,
  total_transferencia: 0,
  billetes_200: 0,
  billetes_100: 0,
  billetes_50: 0,
  billetes_20: 0,
  billetes_10: 0,
  monedas_total: 0,
  observaciones: '',
})

function abrirModalCerrar(turno) {
  turnoCerrar.value = turno
  errorCerrar.value = null
  corte.value = {
    total_efectivo_contado: null,
    total_real: null,
    total_tarjeta: 0,
    total_transferencia: 0,
    billetes_200: 0,
    billetes_100: 0,
    billetes_50: 0,
    billetes_20: 0,
    billetes_10: 0,
    monedas_total: 0,
    observaciones: '',
  }
  modalCerrar.value = true
}

function cerrarModalCerrar() {
  modalCerrar.value = false
  errorCerrar.value = null
}

async function cerrarTurno() {
  cerrando.value = true; errorCerrar.value = null
  try {
    await client.post(`/turnos/${turnoCerrar.value.id}/cerrar`, corte.value)
    cerrarModalCerrar()
    await Promise.all([cargarTurnos(), cargarTurnoActivo()])
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorCerrar.value = Object.values(errs).flat().join('. ')
    } else {
      errorCerrar.value = e.response?.data?.message || 'Error al cerrar turno.'
    }
  } finally { cerrando.value = false }
}
</script>
