<template>
  <div class="min-h-screen flex items-center justify-center bg-base px-4">
    <div class="w-full max-w-md">

      <!-- Marca -->
      <div class="text-center mb-10">
        <h1 class="font-display text-5xl text-amber font-semibold tracking-tight">
          Cafetería
        </h1>
        <p class="text-ink-mute text-xs tracking-[0.25em] uppercase mt-1">
          UPDS · Sistema de Gestión
        </p>
      </div>

      <!-- Card -->
      <div class="bg-card border border-edge rounded-2xl p-8 shadow-2xl">
        <h2 class="font-display text-2xl text-ink font-medium mb-6">
          Iniciar sesión
        </h2>

        <form @submit.prevent="handleLogin" novalidate>
          <!-- Email -->
          <div class="mb-4">
            <label class="block text-ink-mute text-sm mb-1.5">
              Correo electrónico
            </label>
            <input
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              placeholder="usuario@cafeteria.upds"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <!-- Contraseña -->
          <div class="mb-6">
            <label class="block text-ink-mute text-sm mb-1.5">
              Contraseña
            </label>
            <input
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              placeholder="••••••••"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-3 text-ink placeholder-ink-dim text-sm focus:outline-none focus:border-amber transition-colors"
            />
          </div>

          <!-- Error -->
          <div
            v-if="error"
            class="mb-4 px-4 py-3 bg-err/10 border border-err/30 rounded-lg"
          >
            <p class="text-err text-sm">{{ error }}</p>
          </div>

          <!-- Botón -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-amber hover:bg-amber-bright text-base font-medium py-3 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm tracking-wide"
          >
            {{ loading ? 'Ingresando...' : 'Ingresar' }}
          </button>
        </form>
      </div>

      <p class="text-center text-ink-dim text-xs mt-6">
        ¿Sin acceso? Contacta al administrador del sistema.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { DASHBOARD_BY_ROL } from '@/router/index.js'

const router = useRouter()
const auth   = useAuthStore()

const form    = reactive({ email: '', password: '' })
const loading = ref(false)
const error   = ref(null)

async function handleLogin() {
  if (!form.email || !form.password) {
    error.value = 'Completa todos los campos.'
    return
  }
  loading.value = true
  error.value   = null
  try {
    const data    = await auth.login(form.email, form.password)
    const destino = DASHBOARD_BY_ROL[data.usuario?.rol] ?? '/login'
    router.push(destino)
  } catch (e) {
    const msg = e.response?.data?.message
    error.value = msg ?? 'Error al iniciar sesión. Verifica tus credenciales.'
  } finally {
    loading.value = false
  }
}
</script>
