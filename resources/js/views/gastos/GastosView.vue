<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Gastos Operativos</h1>
        <p class="text-ink-mute text-sm mt-1">Registro de gastos operativos del negocio</p>
      </div>
      <button @click="abrirModal()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nuevo gasto
      </button>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <input v-model="filtroFecha" type="date"
        class="w-full sm:w-48 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
      <select v-model="filtroCategoria"
        class="w-full sm:w-48 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
        <option value="">Todas las categorías</option>
        <option v-for="c in CATEGORIAS" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Fecha</th>
              <th class="text-left px-5 py-3">Categoría</th>
              <th class="text-left px-5 py-3">Descripción</th>
              <th class="text-right px-5 py-3">Monto</th>
              <th class="text-left px-5 py-3">Registró</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="gastos.length === 0">
              <td colspan="6" class="px-5 py-8 text-center text-ink-mute">Sin gastos registrados</td>
            </tr>
            <tr v-for="g in gastos" :key="g.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink-dim text-xs">{{ g.fecha ? g.fecha.slice(0, 10) : '—' }}</td>
              <td class="px-5 py-3">
                <span class="inline-flex text-xs px-2 py-0.5 rounded border border-edge text-ink-mute">
                  {{ g.categoria }}
                </span>
              </td>
              <td class="px-5 py-3 text-ink max-w-xs truncate">{{ g.descripcion }}</td>
              <td class="px-5 py-3 text-right font-mono text-err">Bs. {{ Number(g.monto).toFixed(2) }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ g.usuario?.nombre || '—' }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button @click="verDetalle(g)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">Ver</button>
                  <span class="text-edge-lit">|</span>
                  <button @click="eliminar(g)"
                    class="text-ink-mute hover:text-err text-xs font-medium transition-colors">Eliminar</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Nuevo / Editar gasto -->
    <Teleport to="body">
      <div v-if="modalAbierto" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6 my-4">
          <h3 class="font-display text-xl text-ink font-medium mb-4">
            {{ modoEdicion ? 'Editar gasto' : 'Nuevo gasto' }}
          </h3>
          <div class="space-y-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1">Categoría *</label>
              <select v-model="form.categoria"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
                <option value="">Seleccionar...</option>
                <option v-for="c in CATEGORIAS" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Descripción *</label>
              <input v-model="form.descripcion" type="text"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Monto (Bs.) *</label>
              <input v-model.number="form.monto" type="number" min="0.01" step="0.01"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Turno</label>
              <select v-model="form.turno_id"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
                <option value="">Sin turno</option>
                <option v-for="t in turnos" :key="t.id" :value="t.id">
                  #{{ t.id }} — {{ t.fecha_apertura?.slice(0, 10) }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Comprobante URL</label>
              <input v-model="form.comprobante_url" type="text" placeholder="https://..."
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </div>

          <p v-if="errorForm" class="text-err text-sm mt-4">{{ errorForm }}</p>

          <div class="flex gap-3 mt-5">
            <button @click="cerrarModal"
              class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
            <button @click="guardar" :disabled="guardando"
              class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ guardando ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Ver detalle -->
    <Teleport to="body">
      <div v-if="modalDetalle" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6 my-4">
          <h3 class="font-display text-xl text-ink font-medium mb-4">Detalle del gasto</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b border-edge pb-2">
              <span class="text-ink-mute">Categoría</span>
              <span class="text-ink font-medium">{{ detalle?.categoria }}</span>
            </div>
            <div class="flex justify-between border-b border-edge pb-2">
              <span class="text-ink-mute">Descripción</span>
              <span class="text-ink text-right max-w-[200px]">{{ detalle?.descripcion }}</span>
            </div>
            <div class="flex justify-between border-b border-edge pb-2">
              <span class="text-ink-mute">Monto</span>
              <span class="font-mono text-err font-medium">Bs. {{ Number(detalle?.monto).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between border-b border-edge pb-2">
              <span class="text-ink-mute">Fecha</span>
              <span class="text-ink">{{ detalle?.fecha?.slice(0, 10) || '—' }}</span>
            </div>
            <div class="flex justify-between border-b border-edge pb-2">
              <span class="text-ink-mute">Registró</span>
              <span class="text-ink">{{ detalle?.usuario?.nombre || '—' }}</span>
            </div>
            <div class="flex justify-between border-b border-edge pb-2">
              <span class="text-ink-mute">Turno</span>
              <span class="text-ink">{{ detalle?.turno ? `#${detalle.turno.id}` : '—' }}</span>
            </div>
            <div v-if="detalle?.comprobante_url" class="pt-1">
              <a :href="detalle.comprobante_url" target="_blank"
                class="text-amber hover:text-amber-bright text-xs underline">Ver comprobante</a>
            </div>
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

const CATEGORIAS = ['servicios', 'mantenimiento', 'insumos', 'nomina', 'impuestos', 'otros']

const gastos          = ref([])
const turnos          = ref([])
const filtroFecha     = ref('')
const filtroCategoria  = ref('')

const gastosFiltrados = computed(() => {
  let items = gastos.value
  if (filtroFecha.value) items = items.filter(g => g.fecha?.startsWith(filtroFecha.value))
  if (filtroCategoria.value) items = items.filter(g => g.categoria === filtroCategoria.value)
  return items
})

async function cargarGastos() {
  try {
    const { data } = await client.get('/gastos')
    gastos.value = data.data ?? []
  } catch { gastos.value = [] }
}

async function cargarTurnos() {
  try {
    const { data } = await client.get('/turnos')
    turnos.value = data.data ?? []
  } catch { turnos.value = [] }
}

onMounted(() => Promise.all([cargarGastos(), cargarTurnos()]))

// ── Modal: Crear / Editar ────────────────────────────────────────────────────
const modalAbierto = ref(false)
const modoEdicion  = ref(false)
const guardando    = ref(false)
const errorForm    = ref(null)
const form         = ref({ categoria: '', descripcion: '', monto: null, turno_id: '', comprobante_url: '' })

function abrirModal(gasto = null) {
  errorForm.value = null
  if (gasto) {
    modoEdicion.value = true
    form.value = { ...gasto }
  } else {
    modoEdicion.value = false
    form.value = { categoria: '', descripcion: '', monto: null, turno_id: '', comprobante_url: '' }
  }
  modalAbierto.value = true
}

function cerrarModal() {
  modalAbierto.value = false
  errorForm.value = null
}

async function guardar() {
  guardando.value = true; errorForm.value = null
  try {
    if (modoEdicion.value) {
      await client.put(`/gastos/${form.value.id}`, form.value)
    } else {
      await client.post('/gastos', form.value)
    }
    cerrarModal()
    await cargarGastos()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) errorForm.value = Object.values(errs).flat().join('. ')
    else errorForm.value = e.response?.data?.message || 'Error al guardar el gasto.'
  } finally { guardando.value = false }
}

// ── Modal: Ver detalle ──────────────────────────────────────────────────────
const modalDetalle = ref(false)
const detalle      = ref(null)

function verDetalle(gasto) {
  detalle.value = gasto
  modalDetalle.value = true
}

async function eliminar(gasto) {
  if (!confirm(`¿Estás seguro de eliminar este gasto?`)) return
  try {
    await client.delete(`/gastos/${gasto.id}`)
    await cargarGastos()
  } catch {
    alert('Error al eliminar el gasto.')
  }
}
</script>
