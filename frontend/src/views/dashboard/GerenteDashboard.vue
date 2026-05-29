<template>
  <div class="space-y-6">

    <div>
      <h1 class="font-display text-3xl text-ink font-semibold">Panel de Gerencia</h1>
      <p class="text-ink-mute text-sm mt-1">{{ fechaHoy }}</p>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard label="Ventas hoy"     :value="kpis.ventasHoy"   :delta="kpis.deltaVentas" variante="ok" />
      <StatCard label="Gastos del día" :value="kpis.gastosHoy"   variante="warn" />
      <StatCard label="Margen bruto"   :value="kpis.margen"      :variante="kpis.margenNum >= 0 ? 'ok' : 'err'" />
      <StatCard label="Turnos hoy"     :value="kpis.turnosTexto" variante="neutral" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Top productos -->
      <div class="bg-card border border-edge rounded-xl">
        <div class="p-5 border-b border-edge">
          <h2 class="font-display text-lg text-ink font-medium">Top productos del mes</h2>
        </div>
        <div class="p-5 space-y-3">
          <div v-if="topProductos.length === 0" class="text-ink-mute text-sm text-center py-4">Sin datos</div>
          <div v-for="(p, i) in topProductos" :key="p.id" class="flex items-center gap-3">
            <span class="font-mono text-ink-dim text-xs w-4 shrink-0">{{ i + 1 }}</span>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-1">
                <span class="text-ink text-sm truncate">{{ p.nombre }}</span>
                <span class="font-mono text-amber text-xs ml-2 shrink-0">{{ p.total_vendido }} uds</span>
              </div>
              <div class="h-1.5 bg-elevated rounded-full overflow-hidden">
                <div class="h-full bg-amber rounded-full"
                  :style="{ width: `${Math.round((p.total_vendido / topProductos[0].total_vendido) * 100)}%` }" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stock bajo + Turnos del día -->
      <div class="space-y-4">

        <div class="bg-card border border-edge rounded-xl p-5">
          <h2 class="font-display text-lg text-ink font-medium mb-3">Stock bajo mínimo</h2>
          <div v-if="stockBajo.length === 0" class="text-ok text-sm">✓ Todos los productos con stock suficiente</div>
          <div v-for="p in stockBajo.slice(0, 5)" :key="p.id" class="flex items-center justify-between py-2 border-b border-edge last:border-0">
            <span class="text-ink text-sm truncate mr-2">{{ p.nombre }}</span>
            <div class="flex items-center gap-2 shrink-0">
              <span class="font-mono text-err text-xs">{{ p.stock_actual }}/{{ p.stock_minimo }}</span>
              <AlertBadge texto="Bajo" severidad="err" />
            </div>
          </div>
        </div>

        <div class="bg-card border border-edge rounded-xl p-5">
          <h2 class="font-display text-lg text-ink font-medium mb-3">Turnos de hoy</h2>
          <div v-if="turnos.length === 0" class="text-ink-mute text-sm">Sin turnos registrados hoy</div>
          <div v-for="t in turnos" :key="t.id" class="flex items-center justify-between py-2 border-b border-edge last:border-0">
            <div class="min-w-0 mr-2">
              <p class="text-ink text-sm truncate">{{ t.usuario_apertura?.nombre_completo ?? '—' }}</p>
              <p class="text-ink-dim text-xs font-mono">{{ formatHora(t.fecha_apertura) }}</p>
            </div>
            <AlertBadge
              :texto="t.estado"
              :severidad="t.estado === 'abierto' ? 'ok' : t.estado === 'en_corte' ? 'warn' : 'info'"
            />
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client.js'
import StatCard   from '@/components/StatCard.vue'
import AlertBadge from '@/components/AlertBadge.vue'

const kpis         = ref({ ventasHoy: 'Bs. 0', deltaVentas: null, gastosHoy: 'Bs. 0', margen: 'Bs. 0', margenNum: 0, turnosTexto: '0 abiertos' })
const topProductos = ref([])
const stockBajo    = ref([])
const turnos       = ref([])

const fechaHoy = computed(() => new Date().toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

function isoDate(offset = 0) { const d = new Date(); d.setDate(d.getDate() - offset); return d.toISOString().split('T')[0] }
function inicioMes()          { const d = new Date(); d.setDate(1); return d.toISOString().split('T')[0] }
function formatHora(iso)      { return new Date(iso).toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' }) }

onMounted(() => Promise.all([cargarKpis(), cargarTopProductos(), cargarStockBajo(), cargarTurnos()]))

async function cargarKpis() {
  const [rHoy, rAyer, rGastos, rTurnos] = await Promise.allSettled([
    client.get(`/reportes/ventas-diarias?fecha=${isoDate(0)}`),
    client.get(`/reportes/ventas-diarias?fecha=${isoDate(1)}`),
    client.get(`/gastos?fecha=${isoDate(0)}`),
    client.get(`/turnos?fecha=${isoDate(0)}`),
  ])

  const ventasHoy  = rHoy.status  === 'fulfilled' ? Number(rHoy.value.data.data?.total_ventas  ?? 0) : 0
  const ventasAyer = rAyer.status === 'fulfilled' ? Number(rAyer.value.data.data?.total_ventas ?? 0) : 0
  const costoHoy   = rHoy.status  === 'fulfilled' ? Number(rHoy.value.data.data?.total_costo   ?? 0) : 0
  const delta      = ventasAyer > 0 ? Math.round(((ventasHoy - ventasAyer) / ventasAyer) * 1000) / 10 : null

  const gastosList  = rGastos.status  === 'fulfilled' ? (rGastos.value.data.data  ?? []) : []
  const turnosList  = rTurnos.status  === 'fulfilled' ? (rTurnos.value.data.data?.data ?? rTurnos.value.data.data ?? []) : []
  const gastos      = Array.isArray(gastosList) ? gastosList.reduce((a, g) => a + Number(g.monto ?? 0), 0) : 0
  const margen      = ventasHoy - costoHoy - gastos
  const abiertos    = Array.isArray(turnosList) ? turnosList.filter(t => t.estado === 'abierto').length : 0

  kpis.value = {
    ventasHoy:   `Bs. ${ventasHoy.toFixed(2)}`,
    deltaVentas: delta,
    gastosHoy:   `Bs. ${gastos.toFixed(2)}`,
    margen:      `Bs. ${margen.toFixed(2)}`,
    margenNum:   margen,
    turnosTexto: `${abiertos} abierto${abiertos !== 1 ? 's' : ''}`,
  }
}

async function cargarTopProductos() {
  try {
    const { data } = await client.get(`/reportes/productos-vendidos?desde=${inicioMes()}&hasta=${isoDate(0)}`)
    topProductos.value = (data.data ?? []).slice(0, 5)
  } catch { topProductos.value = [] }
}

async function cargarStockBajo() {
  try {
    const { data } = await client.get('/inventario/stock-bajo')
    stockBajo.value = data.data ?? []
  } catch { stockBajo.value = [] }
}

async function cargarTurnos() {
  try {
    const { data } = await client.get(`/turnos?fecha=${isoDate(0)}`)
    const lista = data.data?.data ?? data.data ?? []
    turnos.value = Array.isArray(lista) ? lista : []
  } catch { turnos.value = [] }
}
</script>
