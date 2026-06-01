<template>
  <div class="h-full">

    <!-- ═══ Sin turno activo ═══ -->
    <div v-if="!turnoActivo && !cargandoTurno" class="flex items-center justify-center min-h-[60vh]">
      <div class="bg-card border border-edge rounded-2xl p-8 w-full max-w-sm text-center">
        <div class="w-14 h-14 rounded-full bg-amber/10 flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
          </svg>
        </div>
        <h2 class="font-display text-2xl text-ink font-medium mb-2">Sin turno activo</h2>
        <p class="text-ink-mute text-sm mb-6">Abre un turno para comenzar a vender.</p>
        <label class="block text-ink-mute text-sm mb-1.5 text-left">Caja inicial (Bs.)</label>
        <input v-model.number="cajaInicial" type="number" min="0" step="0.01" placeholder="0.00"
          class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber mb-4" />
        <p v-if="errorModal" class="text-err text-sm mb-3">{{ errorModal }}</p>
        <button @click="abrirTurno" :disabled="loadingModal"
          class="w-full py-3 bg-amber hover:bg-amber-bright text-base font-medium rounded-lg text-sm disabled:opacity-50 transition-colors">
          {{ loadingModal ? 'Abriendo...' : 'Abrir turno' }}
        </button>
      </div>
    </div>

    <div v-if="cargandoTurno" class="flex items-center justify-center min-h-[60vh]">
      <p class="text-ink-mute text-sm">Verificando turno...</p>
    </div>

    <!-- ═══ POS: Interfaz completa de venta ═══ -->
    <div v-if="turnoActivo" class="flex flex-col h-[calc(100vh-5rem)]">

      <!-- Barra superior -->
      <div class="flex items-center justify-between px-4 py-2.5 border-b border-edge bg-card shrink-0">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-ok" />
            <span class="text-ok text-xs font-medium uppercase tracking-wider">Abierto</span>
          </div>
          <span class="text-ink-dim text-xs font-mono">{{ turnoActivo.codigo }}</span>
          <span class="text-ink-dim text-xs">|</span>
          <span class="text-ink-dim text-xs">Apertura: {{ formatHora(turnoActivo.fecha_apertura) }}</span>
          <span class="text-ink-dim text-xs">|</span>
          <span class="text-ink-dim text-xs">Caja: Bs. {{ Number(turnoActivo.caja_inicial ?? 0).toFixed(2) }}</span>
        </div>
        <div class="flex items-center gap-3">
          <!-- Ventas del turno toggle -->
          <button @click="verVentas = !verVentas"
            class="flex items-center gap-1.5 px-3 py-1.5 border border-edge rounded-lg text-ink-dim hover:text-ink text-xs transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
            Ventas ({{ totalVentasHoy }})
          </button>
          <select v-model="nuevaVenta.metodo_pago"
            class="bg-elevated border border-edge rounded-lg px-3 py-1.5 text-ink text-xs focus:outline-none focus:border-amber">
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
            <option value="qr">QR</option>
            <option value="mixto">Mixto</option>
          </select>
          <button @click="modalCerrarTurno = true"
            class="px-4 py-1.5 border border-err/40 text-err hover:bg-err/10 rounded-lg text-xs transition-colors">
            Cerrar turno
          </button>
        </div>
      </div>

      <!-- Cuerpo principal: split menús + carrito -->
      <div class="flex flex-1 overflow-hidden">
        <!-- Panel de menús -->
        <div class="flex-1 flex flex-col p-4 overflow-hidden">
          <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 flex-1 overflow-y-auto pr-2 content-start">
            <MenuCard v-for="m in menusFiltrados" :key="m.id" :menu="m" @click="agregarItem(m)" />
            <div v-if="menusFiltrados.length === 0"
              class="col-span-full py-16 text-center text-ink-dim text-sm">Sin resultados</div>
          </div>
          <!-- Buscador -->
          <div class="relative mt-3 shrink-0">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-dim" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input v-model="busquedaMenu" type="text" placeholder="Buscar menú..."
              class="w-full bg-elevated border border-edge rounded-lg pl-10 pr-4 py-3 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
        </div>

        <!-- Carrito -->
        <div class="w-80 border-l border-edge bg-card flex flex-col shrink-0">
          <div class="p-4 border-b border-edge flex items-center justify-between">
            <h3 class="text-ink font-medium text-sm">Carrito</h3>
            <span v-if="nuevaVenta.items.length" class="text-ink-dim text-xs">{{ nuevaVenta.items.length }} item(s)</span>
          </div>

          <div v-if="nuevaVenta.items.length === 0" class="flex-1 flex items-center justify-center p-4">
            <p class="text-ink-dim text-sm text-center">Selecciona un menú<br>de la lista</p>
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

          <!-- Sección cliente -->
          <div class="px-4 py-3 border-t border-edge space-y-2">
            <label class="text-ink-dim text-xs font-medium uppercase tracking-wider">Cliente</label>

            <div v-if="clienteSeleccionado" class="flex items-center justify-between bg-elevated rounded-lg px-3 py-2">
              <div class="min-w-0 flex-1">
                <p class="text-ink text-sm font-medium truncate">{{ clienteSeleccionado.nombre }}</p>
                <p class="text-ink-dim text-xs truncate">{{ clienteSeleccionado.email || clienteSeleccionado.telefono || '—' }}</p>
              </div>
              <button @click="quitarCliente" class="text-ink-dim hover:text-err text-xs ml-2 shrink-0 transition-colors">Cambiar</button>
            </div>

            <div v-else class="relative">
              <input v-model="busquedaCliente" type="text" placeholder="Buscar o registrar cliente..."
                class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber"
                @input="buscarClientes" />
              <div v-if="resultadosClientes.length > 0"
                class="absolute top-full left-0 right-0 mt-1 bg-card border border-edge rounded-lg shadow-lg z-10 max-h-40 overflow-y-auto">
                <button v-for="c in resultadosClientes" :key="c.id" @click="seleccionarCliente(c)"
                  class="w-full text-left px-3 py-2 hover:bg-elevated text-ink text-sm transition-colors border-b border-edge last:border-0">
                  <span class="font-medium">{{ c.nombre }}</span>
                  <span class="text-ink-dim text-xs ml-2">{{ c.email || c.telefono || '' }}</span>
                </button>
              </div>
              <div v-if="busquedaCliente && resultadosClientes.length === 0 && !buscandoClientes"
                class="mt-1">
                <button @click="mostrarRegistroCliente = true"
                  class="w-full text-left px-3 py-2 rounded-lg border border-dashed border-edge text-ink-dim hover:text-ink hover:border-amber/40 text-sm transition-colors">
                  + Registrar "{{ busquedaCliente }}" como nuevo cliente
                </button>
              </div>
              <!-- Formulario registro rápido -->
              <div v-if="mostrarRegistroCliente" class="mt-2 space-y-2 p-3 bg-elevated rounded-lg border border-edge">
                <input v-model="nuevoCliente.nombre" type="text" placeholder="Nombre *"
                  class="w-full bg-card border border-edge rounded-lg px-3 py-2 text-ink text-sm uppercase focus:outline-none focus:border-amber"
                  @input="nuevoCliente.nombre = $event.target.value.toUpperCase()" />
                <input v-model="nuevoCliente.email" type="email" placeholder="Email"
                  class="w-full bg-card border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
                <input v-model="nuevoCliente.telefono" type="text" placeholder="Teléfono" maxlength="20"
                  class="w-full bg-card border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
                <div class="flex gap-2">
                  <button @click="mostrarRegistroCliente = false"
                    class="flex-1 border border-edge text-ink-mute py-2 rounded-lg text-xs hover:text-ink transition-colors">Cancelar</button>
                  <button @click="crearCliente" :disabled="guardandoCliente || !nuevoCliente.nombre.trim()"
                    class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2 rounded-lg text-xs disabled:opacity-50 transition-colors">
                    {{ guardandoCliente ? 'Registrando...' : 'Registrar' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="nuevaVenta.items.length > 0" class="p-4 border-t border-edge space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-ink font-medium">Total</span>
              <span class="font-mono text-amber text-xl font-bold">Bs. {{ totalNuevaVenta }}</span>
            </div>

            <!-- Efectivo -->
            <template v-if="nuevaVenta.metodo_pago === 'efectivo'">
              <div>
                <label class="text-ink-dim text-xs font-medium uppercase tracking-wider">Monto recibido</label>
                <input v-model.number="pagoEfectivo" type="number" min="0" step="0.01" placeholder="0.00"
                  class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber mt-1" />
              </div>
              <div v-if="pagoEfectivo >= Number(totalNuevaVenta)" class="flex justify-between items-center pt-1">
                <span class="text-ink-dim text-xs">Vuelto</span>
                <span class="font-mono text-ok text-sm font-medium">Bs. {{ vuelto.toFixed(2) }}</span>
              </div>
              <p v-if="pagoEfectivo > 0 && pagoEfectivo < Number(totalNuevaVenta)" class="text-err text-xs">El monto recibido no cubre el total</p>
            </template>

            <!-- Tarjeta / Transferencia / QR -->
            <template v-if="nuevaVenta.metodo_pago === 'tarjeta' || nuevaVenta.metodo_pago === 'transferencia' || nuevaVenta.metodo_pago === 'qr'">
              <p class="text-ink-dim text-xs">Total a cobrar: <span class="font-mono text-ink font-medium">Bs. {{ totalNuevaVenta }}</span></p>
            </template>

            <!-- Mixto -->
            <template v-if="nuevaVenta.metodo_pago === 'mixto'">
              <div>
                <label class="text-ink-dim text-xs font-medium uppercase tracking-wider">Efectivo</label>
                <input v-model.number="pagoEfectivo" type="number" min="0" step="0.01" placeholder="0.00"
                  class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber mt-1" />
              </div>
              <div>
                <label class="text-ink-dim text-xs font-medium uppercase tracking-wider">Tarjeta</label>
                <input v-model.number="pagoTarjeta" type="number" min="0" step="0.01" placeholder="0.00"
                  class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber mt-1" />
              </div>
              <div v-if="(pagoEfectivo || 0) + (pagoTarjeta || 0) >= Number(totalNuevaVenta)" class="flex justify-between items-center pt-1">
                <span class="text-ink-dim text-xs">Vuelto</span>
                <span class="font-mono text-ok text-sm font-medium">Bs. {{ vuelto.toFixed(2) }}</span>
              </div>
              <p v-if="(pagoEfectivo || 0) + (pagoTarjeta || 0) > 0 && (pagoEfectivo || 0) + (pagoTarjeta || 0) < Number(totalNuevaVenta)" class="text-err text-xs">La suma de los pagos no cubre el total</p>
            </template>

            <p v-if="errorPago" class="text-err text-xs">{{ errorPago }}</p>
            <p v-if="errorModal" class="text-err text-xs">{{ errorModal }}</p>
            <button @click="confirmarVenta" :disabled="loadingModal || nuevaVenta.items.length === 0"
              class="w-full py-3 bg-amber hover:bg-amber-bright text-base font-medium rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ loadingModal ? 'Registrando...' : 'Confirmar venta' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Panel lateral: Ventas del turno -->
      <Teleport to="body">
        <Transition name="slide">
          <div v-if="verVentas" class="fixed inset-y-0 right-0 z-40 w-96 bg-card border-l border-edge shadow-xl flex flex-col">
            <div class="flex items-center justify-between p-4 border-b border-edge">
              <h3 class="text-ink font-medium text-sm">Ventas del turno</h3>
              <button @click="verVentas = false" class="text-ink-dim hover:text-ink transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
              <div v-if="ventasTurno.length === 0" class="text-ink-mute text-sm text-center py-8">Sin ventas en este turno</div>
              <div v-for="v in ventasTurno" :key="v.id"
                class="bg-elevated rounded-lg p-3 flex items-center justify-between">
                <div>
                  <p class="text-ink-dim text-xs font-mono">{{ formatHora(v.fecha) }}</p>
                  <p class="text-ink capitalize text-sm">{{ v.metodo_pago }}</p>
                </div>
                <div class="text-right">
                  <p class="font-mono text-amber text-sm">Bs. {{ Number(v.total).toFixed(2) }}</p>
                  <AlertBadge :texto="v.estado" :severidad="v.estado === 'completada' ? 'ok' : 'err'" />
                </div>
              </div>
            </div>
            <div class="p-4 border-t border-edge">
              <div class="flex justify-between">
                <span class="text-ink-mute text-sm">Total acumulado</span>
                <span class="font-mono text-ok text-sm font-medium">Bs. {{ totalTurno }}</span>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>

    <!-- Modal: Cerrar turno -->
    <Teleport to="body">
      <div v-if="modalCerrarTurno" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
        <div class="bg-card border border-edge rounded-2xl w-full max-w-lg p-6 my-4">
          <h3 class="font-display text-xl text-ink font-medium mb-4">Cerrar turno</h3>
          <p class="text-ink-dim text-xs mb-4">{{ turnoActivo?.codigo }}</p>

          <div v-if="cargandoResumenCorte" class="text-ink-dim text-sm text-center py-8">Calculando...</div>

          <template v-if="!cargandoResumenCorte">
            <div class="grid grid-cols-2 gap-3 mb-4">
              <div>
                <label class="block text-ink-dim text-xs mb-1">Efectivo esperado</label>
                <input :value="resumenCorte.efectivo_esperado?.toFixed(2)" readonly
                  class="w-full bg-elevated/50 border border-edge rounded-lg px-3 py-2 text-ink-dim text-sm" />
              </div>
              <div>
                <label class="block text-ink-dim text-xs mb-1">Efectivo contado (entregado)</label>
                <input v-model.number="corte.total_efectivo_contado" type="number" min="0" step="0.01"
                  class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber"
                  :class="{ 'border-err/50': resumenCorte.efectivo_esperado && Number(corte.total_efectivo_contado) !== resumenCorte.efectivo_esperado }" />
                <p v-if="resumenCorte.efectivo_esperado && Number(corte.total_efectivo_contado) !== resumenCorte.efectivo_esperado"
                  class="text-err text-xs mt-1">Dif: Bs. {{ (Number(corte.total_efectivo_contado) - resumenCorte.efectivo_esperado).toFixed(2) }}</p>
              </div>
              <div>
                <label class="block text-ink-dim text-xs mb-1">Tarjeta esperado</label>
                <input :value="resumenCorte.tarjeta_esperado?.toFixed(2)" readonly
                  class="w-full bg-elevated/50 border border-edge rounded-lg px-3 py-2 text-ink-dim text-sm" />
              </div>
              <div>
                <label class="block text-ink-dim text-xs mb-1">Tarjeta contado</label>
                <input v-model.number="corte.total_tarjeta" type="number" min="0" step="0.01"
                  class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
              </div>
              <div>
                <label class="block text-ink-dim text-xs mb-1">Transferencia esperado</label>
                <input :value="resumenCorte.transferencia_esperado?.toFixed(2)" readonly
                  class="w-full bg-elevated/50 border border-edge rounded-lg px-3 py-2 text-ink-dim text-sm" />
              </div>
              <div>
                <label class="block text-ink-dim text-xs mb-1">Transferencia contado</label>
                <input v-model.number="corte.total_transferencia" type="number" min="0" step="0.01"
                  class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
              </div>
              <div>
                <label class="block text-ink-dim text-xs mb-1">QR esperado</label>
                <input :value="resumenCorte.qr_esperado?.toFixed(2)" readonly
                  class="w-full bg-elevated/50 border border-edge rounded-lg px-3 py-2 text-ink-dim text-sm" />
              </div>
              <div>
                <label class="block text-ink-dim text-xs mb-1">Total real</label>
                <input v-model.number="corte.total_real" type="number" min="0" step="0.01"
                  class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
              </div>
            </div>

            <div class="bg-elevated/50 rounded-lg p-3 mb-4 text-sm">
              <div class="flex justify-between">
                <span class="text-ink-dim">Caja final esperada:</span>
                <span class="font-mono text-ink font-medium">Bs. {{ resumenCorte.caja_final_esperada?.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between mt-1">
                <span class="text-ink-dim">Total ventas:</span>
                <span class="font-mono text-ink">Bs. {{ resumenCorte.total_ventas?.toFixed(2) }}</span>
              </div>
            </div>

            <div v-for="campo in camposCorte" :key="campo.key" class="mb-2">
              <label class="block text-ink-dim text-xs mb-1">{{ campo.label }}</label>
              <input v-model.number="corte[campo.key]" type="number" min="0"
                class="w-full bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </template>

          <label class="block text-ink-mute text-sm mb-1.5 mt-3">Observaciones</label>
          <textarea v-model="corte.observaciones" rows="2" placeholder="Opcional..."
            class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber resize-none mb-4" />
          <p v-if="errorModal" class="text-err text-sm mb-3">{{ errorModal }}</p>
          <div class="flex gap-3">
            <button @click="modalCerrarTurno = false; errorModal = null"
              class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
            <button @click="cerrarTurnoFn" :disabled="loadingModal || cargandoResumenCorte"
              class="flex-1 bg-err hover:bg-err/80 text-white font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
              {{ loadingModal ? 'Cerrando...' : 'Cerrar turno' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import client from '@/api/client.js'
import AlertBadge from '@/components/AlertBadge.vue'
import MenuCard from '@/components/MenuCard.vue'

const turnoActivo    = ref(null)
const ventasTurno    = ref([])
const menus          = ref([])
const cargandoTurno  = ref(true)
const loadingModal   = ref(false)
const errorModal     = ref(null)
const modalCerrarTurno = ref(false)
const verVentas      = ref(false)
const busquedaMenu   = ref('')
const cajaInicial    = ref(0)
const pagoEfectivo   = ref(0)
const pagoTarjeta    = ref(0)
const pagoTransferencia = ref(0)
const errorPago      = ref(null)

const vuelto = computed(() => {
  const total = Number(totalNuevaVenta.value)
  const metodo = nuevaVenta.metodo_pago
  if (metodo === 'efectivo') return Math.max(0, pagoEfectivo.value - total)
  if (metodo === 'mixto') {
    const falta = total - (pagoTarjeta.value || 0)
    return Math.max(0, (pagoEfectivo.value || 0) - falta)
  }
  return 0
})

const menusFiltrados = computed(() => {
  const q = busquedaMenu.value.toLowerCase().trim()
  if (!q) return menus.value
  return menus.value.filter(m => m.nombre.toLowerCase().includes(q))
})

const totalVentasHoy = computed(() => ventasTurno.value.filter(v => v.estado === 'completada').length)

const cargandoResumenCorte = ref(false)
const resumenCorte = ref({
  efectivo_esperado: 0, tarjeta_esperado: 0, transferencia_esperado: 0,
  qr_esperado: 0, total_ventas: 0, total_gastos: 0, caja_final_esperada: 0,
})

const corte = reactive({
  total_efectivo_contado: 0, total_real: 0, total_tarjeta: 0, total_transferencia: 0,
  billetes_200: 0, billetes_100: 0, billetes_50: 0, billetes_20: 0, billetes_10: 0,
  monedas_total: 0, observaciones: '',
})
const camposCorte = [
  { key: 'billetes_200', label: 'Billetes Bs. 200' },
  { key: 'billetes_100', label: 'Billetes Bs. 100' },
  { key: 'billetes_50',  label: 'Billetes Bs. 50' },
  { key: 'billetes_20',  label: 'Billetes Bs. 20' },
  { key: 'billetes_10',  label: 'Billetes Bs. 10' },
  { key: 'monedas_total', label: 'Monedas (Bs.)' },
]

const nuevaVenta = reactive({ metodo_pago: 'efectivo', items: [] })

const busquedaCliente    = ref('')
const resultadosClientes = ref([])
const buscandoClientes   = ref(false)
const clienteSeleccionado = ref(null)
const mostrarRegistroCliente = ref(false)
const guardandoCliente   = ref(false)
const nuevoCliente       = reactive({ nombre: '', email: '', telefono: '' })
let timeoutBusquedaCliente = null

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

function buscarClientes() {
  clearTimeout(timeoutBusquedaCliente)
  if (!busquedaCliente.value.trim()) { resultadosClientes.value = []; return }
  timeoutBusquedaCliente = setTimeout(async () => {
    buscandoClientes.value = true
    try {
      const { data } = await client.get('/clientes', { params: { search: busquedaCliente.value } })
      resultadosClientes.value = data.data ?? []
    } catch { resultadosClientes.value = [] }
    finally { buscandoClientes.value = false }
  }, 300)
}

function seleccionarCliente(c) {
  clienteSeleccionado.value = c
  busquedaCliente.value = ''
  resultadosClientes.value = []
  mostrarRegistroCliente.value = false
}

function quitarCliente() {
  clienteSeleccionado.value = null
  busquedaCliente.value = ''
  resultadosClientes.value = []
}

async function crearCliente() {
  if (!nuevoCliente.nombre.trim()) return
  guardandoCliente.value = true
  try {
    const { data } = await client.post('/clientes', {
      nombre:   nuevoCliente.nombre.trim().toUpperCase(),
      email:    nuevoCliente.email.trim() || null,
      telefono: nuevoCliente.telefono.trim() || null,
    })
    const creado = data.data ?? data
    clienteSeleccionado.value = creado
    mostrarRegistroCliente.value = false
    nuevoCliente.nombre = ''; nuevoCliente.email = ''; nuevoCliente.telefono = ''
    busquedaCliente.value = ''
    resultadosClientes.value = []
  } catch (e) {
    errorModal.value = e.response?.data?.message ?? 'Error al registrar cliente.'
  } finally { guardandoCliente.value = false }
}

watch(() => nuevaVenta.metodo_pago, () => {
  pagoEfectivo.value = 0; pagoTarjeta.value = 0; pagoTransferencia.value = 0; errorPago.value = null
})

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
    cajaInicial.value = 0
    await cargarTurnoActivo()
  } catch (e) { errorModal.value = e.response?.data?.message ?? 'Error al abrir turno.' }
  finally { loadingModal.value = false }
}

watch(modalCerrarTurno, async (abierto) => {
  if (!abierto || !turnoActivo.value) return
  cargandoResumenCorte.value = true
  corte.total_efectivo_contado = 0
  corte.total_real = 0
  corte.total_tarjeta = 0
  corte.total_transferencia = 0
  corte.billetes_200 = 0; corte.billetes_100 = 0; corte.billetes_50 = 0
  corte.billetes_20 = 0; corte.billetes_10 = 0; corte.monedas_total = 0
  corte.observaciones = ''
  try {
    const { data } = await client.get(`/turnos/${turnoActivo.value.id}/resumen-cierre`)
    resumenCorte.value = data.data ?? resumenCorte.value
    corte.total_efectivo_contado = resumenCorte.value.efectivo_esperado
    corte.total_tarjeta = resumenCorte.value.tarjeta_esperado
    corte.total_transferencia = resumenCorte.value.transferencia_esperado
    corte.total_real = resumenCorte.value.caja_final_esperada
  } catch {
    resumenCorte.value = { efectivo_esperado: 0, tarjeta_esperado: 0, transferencia_esperado: 0, qr_esperado: 0, total_ventas: 0, total_gastos: 0, caja_final_esperada: 0 }
  } finally { cargandoResumenCorte.value = false }
})

async function cerrarTurnoFn() {
  if (!turnoActivo.value) return
  loadingModal.value = true; errorModal.value = null
  try {
    await client.post(`/turnos/${turnoActivo.value.id}/cerrar`, { ...corte })
    turnoActivo.value = null; ventasTurno.value = []
  } catch (e) { errorModal.value = e.response?.data?.message ?? 'Error al cerrar turno.' }
  finally { loadingModal.value = false }
}

async function confirmarVenta() {
  loadingModal.value = true; errorModal.value = null; errorPago.value = null
  const total = Number(totalNuevaVenta.value)
  const metodo = nuevaVenta.metodo_pago

  if (metodo === 'efectivo' && pagoEfectivo.value < total) {
    errorPago.value = 'El monto recibido es menor al total'
    loadingModal.value = false; return
  }
  if (metodo === 'mixto') {
    const suma = (pagoEfectivo.value || 0) + (pagoTarjeta.value || 0)
    if (suma < total) {
      errorPago.value = 'La suma de los pagos no cubre el total'
      loadingModal.value = false; return
    }
  }

  try {
    await client.post('/ventas', {
      turno_id:         turnoActivo.value.id,
      metodo_pago:      nuevaVenta.metodo_pago,
      cliente_id:       clienteSeleccionado.value?.id ?? null,
      items:            nuevaVenta.items.map(i => ({ tipo: i.tipo, id: i.id, cantidad: i.cantidad, precio_unitario: i.precio_unitario })),
      pago_efectivo:    metodo === 'efectivo' || metodo === 'mixto' ? pagoEfectivo.value || null : null,
      pago_tarjeta:     metodo === 'tarjeta' || metodo === 'mixto' ? (metodo === 'tarjeta' ? total : pagoTarjeta.value || null) : null,
      pago_transferencia: metodo === 'transferencia' ? total : null,
      pago_qr:          metodo === 'qr' ? total : null,
    })
    nuevaVenta.items = []
    clienteSeleccionado.value = null
    pagoEfectivo.value = 0
    pagoTarjeta.value = 0
    pagoTransferencia.value = 0
    await cargarVentasTurno()
  } catch (e) { errorModal.value = e.response?.data?.message ?? 'Error al registrar la venta.' }
  finally { loadingModal.value = false }
}
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: transform 0.25s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
