<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Clientes</h1>
        <p class="text-ink-mute text-sm mt-1">Gestión de clientes</p>
      </div>
      <button @click="abrirModal()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nuevo cliente
      </button>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <input v-model="filtro" type="text" placeholder="Buscar por nombre, email o teléfono..."
        class="w-full sm:w-80 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
    </div>

    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Nombre</th>
              <th class="text-left px-5 py-3">Email</th>
              <th class="text-left px-5 py-3">Teléfono</th>
              <th class="text-right px-5 py-3">Puntos Acum.</th>
              <th class="text-right px-5 py-3">Puntos Canj.</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="clientesFiltrados.length === 0">
              <td colspan="6" class="px-5 py-8 text-center text-ink-mute">Sin clientes registrados</td>
            </tr>
            <tr v-for="c in clientesFiltrados" :key="c.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-medium">{{ c.nombre }}</td>
              <td class="px-5 py-3 font-mono text-ink-dim text-xs">{{ c.email || '—' }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ c.telefono || '—' }}</td>
              <td class="px-5 py-3 text-right font-mono text-ink">{{ c.puntos_acumulados ?? 0 }}</td>
              <td class="px-5 py-3 text-right font-mono text-ink-dim">{{ c.puntos_canjeados ?? 0 }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center">
                  <button @click="abrirModal(c)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">Editar</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  <Teleport to="body">
    <div v-if="modalAbierto" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6">
        <h3 class="font-display text-xl text-ink font-medium mb-4">
          {{ modoEdicion ? 'Editar cliente' : 'Nuevo cliente' }}
        </h3>
        <div class="space-y-3">
          <div>
            <label class="block text-ink-mute text-sm mb-1">Nombre *</label>
            <input v-model="form.nombre" type="text"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Email</label>
            <input v-model="form.email" type="email"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Teléfono</label>
            <input v-model="form.telefono" type="text" maxlength="20"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
        </div>
        <p v-if="errorForm" class="text-err text-sm mt-4">{{ errorForm }}</p>
        <div class="flex gap-3 mt-5">
          <button @click="modalAbierto = false; errorForm = null"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
          <button @click="guardar" :disabled="guardando"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
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

const clientes          = ref([])
const filtro           = ref('')

const clientesFiltrados = computed(() => {
  const q = filtro.value.toLowerCase()
  return clientes.value.filter(c =>
    c.nombre.toLowerCase().includes(q) ||
    (c.email || '').toLowerCase().includes(q) ||
    (c.telefono || '').includes(q)
  )
})

onMounted(() => cargarClientes())

async function cargarClientes() {
  try {
    const { data } = await client.get('/clientes')
    clientes.value = data.data ?? []
  } catch { clientes.value = [] }
}

const modalAbierto = ref(false)
const modoEdicion  = ref(false)
const guardando    = ref(false)
const errorForm    = ref(null)
const form         = ref({ nombre: '', email: '', telefono: '' })

function abrirModal(cliente = null) {
  errorForm.value = null
  if (cliente) {
    modoEdicion.value = true
    form.value = { nombre: cliente.nombre, email: cliente.email || '', telefono: cliente.telefono || '', _id: cliente.id }
  } else {
    modoEdicion.value = false
    form.value = { nombre: '', email: '', telefono: '' }
  }
  modalAbierto.value = true
}

async function guardar() {
  guardando.value = true; errorForm.value = null
  try {
    if (modoEdicion.value) {
      await client.put(`/clientes/${form.value._id}`, form.value)
    } else {
      await client.post('/clientes', form.value)
    }
    modalAbierto.value = false
    await cargarClientes()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorForm.value = Object.values(errs).flat().join('. ')
    } else {
      errorForm.value = e.response?.data?.message || 'Error al guardar el cliente.'
    }
  } finally { guardando.value = false }
}
</script>
