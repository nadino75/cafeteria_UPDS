<template>
  <div class="space-y-6">

    <div class="flex items-center justify-between">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Panel Contable</h1>
        <p class="text-ink-mute text-sm mt-1">{{ fechaHoy }}</p>
      </div>
      <div class="flex items-center gap-2">
        <select v-model.number="selectedAnio"
          class="bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber">
          <option v-for="a in ANIOS" :key="a" :value="a">{{ a }}</option>
        </select>
        <select v-model.number="selectedMes"
          class="bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber">
          <option v-for="m in MESES" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
        <button @click="recargar" :disabled="cargando"
          class="px-4 py-2 bg-amber hover:bg-amber-bright text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
          {{ cargando ? '...' : 'Consultar' }}
        </button>
        <button @click="imprimir"
          class="px-4 py-2 border border-edge hover:bg-elevated text-sm font-medium rounded-lg transition-colors">
          Imprimir
        </button>
      </div>
    </div>

    <!-- KPIs del mes seleccionado -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <StatCard label="Util. Neta" :value="`Bs. ${kpiUtilidadNeta}`"
        :variante="kpiUtilidadNetaNum >= 0 ? 'ok' : 'err'" />
      <StatCard label="Ingresos" :value="`Bs. ${kpiIngresos}`" variante="ok" />
      <StatCard label="Costos + Gastos" :value="`Bs. ${kpiEgresos}`" variante="warn" />
      <StatCard label="Margen Bruto" :value="`${kpiMargenBruto}%`" variante="neutral" />
    </div>

    <!-- Comparativa vs mes anterior -->
    <div v-if="mesSeleccionado" class="bg-card border border-edge rounded-xl p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-display text-lg text-ink font-medium">Comparativa mensual</h2>
        <span class="text-xs text-ink-mute">
          {{ mesAnteriorLabel }} vs {{ mesSeleccionado.etiqueta }}
        </span>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div>
          <span class="text-ink-mute text-xs">Ingresos</span>
          <p class="font-mono text-lg text-ink">Bs. {{ Number(mesSeleccionado.ingresos).toFixed(0) }}</p>
          <VariacionBadge :valor="variacionIngresos" />
        </div>
        <div>
          <span class="text-ink-mute text-xs">Utilidad Neta</span>
          <p class="font-mono text-lg" :class="mesSeleccionado.utilidad_neta >= 0 ? 'text-ok' : 'text-err'">
            Bs. {{ Number(mesSeleccionado.utilidad_neta).toFixed(0) }}
          </p>
          <VariacionBadge :valor="variacionUtilidad" />
        </div>
        <div>
          <span class="text-ink-mute text-xs">Gastos</span>
          <p class="font-mono text-lg text-warn">Bs. {{ Number(mesSeleccionado.gastos).toFixed(0) }}</p>
        </div>
        <div>
          <span class="text-ink-mute text-xs">Ticket Promedio</span>
          <p class="font-mono text-lg text-ink">Bs. {{ Number(mesSeleccionado.ticket_promedio).toFixed(0) }}</p>
        </div>
      </div>
    </div>

    <!-- Tendencia mensual (gráfico + tabla) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 bg-card border border-edge rounded-xl p-5">
        <h2 class="font-display text-lg text-ink font-medium mb-3">Tendencia últimos 6 meses</h2>
        <div style="height:192px;position:relative;">
          <MiniChart :datos="datosGrafico" tipo="bar" :color="chartColor" />
          <p v-if="!tendencia.length" class="absolute inset-0 flex items-center justify-center text-ink-mute text-sm">
            Sin datos de tendencia
          </p>
        </div>
        <div class="overflow-x-auto mt-4">
          <table class="w-full text-xs">
            <thead>
              <tr class="text-ink-dim uppercase tracking-wider">
                <th class="text-left py-2 pr-3">Mes</th>
                <th class="text-right py-2 px-3">Ingresos</th>
                <th class="text-right py-2 px-3">Costos</th>
                <th class="text-right py-2 px-3">Gastos</th>
                <th class="text-right py-2 px-3">Util. Neta</th>
                <th class="text-right py-2 pl-3">Ventas</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(m, i) in tendencia" :key="m.etiqueta"
                class="border-t border-edge/50 cursor-pointer transition-colors"
                :class="i === selectedIdx ? 'bg-amber/5' : 'hover:bg-elevated/30'"
                @click="seleccionarMesPorIndice(i)">
                <td class="py-2 pr-3 font-medium" :class="i === selectedIdx ? 'text-amber' : 'text-ink'">
                  {{ m.etiqueta }}
                </td>
                <td class="py-2 px-3 text-right font-mono text-ok">Bs. {{ m.ingresos.toFixed(0) }}</td>
                <td class="py-2 px-3 text-right font-mono text-err">Bs. {{ m.costos.toFixed(0) }}</td>
                <td class="py-2 px-3 text-right font-mono text-warn">Bs. {{ m.gastos.toFixed(0) }}</td>
                <td class="py-2 px-3 text-right font-mono" :class="m.utilidad_neta >= 0 ? 'text-ok' : 'text-err'">
                  Bs. {{ m.utilidad_neta.toFixed(0) }}
                </td>
                <td class="py-2 pl-3 text-right text-ink-dim">{{ m.num_ventas }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Balance General -->
      <div class="bg-card border border-edge rounded-xl p-5">
        <h2 class="font-display text-lg text-ink font-medium mb-4">Balance General</h2>
        <div v-if="balanceGeneral.activos" class="space-y-4">
          <div>
            <h3 class="text-xs text-ink-dim uppercase tracking-wider mb-2">Activos</h3>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-ink-mute">Caja</span>
                <span class="font-mono text-ink">Bs. {{ Number(balanceGeneral.activos.caja).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-ink-mute">Inventario</span>
                <span class="font-mono text-ink">Bs. {{ Number(balanceGeneral.activos.inventario).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm font-medium border-t border-edge pt-2">
                <span class="text-ink">Total Activos</span>
                <span class="font-mono text-ok">Bs. {{ Number(balanceGeneral.activos.total).toFixed(2) }}</span>
              </div>
            </div>
          </div>
          <div>
            <h3 class="text-xs text-ink-dim uppercase tracking-wider mb-2">Pasivos</h3>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-ink-mute">Ctas. por Pagar</span>
                <span class="font-mono text-warn">Bs. {{ Number(balanceGeneral.pasivos.cuentas_pagar).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm font-medium border-t border-edge pt-2">
                <span class="text-ink">Total Pasivos</span>
                <span class="font-mono text-warn">Bs. {{ Number(balanceGeneral.pasivos.total).toFixed(2) }}</span>
              </div>
            </div>
          </div>
          <div class="border-t border-edge pt-3">
            <div class="flex justify-between text-sm font-semibold">
              <span class="text-ink">Patrimonio Neto</span>
              <span class="font-mono text-ok">Bs. {{ Number(balanceGeneral.patrimonio.total).toFixed(2) }}</span>
            </div>
          </div>
        </div>
        <p v-else class="text-ink-mute text-sm text-center py-8">Sin datos contables</p>
      </div>
    </div>

    <!-- Cierres diarios del mes seleccionado -->
    <div class="bg-card border border-edge rounded-xl">
      <div class="p-5 border-b border-edge flex items-center justify-between">
        <h2 class="font-display text-lg text-ink font-medium">Cierres diarios</h2>
        <span class="text-xs text-ink-mute">{{ cierres.length }} registros — {{ mesSeleccionado?.etiqueta ?? '' }}</span>
      </div>
      <div class="overflow-x-auto max-h-72 overflow-y-auto">
        <table class="w-full text-sm">
          <thead class="sticky top-0 bg-card">
            <tr class="text-ink-dim text-xs uppercase tracking-wider">
              <th class="text-left px-5 py-3">Fecha</th>
              <th class="text-right px-5 py-3">Ventas</th>
              <th class="text-right px-5 py-3">Egresos</th>
              <th class="text-right px-5 py-3">Utilidad</th>
              <th class="text-right px-5 py-3">#</th>
              <th class="text-center px-5 py-3">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cierres.length === 0">
              <td colspan="6" class="px-5 py-6 text-center text-ink-mute">Sin cierres este mes</td>
            </tr>
            <tr v-for="c in cierres" :key="c.id" class="border-t border-edge hover:bg-elevated transition-colors">
              <td class="px-5 py-3 font-mono text-ink-mute text-xs">{{ formatFecha(c.fecha) }}</td>
              <td class="px-5 py-3 text-right font-mono text-ok text-xs">Bs. {{ Number(c.total_ventas ?? 0).toFixed(2) }}</td>
              <td class="px-5 py-3 text-right font-mono text-warn text-xs">Bs. {{ Number(c.total_egresos ?? 0).toFixed(2) }}</td>
              <td class="px-5 py-3 text-right font-mono text-xs"
                :class="(c.total_ventas - c.total_egresos) >= 0 ? 'text-ok' : 'text-err'">
                Bs. {{ Number(c.total_ventas - c.total_egresos).toFixed(2) }}
              </td>
              <td class="px-5 py-3 text-right text-ink-dim text-xs">{{ c.num_ventas }}</td>
              <td class="px-5 py-3 text-center">
                <AlertBadge :texto="c.estado" :severidad="c.estado === 'cerrado' ? 'ok' : 'warn'" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import client from '@/api/client.js'
import StatCard from '@/components/StatCard.vue'
import AlertBadge from '@/components/AlertBadge.vue'
import MiniChart from '@/components/MiniChart.vue'
import VariacionBadge from '@/components/VariacionBadge.vue'

const MESES = [
  { value: 1, label: 'Enero' }, { value: 2, label: 'Febrero' },
  { value: 3, label: 'Marzo' }, { value: 4, label: 'Abril' },
  { value: 5, label: 'Mayo' }, { value: 6, label: 'Junio' },
  { value: 7, label: 'Julio' }, { value: 8, label: 'Agosto' },
  { value: 9, label: 'Septiembre' }, { value: 10, label: 'Octubre' },
  { value: 11, label: 'Noviembre' }, { value: 12, label: 'Diciembre' },
]
const anioActual = new Date().getFullYear()
const ANIOS = Array.from({ length: 5 }, (_, i) => anioActual - i)

const cargando = ref(false)
const tendencia = ref([])
const balanceGeneral = ref({})
const cierres = ref([])
const selectedIdx = ref(-1)
const selectedMes = ref(new Date().getMonth() + 1)
const selectedAnio = ref(anioActual)

const fechaHoy = computed(() =>
  new Date().toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

const mesSeleccionado = computed(() => {
  const t = tendencia.value
  if (!t.length || selectedIdx.value < 0) return null
  return t[selectedIdx.value] ?? null
})

const mesAnterior = computed(() => {
  const t = tendencia.value
  if (!t.length || selectedIdx.value < 1) return null
  return t[selectedIdx.value - 1] ?? null
})

const mesAnteriorLabel = computed(() => mesAnterior.value?.etiqueta ?? '—')

const variacionIngresos = computed(() => {
  const act = mesSeleccionado.value
  const ant = mesAnterior.value
  if (!act || !ant || !ant.ingresos) return 0
  return Math.round((act.ingresos - ant.ingresos) / ant.ingresos * 1000) / 10
})

const variacionUtilidad = computed(() => {
  const act = mesSeleccionado.value
  const ant = mesAnterior.value
  if (!act || !ant || !ant.utilidad_neta) return 0
  return Math.round((act.utilidad_neta - ant.utilidad_neta) / Math.abs(ant.utilidad_neta) * 1000) / 10
})

const kpiUtilidadNeta = computed(() => mesSeleccionado.value ? Number(mesSeleccionado.value.utilidad_neta).toFixed(2) : '0.00')
const kpiUtilidadNetaNum = computed(() => mesSeleccionado.value?.utilidad_neta ?? 0)
const kpiIngresos = computed(() => mesSeleccionado.value ? Number(mesSeleccionado.value.ingresos).toFixed(2) : '0.00')
const kpiEgresos = computed(() => {
  const m = mesSeleccionado.value
  return m ? Number(m.costos + m.gastos).toFixed(2) : '0.00'
})
const kpiMargenBruto = computed(() => {
  const m = mesSeleccionado.value
  if (!m || !m.ingresos) return '0.0'
  return ((m.ingresos - m.costos) / m.ingresos * 100).toFixed(1)
})

const datosGrafico = computed(() =>
  tendencia.value.length ? tendencia.value.map(m => m.utilidad_neta) : [0]
)

const chartColor = computed(() => {
  if (!mesSeleccionado.value) return '#D4821E'
  return mesSeleccionado.value.utilidad_neta >= 0 ? '#22A67E' : '#E05252'
})

function seleccionarMesPorIndice(i) {
  selectedIdx.value = i
}

function sincronizarSelectores() {
  const i = tendencia.value.findIndex(m => m.anio === selectedAnio.value && m.mes === selectedMes.value)
  if (i >= 0) {
    selectedIdx.value = i
  } else if (tendencia.value.length) {
    selectedIdx.value = tendencia.value.length - 1
    const ultimo = tendencia.value[selectedIdx.value]
    selectedMes.value = ultimo.mes
    selectedAnio.value = ultimo.anio
  }
}

function formatFecha(iso) { return iso ? new Date(iso).toLocaleDateString('es-BO') : '—' }
function imprimir() { window.print() }

async function recargar() {
  cargando.value = true
  await Promise.all([cargarTendencia(), cargarBalanceGeneral(), cargarCierres()])
  sincronizarSelectores()
  cargando.value = false
}

async function cargarTendencia() {
  try {
    const { data } = await client.get('/contabilidad/tendencia', {
      params: { meses: 6, mes: selectedMes.value, anio: selectedAnio.value }
    })
    tendencia.value = data.data ?? []
  } catch { tendencia.value = [] }
}

async function cargarBalanceGeneral() {
  try {
    const { data } = await client.get('/contabilidad/balance-general')
    balanceGeneral.value = data.data ?? {}
  } catch { balanceGeneral.value = {} }
}

async function cargarCierres() {
  try {
    const { data } = await client.get(`/reportes/cierres-diarios?mes=${selectedMes.value}&anio=${selectedAnio.value}`)
    cierres.value = Array.isArray(data.data) ? data.data : []
  } catch { cierres.value = [] }
}

watch([selectedMes, selectedAnio], () => {
  recargar()
})

onMounted(recargar)
</script>
