<template>
  <div class="min-h-screen flex items-center justify-center bg-base px-4">
    <div class="w-full max-w-lg">

      <!-- Encabezado -->
      <div class="flex items-center gap-3 mb-8">
        <button
          @click="router.back()"
          class="text-ink-mute hover:text-ink transition-colors"
          aria-label="Volver"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
          </svg>
        </button>
        <div>
          <h1 class="font-display text-3xl text-ink font-medium">Nuevo usuario</h1>
          <p class="text-ink-mute text-sm">Solo el Administrador puede crear cuentas</p>
        </div>
      </div>

      <!-- Card -->
      <div class="bg-card border border-edge rounded-2xl p-8">
        <form @submit.prevent="handleSubmit" novalidate>

          <div class="mb-4">
            <label class="block text-ink-mute text-sm mb-1.5">Nombre completo</label>
            <input
              v-model="form.nombre_completo"
              type="text"
              required
              placeholder="Ej: María López"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <div class="mb-4">
            <label class="block text-ink-mute text-sm mb-1.5">Correo electrónico</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="usuario@cafeteria.upds"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <div class="mb-4">
            <label class="block text-ink-mute text-sm mb-1.5">Contraseña</label>
            <input
              v-model="form.password"
              type="password"
              required
              minlength="8"
              placeholder="Mínimo 8 caracteres"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <div class="mb-6">
            <label class="block text-ink-mute text-sm mb-1.5">Rol</label>
            <select
              v-model="form.rol_id"
              required
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink text-sm focus:outline-none focus:border-amber transition-colors"
            >
              <option value="" disabled>Selecciona un rol...</option>
              <option v-for="rol in ROLES" :key="rol.id" :value="rol.id">
                {{ rol.nombre }}
              </option>
            </select>
          </div>

          <div v-if="error" class="mb-4 px-4 py-3 bg-err/10 border border-err/30 rounded-lg">
            <p class="text-err text-sm">{{ error }}</p>
          </div>
          <div v-if="exito" class="mb-4 px-4 py-3 bg-ok/10 border border-ok/30 rounded-lg">
            <p class="text-ok text-sm">✓ Usuario creado correctamente.</p>
          </div>

          <div class="flex gap-3">
            <button
              type="button"
              @click="router.back()"
              class="flex-1 border border-edge text-ink-mute hover:text-ink hover:border-edge-lit py-3 rounded-lg transition-colors text-sm"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-3 rounded-lg transition-colors disabled:opacity-50 text-sm"
            >
              {{ loading ? 'Creando...' : 'Crear usuario' }}
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import client from '@/api/client.js'

const router  = useRouter()
const loading = ref(false)
const error   = ref(null)
const exito   = ref(false)

const ROLES = [
  { id: 2, nombre: 'Gerente' },
  { id: 3, nombre: 'Cajero' },
  { id: 4, nombre: 'Almacenista' },
  { id: 5, nombre: 'Contador' },
]

const form = reactive({
  nombre_completo: '',
  email: '',
  password: '',
  rol_id: '',
})

async function handleSubmit() {
  if (!form.nombre_completo || !form.email || !form.password || !form.rol_id) {
    error.value = 'Completa todos los campos.'
    return
  }
  if (form.password.length < 8) {
    error.value = 'La contraseña debe tener al menos 8 caracteres.'
    return
  }
  loading.value = true
  error.value   = null
  exito.value   = false
  try {
    await client.post('/usuarios', {
      nombre_completo: form.nombre_completo,
      email:           form.email,
      password:        form.password,
      rol_id:          form.rol_id,
    })
    exito.value = true
    Object.assign(form, { nombre_completo: '', email: '', password: '', rol_id: '' })
  } catch (e) {
    const msgs = e.response?.data?.errors
    error.value = msgs
      ? Object.values(msgs).flat().join(' ')
      : (e.response?.data?.message ?? 'Error al crear el usuario.')
  } finally {
    loading.value = false
  }
}
</script>
