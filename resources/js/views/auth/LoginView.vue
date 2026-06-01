<template>
  <div class="login-page">
    <div class="login-overlay">
      <div class="login-container">
        <router-link to="/"><img :src="loginImg" alt="Cafetería UPDS"></router-link>
        <h2>Acceso exclusivo</h2>
        <p>Ingresa con tu correo y contraseña para gestionar el sistema</p>

        <form @submit.prevent="handleLogin" novalidate>
          <div class="input-group">
            <label>Correo electrónico</label>
            <input
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              placeholder="tu@upds.com"
            />
          </div>

          <div class="input-group">
            <label>Contraseña</label>
            <input
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              placeholder="********"
            />
          </div>

          <div v-if="error" class="error-message">{{ error }}</div>

          <button type="submit" :disabled="loading" class="login-btn">
            {{ loading ? 'Ingresando...' : 'Ingresar al sistema' }}
          </button>
        </form>

        <p class="register-link">
          ¿Sin acceso? Contacta al administrador del sistema.
        </p>
      </div>
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

const loginImg = '/assets/img/CAFETERIA.svg'

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

<style scoped>
@font-face {
  font-family: 'Room-205';
  font-display: swap;
  src: url('https://onyxcoffeelab.com/cdn/shop/t/31/assets/Room-205.woff2?v=18721117088091669681705351138') format('woff2');
}

.login-page {
  min-height: 100vh;
  background: var(--color-base);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Montserrat', sans-serif;
}

.login-overlay {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.login-container {
  background: var(--color-card);
  border: 1px solid var(--color-edge);
  border-radius: 32px;
  padding: 2.5rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 25px 45px rgba(0,0,0,0.3);
  text-align: center;
  animation: fadeInUp 0.6s ease;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.login-container img {
  width: 120px;
  margin-bottom: 1rem;
  filter: drop-shadow(0 0 6px #f5c542);
}

.login-container h2 {
  color: var(--color-amber);
  font-family: 'Room-205', serif;
  font-size: 1.8rem;
  margin-bottom: 0.5rem;
  letter-spacing: 1px;
}

.login-container > p {
  color: var(--color-ink-mute);
  margin-bottom: 2rem;
  font-size: 0.85rem;
}

.input-group {
  margin-bottom: 1.2rem;
  text-align: left;
}

.input-group label {
  display: block;
  color: var(--color-ink);
  font-size: 0.8rem;
  margin-bottom: 0.4rem;
  font-weight: 500;
}

.input-group input {
  width: 100%;
  padding: 12px 15px;
  background: var(--color-elevated);
  border: 1px solid var(--color-edge);
  border-radius: 40px;
  color: var(--color-ink);
  font-size: 1rem;
  outline: none;
  transition: all 0.2s;
  font-family: 'Montserrat', sans-serif;
}

.input-group input::placeholder {
  color: var(--color-ink-dim);
}

.input-group input:focus {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-amber) 30%, transparent);
}

.login-btn {
  width: 100%;
  background: var(--color-amber);
  color: var(--color-base);
  border: none;
  padding: 12px;
  border-radius: 40px;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 0.5rem;
  font-family: 'Montserrat', sans-serif;
}

.login-btn:hover {
  background: #ffd966;
  transform: scale(1.02);
}

.login-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.error-message {
  color: var(--color-err);
  font-size: 0.8rem;
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
  text-align: left;
}

.register-link {
  color: var(--color-ink-dim);
  font-size: 0.75rem;
  margin-top: 1.5rem;
}
</style>
