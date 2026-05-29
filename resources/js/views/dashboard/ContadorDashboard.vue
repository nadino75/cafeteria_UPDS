<template>
  <div class="space-y-6">

    <div>
      <h1 class="font-display text-3xl text-ink font-semibold">Panel Contable</h1>
      <p class="text-ink-mute text-sm mt-1">Solo lectura · {{ fechaHoy }}</p>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard label="Balance del día" :value="kpis.balance" :variante="kpis.balanceNum >= 0 ? 'ok' : 'err'" />
      <StatCard label="Ingresos"        :value="kpis.ingresos" variante="ok" />
      <StatCard label="Egresos"         :value="kpis.egresos"  variante="warn" />
      <StatCard label="CMV (FIFO)"      :value="kpis.cmv"      variante="neutral" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Resumen mensual -->
      <div class="bg-card border border-edge rounded-xl p-5">
        <h2 class="font-display text-lg text-ink font-medium mb-4">Resumen mensual</h2>
        <div v-if="resumenMensual" class="space-y-3">
          <div class="flex justify-between py-2 border-b border-edge">
            <span class="text-ink-mute text-sm">Total ventas</span>
            <span class="font-mono text-ok text-sm">Bs. {{ Number(resumenMensual.total_ventas ?? 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-edge">
            <span class="text-ink-mute text-sm">Costo mercancía (CMV)</span>
            <span class="font-mono text-warn text-sm">Bs. {{ Number(resumenMensual.total_costo_mercancia ?? 0).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between py-2 border-b border-edge">
            <span class="text-ink-mute text-sm">N° de ventas</span>
            <span class="font-mono text-ink text-sm">{{ resumenMensual.num_ventas ?? 0 }}</span>
          </div>
          <div class="flex justify-between py-2">
            <span class="text-ink-mute text-sm">Ticket promedio</span>
            <span class="font-mono text-ink text-sm">Bs. {{ Number(resumenMensual.ticket_promedio ?? 0).toFixed(2) }}</span>
          </div>
        </div>
        <div v-else class="text-ink-mute text-sm py-4 text-center">Sin datos para este mes</div>
      </div>

      <!-- Cierres diarios del mes -->
      <div class="bg-card border border-edge rounded-xl">
        <div class="p-5 border-b border-edge">
          <h2 class="font-display text-lg text-ink font-medium">Cierres diarios del mes</h2>
        </div>
        <div class="overflow-x-auto max-h-72 overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="sticky top-0 bg-card">
              <tr class="text-ink-dim text-xs uppercase tracking-wider">
                <th class="text-left px-5 py-3">Fecha</th>
                <th class="text-right px-5 py-3">Ventas</th>
                <th class="text-right px-5 py-3">Egresos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cierres.length === 0">
                <td colspan="3" class="px-5 py-6 text-center text-ink-mute">Sin cierres este mes</td>
              </tr>
              <tr v-for="c in cierres" :key="c.id" class="border-t border-edge hover:bg-elevated transition-colors">
                <td class="px-5 py-3 font-mono text-ink-mute text-xs">{{ formatFecha(c.fecha) }}</td>
                <td class="px-5 py-3 text-right font-mono text-ok text-xs">Bs. {{ Number(c.total_ventas ?? 0).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right font-mono text-warn text-xs">Bs. {{ Number(c.total_egresos ?? 0).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client.js'
import StatCard from '@/components/StatCard.vue'

const kpis           = ref({ balance: 'Bs. 0', balanceNum: 0, ingresos: 'Bs. 0', egresos: 'Bs. 0', cmv: 'Bs. 0' })
const cierres        = ref([])
const resumenMensual = ref(null)

const fechaHoy = computed(() =>
  new Date().toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

function isoDate()   { return new Date().toISOString().split('T')[0] }
function mesActual() { return new Date().getMonth() + 1 }
function anioActual(){ return new Date().getFullYear() }
function formatFecha(iso) { return iso ? new Date(iso).toLocaleDateString('es-BO') : '—' }

onMounted(() => Promise.all([cargarKpis(), cargarCierres(), cargarResumenMensual()]))

async function cargarKpis() {
  try {
    const { data } = await client.get(`/reportes/balance-diario?fecha=${isoDate()}`)
    const balance = data.data

    if (balance) {
      const ingresos = Number(balance.total_ventas ?? 0)
      const cmv      = Number(balance.cmv ?? balance.total_costo_mercancia ?? 0)
      const egresos  = Number(balance.total_egresos ?? cmv)
      const neto     = ingresos - egresos
      kpis.value = {
        balance:    `Bs. ${neto.toFixed(2)}`,
        balanceNum: neto,
        ingresos:   `Bs. ${ingresos.toFixed(2)}`,
        egresos:    `Bs. ${egresos.toFixed(2)}`,
        cmv:        `Bs. ${cmv.toFixed(2)}`,
      }
    } else {
      const r = await client.get(`/reportes/ventas-diarias?fecha=${isoDate()}`).catch(() => ({ data: { data: {} } }))
      const ingresos = Number(r.data.data?.total_ventas ?? 0)
      const cmv      = Number(r.data.data?.total_costo  ?? 0)
      kpis.value = {
        balance:    `Bs. ${(ingresos - cmv).toFixed(2)}`,
        balanceNum: ingresos - cmv,
        ingresos:   `Bs. ${ingresos.toFixed(2)}`,
        egresos:    `Bs. ${cmv.toFixed(2)}`,
        cmv:        `Bs. ${cmv.toFixed(2)}`,
      }
    }
  } catch { /* mantener valores en cero */ }
}

async function cargarCierres() {
  try {
    const { data } = await client.get(`/reportes/cierres-diarios?mes=${mesActual()}&anio=${anioActual()}`)
    cierres.value = Array.isArray(data.data) ? data.data : []
  } catch { cierres.value = [] }
}

async function cargarResumenMensual() {
  try {
    const { data } = await client.get(`/reportes/resumen-mensual?mes=${mesActual()}&anio=${anioActual()}`)
    resumenMensual.value = data.data ?? null
  } catch { resumenMensual.value = null }
}
</script>
