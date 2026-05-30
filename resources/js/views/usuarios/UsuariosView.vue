<template>
  <div class="space-y-6">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Usuarios</h1>
        <p class="text-ink-mute text-sm mt-1">Gestión de usuarios del sistema</p>
      </div>
      <button @click="abrirModal()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nuevo usuario
      </button>
    </div>

    <!-- Filtro -->
    <div class="flex flex-col sm:flex-row gap-3">
      <input v-model="filtro" type="text" placeholder="Buscar por nombre o email..."
        class="w-full sm:w-72 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
    </div>

    <!-- Tabla -->
    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Nombre Completo</th>
              <th class="text-left px-5 py-3">Email</th>
              <th class="text-left px-5 py-3">Rol</th>
              <th class="text-center px-5 py-3">Activo</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="usuariosFiltrados.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-ink-mute">Sin usuarios registrados</td>
            </tr>
            <tr v-for="u in usuariosFiltrados" :key="u.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-medium">{{ u.nombre_completo }}</td>
              <td class="px-5 py-3 font-mono text-ink-dim text-xs">{{ u.email }}</td>
              <td class="px-5 py-3">
                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-elevated text-ink-mute border border-edge">
                  {{ u.rol?.nombre || '—' }}
                </span>
              </td>
              <td class="px-5 py-3 text-center">
                <span v-if="u.activo" class="inline-flex items-center gap-1 text-ok text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-ok" /> Sí
                </span>
                <span v-else class="inline-flex items-center gap-1 text-err text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-err" /> No
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button @click="abrirModal(u)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">
                    Editar
                  </button>
                  <span class="text-edge-lit">|</span>
                  <button @click="desactivar(u)"
                    class="text-ink-mute hover:text-err text-xs font-medium transition-colors">
                    Desactivar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  <!-- Modal Crear / Editar -->
  <Teleport to="body">
    <div v-if="modalAbierto" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6">
        <h3 class="font-display text-xl text-ink font-medium mb-4">
          {{ modoEdicion ? 'Editar usuario' : 'Nuevo usuario' }}
        </h3>

        <div class="space-y-3">
          <div>
            <label class="block text-ink-mute text-sm mb-1">Nombre completo *</label>
            <input v-model="form.nombre_completo" type="text"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Email *</label>
            <input v-model="form.email" type="email"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Rol *</label>
            <select v-model="form.rol_id"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="" disabled>Selecciona un rol...</option>
              <option v-for="r in ROLES" :key="r.id" :value="r.id">{{ r.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">
              Contraseña
              <span v-if="modoEdicion" class="text-ink-dim font-normal"> (dejar vacío para mantener)</span>
              <span v-else class="text-err"> *</span>
            </label>
            <input v-model="form.password" type="password" minlength="8" placeholder="Mínimo 8 caracteres"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
        </div>

        <p v-if="errorForm" class="text-err text-sm mt-4">{{ errorForm }}</p>

        <div class="flex gap-3 mt-5">
          <button @click="modalAbierto = false; errorForm = null"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">
            Cancelar
          </button>
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

const ROLES = [
  { id: 1, nombre: 'Administrador' },
  { id: 2, nombre: 'Gerente' },
  { id: 3, nombre: 'Cajero' },
  { id: 4, nombre: 'Almacenista' },
  { id: 5, nombre: 'Contador' },
]

const usuarios        = ref([])
const filtro         = ref('')

const usuariosFiltrados = computed(() => {
  const q = filtro.value.toLowerCase()
  return usuarios.value.filter(u =>
    u.nombre_completo.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q)
  )
})

onMounted(() => cargarUsuarios())

async function cargarUsuarios() {
  try {
    const { data } = await client.get('/usuarios')
    usuarios.value = data.data ?? []
  } catch { usuarios.value = [] }
}

const modalAbierto  = ref(false)
const modoEdicion   = ref(false)
const guardando     = ref(false)
const errorForm     = ref(null)
const form          = ref({
  nombre_completo: '',
  email: '',
  password: '',
  rol_id: '',
})

function abrirModal(usuario = null) {
  errorForm.value = null
  if (usuario) {
    modoEdicion.value = true
    form.value = {
      nombre_completo: usuario.nombre_completo,
      email: usuario.email,
      password: '',
      rol_id: usuario.rol_id,
      _id: usuario.id,
    }
  } else {
    modoEdicion.value = false
    form.value = { nombre_completo: '', email: '', password: '', rol_id: '' }
  }
  modalAbierto.value = true
}

async function guardar() {
  guardando.value = true; errorForm.value = null
  try {
    if (modoEdicion.value) {
      const payload = { ...form.value }
      if (!payload.password) delete payload.password
      await client.put(`/usuarios/${form.value._id}`, payload)
    } else {
      await client.post('/usuarios', form.value)
    }
    modalAbierto.value = false
    await cargarUsuarios()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorForm.value = Object.values(errs).flat().join('. ')
    } else {
      errorForm.value = e.response?.data?.message || 'Error al guardar el usuario.'
    }
  }
  finally { guardando.value = false }
}

async function desactivar(usuario) {
  if (!confirm(`¿Estás seguro de desactivar "${usuario.nombre_completo}"?`)) return
  try {
    await client.delete(`/usuarios/${usuario.id}`)
    await cargarUsuarios()
  } catch {
    alert('Error al desactivar el usuario.')
  }
}
</script>
