<template>
  <div class="space-y-6">
    <div>
      <h1 class="font-display text-3xl text-ink font-semibold">Reportes</h1>
      <p class="text-ink-mute text-sm mt-1">Análisis de ventas, productos, balances y cierres</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-elevated/50 border border-edge rounded-xl p-1 overflow-x-auto">
      <button v-for="tab in TABS" :key="tab.key" @click="tabActivo = tab.key"
        class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors"
        :class="tabActivo === tab.key ? 'bg-card text-ink border border-edge shadow-sm' : 'text-ink-mute hover:text-ink'">
        {{ tab.label }}
      </button>
    </div>

    <!-- ════════════════ 1. Ventas Diarias ════════════════ -->
    <div v-if="tabActivo === 'ventas-diarias'" class="space-y-4">
      <div class="flex items-center gap-3">
        <input v-model="vdFecha" type="date"
          class="bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
        <button @click="cargarVentasDiarias" :disabled="cargandoVD"
          class="px-4 py-2 bg-amber hover:bg-amber-bright text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
          {{ cargandoVD ? 'Cargando...' : 'Consultar' }}
        </button>
      </div>

      <div v-if="vdData" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <StatCard label="Ventas" :value="`Bs. ${Number(vdData.total_ventas ?? 0).toFixed(2)}`" variante="ok" />
        <StatCard label="Costo" :value="`Bs. ${Number(vdData.total_costo ?? 0).toFixed(2)}`" variante="err" />
        <StatCard label="Utilidad Bruta" :value="`Bs. ${Number(vdData.utilidad_bruta ?? 0).toFixed(2)}`" variante="ok" />
        <StatCard label="Ticket Promedio" :value="`Bs. ${Number(vdData.ticket_promedio ?? 0).toFixed(2)}`" />
      </div>

      <div v-if="vdData" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-card border border-edge rounded-xl p-5">
          <h3 class="font-display text-sm text-ink font-medium mb-3">Transacciones</h3>
          <p class="font-mono text-2xl text-ink">{{ vdData.num_transacciones ?? 0 }}</p>
        </div>
        <div class="bg-card border border-edge rounded-xl p-5">
          <h3 class="font-display text-sm text-ink font-medium mb-3">Por método de pago</h3>
          <div v-if="vdData.por_metodo_pago" class="space-y-2">
            <div v-for="(monto, metodo) in vdData.por_metodo_pago" :key="metodo"
              class="flex items-center justify-between text-sm">
              <span class="text-ink-mute capitalize">{{ metodo }}</span>
              <span class="font-mono text-ink">Bs. {{ Number(monto).toFixed(2) }}</span>
            </div>
          </div>
          <p v-else class="text-ink-dim text-sm italic">Sin datos</p>
        </div>
      </div>
    </div>

    <!-- ════════════════ 2. Productos Vendidos ════════════════ -->
    <div v-if="tabActivo === 'productos-vendidos'" class="space-y-4">
      <div class="flex flex-wrap items-center gap-3">
        <div>
          <label class="block text-ink-mute text-xs mb-1">Desde</label>
          <input v-model="pvDesde" type="date"
            class="bg-elevated border border-edge rounded-lg px-4 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
        </div>
        <div>
          <label class="block text-ink-mute text-xs mb-1">Hasta</label>
          <input v-model="pvHasta" type="date"
            class="bg-elevated border border-edge rounded-lg px-4 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
        </div>
        <button @click="cargarProductosVendidos" :disabled="cargandoPV"
          class="mt-4 px-4 py-2 bg-amber hover:bg-amber-bright text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
          {{ cargandoPV ? 'Cargando...' : 'Consultar' }}
        </button>
      </div>

      <div v-if="pvData.length > 0" class="bg-card border border-edge rounded-xl overflow-hidden">
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50 sticky top-0">
                <th class="text-left px-5 py-3">#</th>
                <th class="text-left px-5 py-3">Producto</th>
                <th class="text-right px-5 py-3">Cantidad</th>
                <th class="text-right px-5 py-3">Ingresos</th>
                <th class="text-right px-5 py-3">Costo Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(p, i) in pvData" :key="p.id" class="border-t border-edge hover:bg-elevated/30">
                <td class="px-5 py-3 text-ink-dim text-xs">{{ i + 1 }}</td>
                <td class="px-5 py-3 text-ink font-medium">{{ p.nombre }}</td>
                <td class="px-5 py-3 text-right font-mono text-ink">{{ p.total_vendido }}</td>
                <td class="px-5 py-3 text-right font-mono text-ok">Bs. {{ Number(p.ingresos).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right font-mono text-err">Bs. {{ Number(p.costo_total).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <p v-else-if="pvCargado" class="text-ink-mute text-sm italic">Sin resultados para el período</p>
    </div>

    <!-- ════════════════ 3. Balance Diario ════════════════ -->
    <div v-if="tabActivo === 'balance-diario'" class="space-y-4">
      <div class="flex items-center gap-3">
        <input v-model="bdFecha" type="date"
          class="bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
        <button @click="cargarBalanceDiario" :disabled="cargandoBD"
          class="px-4 py-2 bg-amber hover:bg-amber-bright text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
          {{ cargandoBD ? 'Cargando...' : 'Consultar' }}
        </button>
      </div>

      <div v-if="bdData" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <StatCard label="Ingresos Ventas" :value="`Bs. ${Number(bdData.ingresos_ventas ?? 0).toFixed(2)}`" variante="ok" />
        <StatCard label="CMV" :value="`Bs. ${Number(bdData.costo_mercancia_vendida ?? 0).toFixed(2)}`" variante="err" />
        <StatCard label="Gastos Operativos" :value="`Bs. ${Number(bdData.gastos_operativos ?? 0).toFixed(2)}`" variante="warn" />
        <StatCard label="Utilidad Neta" :value="`Bs. ${Number((bdData.utilidad_neta ?? 0)).toFixed(2)}`"
          :variante="(bdData.utilidad_neta ?? 0) >= 0 ? 'ok' : 'err'" />
      </div>

      <div v-if="bdData" class="bg-card border border-edge rounded-xl p-5">
        <h3 class="font-display text-sm text-ink font-medium mb-3 border-b border-edge pb-2">Desglose</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-ink-mute">Total Ingresos</span>
            <p class="font-mono text-ok">Bs. {{ Number(bdData.total_ingresos ?? 0).toFixed(2) }}</p>
          </div>
          <div>
            <span class="text-ink-mute">Total Egresos</span>
            <p class="font-mono text-err">Bs. {{ Number(bdData.total_egresos ?? 0).toFixed(2) }}</p>
          </div>
          <div>
            <span class="text-ink-mute">Otros Ingresos</span>
            <p class="font-mono text-ink-dim">Bs. {{ Number(bdData.otros_ingresos ?? 0).toFixed(2) }}</p>
          </div>
          <div>
            <span class="text-ink-mute">Utilidad Bruta</span>
            <p class="font-mono text-ok">Bs. {{ Number(bdData.utilidad_bruta ?? 0).toFixed(2) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ════════════════ 4. Resumen Mensual ════════════════ -->
    <div v-if="tabActivo === 'resumen-mensual'" class="space-y-4">
      <div class="flex items-center gap-3">
        <select v-model.number="rmMes"
          class="bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
          <option v-for="m in MESES" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
        <select v-model.number="rmAnio"
          class="bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
          <option v-for="a in ANIOS" :key="a" :value="a">{{ a }}</option>
        </select>
        <button @click="cargarResumenMensual" :disabled="cargandoRM"
          class="px-4 py-2 bg-amber hover:bg-amber-bright text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
          {{ cargandoRM ? 'Cargando...' : 'Consultar' }}
        </button>
      </div>

      <div v-if="rmData" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <StatCard label="Ventas del Mes" :value="`Bs. ${Number(rmData.total_ventas ?? 0).toFixed(2)}`" variante="ok" />
        <StatCard label="Costo Mercancía" :value="`Bs. ${Number(rmData.total_costo_mercancia ?? 0).toFixed(2)}`" variante="err" />
        <StatCard v-if="rmData.utilidad_bruta != null" label="Utilidad Bruta" :value="`Bs. ${Number(rmData.utilidad_bruta).toFixed(2)}`" variante="ok" />
        <StatCard v-if="rmData.utilidad_neta != null" label="Utilidad Neta" :value="`Bs. ${Number(rmData.utilidad_neta).toFixed(2)}`"
          :variante="(rmData.utilidad_neta ?? 0) >= 0 ? 'ok' : 'err'" />
      </div>

      <div v-if="rmData" class="bg-card border border-edge rounded-xl p-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
          <div>
            <span class="text-ink-mute">Transacciones</span>
            <p class="font-mono text-ink text-lg">{{ rmData.num_ventas ?? 0 }}</p>
          </div>
          <div>
            <span class="text-ink-mute">Ticket Promedio</span>
            <p class="font-mono text-ink text-lg">Bs. {{ Number(rmData.ticket_promedio ?? 0).toFixed(2) }}</p>
          </div>
          <div v-if="rmData.total_gastos_operativos != null">
            <span class="text-ink-mute">Gastos Operativos</span>
            <p class="font-mono text-warn text-lg">Bs. {{ Number(rmData.total_gastos_operativos).toFixed(2) }}</p>
          </div>
          <div v-if="rmData.producto_mas_vendido">
            <span class="text-ink-mute">Producto Top</span>
            <p class="text-ink text-lg truncate">{{ rmData.producto_mas_vendido }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ════════════════ 5. Cierres Diarios ════════════════ -->
    <div v-if="tabActivo === 'cierres-diarios'" class="space-y-4">
      <div class="flex items-center gap-3">
        <select v-model.number="cdMes"
          class="bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
          <option v-for="m in MESES" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
        <select v-model.number="cdAnio"
          class="bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
          <option v-for="a in ANIOS" :key="a" :value="a">{{ a }}</option>
        </select>
        <button @click="cargarCierresDiarios" :disabled="cargandoCD"
          class="px-4 py-2 bg-amber hover:bg-amber-bright text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
          {{ cargandoCD ? 'Cargando...' : 'Consultar' }}
        </button>
      </div>

      <div v-if="cdData.length > 0" class="bg-card border border-edge rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
                <th class="text-left px-5 py-3">Fecha</th>
                <th class="text-right px-5 py-3">Ventas</th>
                <th class="text-right px-5 py-3">Efectivo</th>
                <th class="text-right px-5 py-3">Tarjeta</th>
                <th class="text-right px-5 py-3">Transferencia</th>
                <th class="text-right px-5 py-3">Compras</th>
                <th class="text-right px-5 py-3">Gastos</th>
                <th class="text-right px-5 py-3"># Ventas</th>
                <th class="text-center px-5 py-3">Estado</th>
                <th class="text-left px-5 py-3">Cerró</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in cdData" :key="c.id" class="border-t border-edge hover:bg-elevated/30">
                <td class="px-5 py-3 text-ink-dim text-xs">{{ c.fecha }}</td>
                <td class="px-5 py-3 text-right font-mono text-ok">Bs. {{ Number(c.total_ventas).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right font-mono text-ink-dim">Bs. {{ Number(c.total_ventas_efectivo).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right font-mono text-ink-dim">Bs. {{ Number(c.total_ventas_tarjeta).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right font-mono text-ink-dim">Bs. {{ Number(c.total_ventas_transferencia).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right font-mono text-err">Bs. {{ Number(c.total_compras).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right font-mono text-warn">Bs. {{ Number(c.total_gastos_operativos).toFixed(2) }}</td>
                <td class="px-5 py-3 text-right text-ink-dim">{{ c.num_ventas }}</td>
                <td class="px-5 py-3 text-center">
                  <AlertBadge :texto="c.estado" :severidad="c.estado === 'cerrado' ? 'ok' : 'warn'" />
                </td>
                <td class="px-5 py-3 text-ink-dim text-xs">{{ c.usuario?.nombre || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <p v-else-if="cdCargado" class="text-ink-mute text-sm italic">Sin cierres para este período</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '@/api/client.js'
import StatCard from '@/components/StatCard.vue'
import AlertBadge from '@/components/AlertBadge.vue'

const TABS = [
  { key: 'ventas-diarias',     label: 'Ventas Diarias' },
  { key: 'productos-vendidos', label: 'Productos Vendidos' },
  { key: 'balance-diario',     label: 'Balance Diario' },
  { key: 'resumen-mensual',    label: 'Resumen Mensual' },
  { key: 'cierres-diarios',    label: 'Cierres Diarios' },
]

const tabActivo = ref('ventas-diarias')

const MESES = [
  { value: 1, label: 'Enero' }, { value: 2, label: 'Febrero' },
  { value: 3, label: 'Marzo' }, { value: 4, label: 'Abril' },
  { value: 5, label: 'Mayo' }, { value: 6, label: 'Junio' },
  { value: 7, label: 'Julio' }, { value: 8, label: 'Agosto' },
  { value: 9, label: 'Septiembre' }, { value: 10, label: 'Octubre' },
  { value: 11, label: 'Noviembre' }, { value: 12, label: 'Diciembre' },
]
const ahora = new Date()
const anioActual = ahora.getFullYear()
const ANIOS = Array.from({ length: 5 }, (_, i) => anioActual - i)

// ── Ventas Diarias ──────────────────────────────────────────────────────────
const vdFecha = ref(new Date().toISOString().slice(0, 10))
const vdData = ref(null)
const cargandoVD = ref(false)

async function cargarVentasDiarias() {
  cargandoVD.value = true
  try {
    const { data } = await client.get('/reportes/ventas-diarias', { params: { fecha: vdFecha.value } })
    vdData.value = data.data
  } catch { vdData.value = null }
  finally { cargandoVD.value = false }
}

// ── Productos Vendidos ──────────────────────────────────────────────────────
const inicioMes = new Date(ahora.getFullYear(), ahora.getMonth(), 1).toISOString().slice(0, 10)
const pvDesde = ref(inicioMes)
const pvHasta = ref(new Date().toISOString().slice(0, 10))
const pvData = ref([])
const pvCargado = ref(false)
const cargandoPV = ref(false)

async function cargarProductosVendidos() {
  cargandoPV.value = true; pvCargado.value = false
  try {
    const { data } = await client.get('/reportes/productos-vendidos', { params: { desde: pvDesde.value, hasta: pvHasta.value } })
    pvData.value = data.data ?? []
  } catch { pvData.value = [] }
  finally { pvCargado.value = true; cargandoPV.value = false }
}

// ── Balance Diario ──────────────────────────────────────────────────────────
const bdFecha = ref(new Date().toISOString().slice(0, 10))
const bdData = ref(null)
const cargandoBD = ref(false)

async function cargarBalanceDiario() {
  cargandoBD.value = true
  try {
    const { data } = await client.get('/reportes/balance-diario', { params: { fecha: bdFecha.value } })
    bdData.value = data.data
  } catch { bdData.value = null }
  finally { cargandoBD.value = false }
}

// ── Resumen Mensual ─────────────────────────────────────────────────────────
const rmMes = ref(ahora.getMonth() + 1)
const rmAnio = ref(anioActual)
const rmData = ref(null)
const cargandoRM = ref(false)

async function cargarResumenMensual() {
  cargandoRM.value = true
  try {
    const { data } = await client.get('/reportes/resumen-mensual', { params: { mes: rmMes.value, anio: rmAnio.value } })
    rmData.value = data.data
  } catch { rmData.value = null }
  finally { cargandoRM.value = false }
}

// ── Cierres Diarios ─────────────────────────────────────────────────────────
const cdMes = ref(ahora.getMonth() + 1)
const cdAnio = ref(anioActual)
const cdData = ref([])
const cdCargado = ref(false)
const cargandoCD = ref(false)

async function cargarCierresDiarios() {
  cargandoCD.value = true; cdCargado.value = false
  try {
    const { data } = await client.get('/reportes/cierres-diarios', { params: { mes: cdMes.value, anio: cdAnio.value } })
    cdData.value = data.data ?? []
  } catch { cdData.value = [] }
  finally { cdCargado.value = true; cargandoCD.value = false }
}

onMounted(() => {
  cargarVentasDiarias()
})
</script>
