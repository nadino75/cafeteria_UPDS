<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Proveedores</h1>
        <p class="text-ink-mute text-sm mt-1">Gestión de proveedores</p>
      </div>
      <button @click="abrirModal()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nuevo proveedor
      </button>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <input v-model="filtro" type="text" placeholder="Buscar por nombre o contacto..."
        class="w-full sm:w-72 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
    </div>

    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Nombre Empresa</th>
              <th class="text-left px-5 py-3">Contacto</th>
              <th class="text-left px-5 py-3">Email</th>
              <th class="text-left px-5 py-3">Teléfono</th>
              <th class="text-center px-5 py-3">Activo</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="proveedoresFiltrados.length === 0">
              <td colspan="6" class="px-5 py-8 text-center text-ink-mute">Sin proveedores registrados</td>
            </tr>
            <tr v-for="p in proveedoresFiltrados" :key="p.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-medium">{{ p.nombre_empresa }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ p.contacto_nombre || '—' }}</td>
              <td class="px-5 py-3 font-mono text-ink-dim text-xs">{{ p.email || '—' }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ p.telefono || '—' }}</td>
              <td class="px-5 py-3 text-center">
                <span v-if="p.activo" class="inline-flex items-center gap-1 text-ok text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-ok" /> Sí
                </span>
                <span v-else class="inline-flex items-center gap-1 text-err text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-err" /> No
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button @click="abrirModal(p)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">Editar</button>
                  <span class="text-edge-lit">|</span>
                  <button @click="desactivar(p)"
                    class="text-ink-mute hover:text-err text-xs font-medium transition-colors">Desactivar</button>
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
          {{ modoEdicion ? 'Editar proveedor' : 'Nuevo proveedor' }}
        </h3>
        <div class="space-y-3">
          <div>
            <label class="block text-ink-mute text-sm mb-1">Nombre empresa *</label>
            <input v-model="form.nombre_empresa" type="text"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Nombre contacto</label>
            <input v-model="form.contacto_nombre" type="text"
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
          <div>
            <label class="block text-ink-mute text-sm mb-1">Dirección</label>
            <textarea v-model="form.direccion" rows="2"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber"></textarea>
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

const proveedores          = ref([])
const filtro              = ref('')

const proveedoresFiltrados = computed(() => {
  const q = filtro.value.toLowerCase()
  return proveedores.value.filter(p =>
    p.nombre_empresa.toLowerCase().includes(q) ||
    (p.contacto_nombre || '').toLowerCase().includes(q)
  )
})

onMounted(() => cargarProveedores())

async function cargarProveedores() {
  try {
    const { data } = await client.get('/proveedores')
    proveedores.value = data.data ?? []
  } catch { proveedores.value = [] }
}

const modalAbierto = ref(false)
const modoEdicion  = ref(false)
const guardando    = ref(false)
const errorForm    = ref(null)
const form         = ref({ nombre_empresa: '', contacto_nombre: '', email: '', telefono: '', direccion: '' })

function abrirModal(proveedor = null) {
  errorForm.value = null
  if (proveedor) {
    modoEdicion.value = true
    form.value = { nombre_empresa: proveedor.nombre_empresa, contacto_nombre: proveedor.contacto_nombre || '', email: proveedor.email || '', telefono: proveedor.telefono || '', direccion: proveedor.direccion || '', _id: proveedor.id }
  } else {
    modoEdicion.value = false
    form.value = { nombre_empresa: '', contacto_nombre: '', email: '', telefono: '', direccion: '' }
  }
  modalAbierto.value = true
}

async function guardar() {
  guardando.value = true; errorForm.value = null
  try {
    if (modoEdicion.value) {
      await client.put(`/proveedores/${form.value._id}`, form.value)
    } else {
      await client.post('/proveedores', form.value)
    }
    modalAbierto.value = false
    await cargarProveedores()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorForm.value = Object.values(errs).flat().join('. ')
    } else {
      errorForm.value = e.response?.data?.message || 'Error al guardar el proveedor.'
    }
  } finally { guardando.value = false }
}

async function desactivar(proveedor) {
  if (!confirm(`¿Desactivar proveedor "${proveedor.nombre_empresa}"?`)) return
  try {
    await client.delete(`/proveedores/${proveedor.id}`)
    await cargarProveedores()
  } catch {
    alert('Error al desactivar el proveedor.')
  }
}
</script>
