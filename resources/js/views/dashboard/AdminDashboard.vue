<template>
  <div class="space-y-6">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">
          Bienvenido, {{ primerNombre }}
        </h1>
        <p class="text-ink-mute text-sm mt-1">{{ fechaHoy }} · Panel de Administración</p>
      </div>
      <div class="flex gap-2 flex-wrap">
        <RouterLink to="/register"
          class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors">
          + Nuevo usuario
        </RouterLink>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <StatCard label="Ventas hoy"        :value="kpis.ventasHoy"      :delta="kpis.deltaVentas"  variante="ok" />
      <StatCard label="Turnos activos"    :value="kpis.turnosActivos"  variante="neutral" />
      <StatCard label="Stock bajo alerta" :value="kpis.stockBajo"      :variante="kpis.stockBajoNum > 0 ? 'err' : 'ok'" />
      <StatCard label="Usuarios activos"  :value="kpis.usuariosActivos" variante="neutral" />
    </div>

    <!-- Gráfico ventas + alertas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <div class="lg:col-span-2 bg-card border border-edge rounded-xl p-5 flex flex-col">
        <div class="flex items-center justify-between mb-4 flex-shrink-0">
          <h2 class="font-display text-lg text-ink font-medium">Ventas — últimos 7 días</h2>
          <span class="text-ink-dim text-xs font-mono">Bs.</span>
        </div>
        <div v-if="cargandoGrafico" class="flex-1 flex items-center justify-center text-ink-dim text-sm">
          Cargando...
        </div>
        <MiniChart v-else :datos="ventasSemana" tipo="bar" class="flex-1 min-h-0" />
        <div class="flex justify-between mt-3 px-1 flex-shrink-0">
          <span v-for="dia in labelsSemana" :key="dia" class="text-ink-dim text-[10px]">{{ dia }}</span>
        </div>
      </div>

      <div class="bg-card border border-edge rounded-xl p-5 flex flex-col gap-5">

        <div>
          <h2 class="font-display text-lg text-ink font-medium mb-4">Alertas del sistema</h2>

          <template v-if="sinAlertas">
            <div class="text-ink-mute text-sm py-4 text-center">Sin alertas críticas ✓</div>
          </template>

          <!-- Stock crítico (0) -->
          <div v-if="stockCritico.length > 0">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-err mb-2">
              <AlertBadge texto="Sin stock" severidad="err" />
              {{ stockCritico.length }} producto(s) sin stock
            </h3>
            <div v-for="p in stockCritico" :key="p.id" class="flex items-center justify-between py-1 px-3 bg-err/5 rounded-lg text-sm mb-1">
              <span class="text-ink font-medium">{{ p.nombre }}</span>
              <span class="text-ink-dim text-xs">{{ p.categoria }}</span>
            </div>
          </div>

          <!-- Stock bajo -->
          <div v-if="stockBajo.length > 0">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-warn mb-2 mt-4">
              <AlertBadge texto="Stock bajo" severidad="warn" />
              {{ stockBajo.length }} producto(s) bajo mínimo
            </h3>
            <div v-for="p in stockBajo" :key="p.id" class="mb-1 last:mb-0">
              <div class="flex items-center justify-between py-1 px-3 bg-warning/5 rounded-lg text-sm">
                <span class="text-ink font-medium">{{ p.nombre }}</span>
                <span class="text-ink-mute text-xs font-mono whitespace-nowrap ml-2">
                  {{ p.stock_actual }} / {{ p.stock_minimo }} {{ p.unidad_medida }}
                </span>
              </div>
              <div v-if="p.menus.length > 0" class="ml-4 mt-0.5 mb-1 text-xs text-ink-dim">
                Afecta a: <span v-for="(m, i) in p.menus" :key="m.id">{{ m.nombre }}<template v-if="i < p.menus.length - 1">, </template></span>
              </div>
            </div>
          </div>

          <!-- Vencimientos -->
          <div v-if="vencimientos.length > 0">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-warn mb-2 mt-4">
              <AlertBadge texto="Vencimiento" severidad="warn" />
              {{ vencimientos.length }} lote(s) vencen pronto
            </h3>
            <div v-for="l in vencimientos" :key="l.id" class="flex items-center justify-between py-1 px-3 bg-warning/5 rounded-lg text-sm mb-1">
              <span class="text-ink font-medium">{{ l.producto_nombre }}</span>
              <span class="text-ink-mute text-xs font-mono whitespace-nowrap ml-2">Lote {{ l.numero_lote }} — {{ l.dias_restantes }}d</span>
            </div>
          </div>

          <!-- Turnos abiertos del día anterior -->
          <div v-if="turnosAbiertosAnterior.length > 0">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-err mb-2 mt-4">
              <AlertBadge texto="Turno abierto" severidad="err" />
              {{ turnosAbiertosAnterior.length }} turno(s) sin cerrar
            </h3>
            <div v-for="t in turnosAbiertosAnterior" :key="t.id" class="flex items-center justify-between py-1 px-3 bg-err/5 rounded-lg text-sm mb-1">
              <span class="text-ink font-medium">{{ t.usuario }}</span>
              <span class="text-ink-dim text-xs font-mono whitespace-nowrap ml-2">{{ formatFecha(t.fecha_apertura) }}</span>
            </div>
          </div>

          <!-- Proveedores sin compras -->
          <div v-if="proveedoresSinCompras.length > 0">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-ink mb-2 mt-4">
              <AlertBadge texto="Inactivo" severidad="info" />
              {{ proveedoresSinCompras.length }} proveedor(es) sin compras recientes
            </h3>
            <div v-for="p in proveedoresSinCompras" :key="p.id" class="flex items-center justify-between py-1 px-3 bg-elevated/5 rounded-lg text-sm mb-1">
              <span class="text-ink font-medium">{{ p.nombre_empresa }}</span>
              <span class="text-ink-dim text-xs whitespace-nowrap ml-2">{{ p.ultima_compra ? formatFecha(p.ultima_compra) : 'Sin compras' }}</span>
            </div>
          </div>

          <!-- Discrepancias en cierres -->
          <div v-if="discrepanciasCierres.length > 0">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-err mb-2 mt-4">
              <AlertBadge texto="Discrepancia" severidad="err" />
              {{ discrepanciasCierres.length }} cierre(s) con diferencia
            </h3>
            <div v-for="d in discrepanciasCierres.slice(0, 5)" :key="d.id" class="flex items-center justify-between py-1 px-3 bg-err/5 rounded-lg text-sm mb-1">
              <span class="text-ink font-medium">{{ d.codigo }}</span>
              <span :class="['text-xs font-mono whitespace-nowrap ml-2', d.diferencia < 0 ? 'text-err' : 'text-warn']">
                {{ d.diferencia > 0 ? '+' : '' }}{{ d.diferencia.toFixed(2) }}
              </span>
            </div>
          </div>
        </div>
    </div>

    </div>

    <!-- Últimas ventas del día -->
    <div class="bg-card border border-edge rounded-xl">
      <div class="flex items-center justify-between p-5 border-b border-edge">
        <h2 class="font-display text-lg text-ink font-medium">Últimas ventas del día</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider">
              <th class="text-left px-5 py-3">Hora</th>
              <th class="text-left px-5 py-3">Usuario</th>
              <th class="text-left px-5 py-3">Método</th>
              <th class="text-right px-5 py-3">Total</th>
              <th class="text-left px-5 py-3">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="ultimasVentas.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-ink-mute">Sin ventas hoy</td>
            </tr>
            <tr v-for="v in ultimasVentas" :key="v.id" class="border-t border-edge hover:bg-elevated transition-colors">
              <td class="px-5 py-3 font-mono text-ink-mute text-xs">{{ formatHora(v.fecha) }}</td>
              <td class="px-5 py-3 text-ink">{{ v.usuario?.nombre_completo ?? '—' }}</td>
              <td class="px-5 py-3 text-ink-mute capitalize">{{ v.metodo_pago }}</td>
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
import client from '@/api/client.js'
import StatCard   from '@/components/StatCard.vue'
import AlertBadge from '@/components/AlertBadge.vue'
import MiniChart  from '@/components/MiniChart.vue'

const auth = useAuthStore()

const kpis          = ref({ ventasHoy: 'Bs. 0', deltaVentas: null, turnosActivos: 0, stockBajo: 0, stockBajoNum: 0, usuariosActivos: 0 })
const ultimasVentas = ref([])
const ventasSemana  = ref([0,0,0,0,0,0,0])
const stockBajo     = ref([])
const vencimientos  = ref([])
const stockCritico  = ref([])
const turnosAbiertosAnterior = ref([])
const proveedoresSinCompras  = ref([])
const discrepanciasCierres   = ref([])
const cargandoGrafico = ref(true)

const sinAlertas = computed(() =>
  stockBajo.value.length === 0 &&
  vencimientos.value.length === 0 &&
  stockCritico.value.length === 0 &&
  turnosAbiertosAnterior.value.length === 0 &&
  proveedoresSinCompras.value.length === 0 &&
  discrepanciasCierres.value.length === 0
)

const primerNombre = computed(() => auth.nombreCompleto.split(' ')[0] || 'Administrador')
const fechaHoy     = computed(() => new Date().toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))
const labelsSemana = computed(() => {
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(); d.setDate(d.getDate() - (6 - i))
    return d.toLocaleDateString('es-BO', { weekday: 'short' })
  })
})

function isoDate(offset = 0) {
  const d = new Date(); d.setDate(d.getDate() - offset)
  return d.toISOString().split('T')[0]
}
function formatHora(iso) {
  return new Date(iso).toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
}
function formatFecha(iso) {
  return new Date(iso).toLocaleDateString('es-BO', { day: 'numeric', month: 'short' })
}

onMounted(() => Promise.all([cargarKpis(), cargarVentas(), cargarGrafico(), cargarAlertas()]))

async function cargarKpis() {
  const [rHoy, rAyer, rTurnos, rStock, rUsers] = await Promise.allSettled([
    client.get(`/reportes/ventas-diarias?fecha=${isoDate(0)}`),
    client.get(`/reportes/ventas-diarias?fecha=${isoDate(1)}`),
    client.get('/turnos?estado=abierto'),
    client.get('/inventario/stock-bajo'),
    client.get('/usuarios'),
  ])

  const ventasHoy  = rHoy.status  === 'fulfilled' ? Number(rHoy.value.data.data?.total_ventas  ?? 0) : 0
  const ventasAyer = rAyer.status === 'fulfilled' ? Number(rAyer.value.data.data?.total_ventas ?? 0) : 0
  const delta      = ventasAyer > 0 ? Math.round(((ventasHoy - ventasAyer) / ventasAyer) * 1000) / 10 : null
  const turnos     = rTurnos.status === 'fulfilled' ? (rTurnos.value.data.data?.data ?? rTurnos.value.data.data ?? []) : []
  const stock      = rStock.status  === 'fulfilled' ? (rStock.value.data.data ?? [])  : []
  const users      = rUsers.status  === 'fulfilled' ? (rUsers.value.data.data ?? [])  : []

  kpis.value = {
    ventasHoy:      `Bs. ${ventasHoy.toFixed(2)}`,
    deltaVentas:    delta,
    turnosActivos:  Array.isArray(turnos) ? turnos.length : 0,
    stockBajo:      Array.isArray(stock)  ? stock.length  : 0,
    stockBajoNum:   Array.isArray(stock)  ? stock.length  : 0,
    usuariosActivos: Array.isArray(users) ? users.filter(u => u.activo !== false).length : 0,
  }
}

async function cargarVentas() {
  try {
    const { data } = await client.get(`/ventas?fecha=${isoDate(0)}`)
    const lista = data.data?.data ?? data.data ?? []
    ultimasVentas.value = Array.isArray(lista) ? lista.slice(0, 10) : []
  } catch { ultimasVentas.value = [] }
}

async function cargarGrafico() {
  cargandoGrafico.value = true
  try {
    const resultados = await Promise.allSettled(
      Array.from({ length: 7 }, (_, i) =>
        client.get(`/reportes/ventas-diarias?fecha=${isoDate(6 - i)}`)
          .then(r => Number(r.data.data?.total_ventas ?? 0))
      )
    )
    ventasSemana.value = resultados.map(r => r.status === 'fulfilled' ? r.value : 0)
  } finally {
    cargandoGrafico.value = false
  }
}

async function cargarAlertas() {
  const r = await client.get('/inventario/alertas-dashboard').catch(() => null)
  const data = r?.data?.data
  stockBajo.value             = data?.stock_bajo             ?? []
  vencimientos.value          = data?.vencimientos           ?? []
  stockCritico.value          = data?.stock_critico          ?? []
  turnosAbiertosAnterior.value = data?.turnos_abiertos_anterior ?? []
  proveedoresSinCompras.value  = data?.proveedores_sin_compras  ?? []
  discrepanciasCierres.value   = data?.discrepancias_cierres    ?? []
}
</script>
