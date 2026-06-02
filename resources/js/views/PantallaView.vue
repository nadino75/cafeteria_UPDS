<template>
  <div class="pantalla" ref="pantallaRef">
    <div v-if="contenidos.length > 0" class="contenido-wrapper">
      <video
        v-if="contenidos[indiceActual]?.tipo === 'video'"
        :key="'v-' + indiceActual + '-' + Date.now()"
        :src="contenidos[indiceActual].archivo_url"
        autoplay
        muted
        playsinline
        class="contenido"
        @ended="siguiente"
        @error="siguiente"
      />
      <img
        v-else
        :key="'i-' + indiceActual + '-' + Date.now()"
        :src="contenidos[indiceActual].archivo_url"
        class="contenido"
        @load="iniciarTimer(contenidos[indiceActual].duracion_segundos)"
        @error="siguiente"
      />
    </div>

    <div v-else class="sin-contenido">
      <p class="texto-espera">Esperando contenido...</p>
    </div>

    <div class="reloj">
      {{ horaActual }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const API_POLLING = 60000

const contenidos = ref([])
const indiceActual = ref(0)
let timer = null
let pollingInterval = null
let relojInterval = null
const horaActual = ref('')
const pantallaRef = ref(null)

function actualizarReloj() {
  const d = new Date()
  const offset = -4 * 60
  const local = new Date(d.getTime() + (d.getTimezoneOffset() + offset) * 60000)
  horaActual.value = local.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
}

async function fetchContenidos() {
  try {
    const res = await fetch('/api/pantalla/contenido')
    if (res.ok) {
      const data = await res.json()
      if (data.length > 0) {
        contenidos.value = data
      }
    }
  } catch (e) {
    console.error('Error fetching pantalla content:', e)
  }
}

function iniciarTimer(segundos) {
  if (timer) clearTimeout(timer)
  timer = setTimeout(() => siguiente(), segundos * 1000)
}

function siguiente() {
  if (timer) clearTimeout(timer)
  if (contenidos.value.length === 0) return
  indiceActual.value = (indiceActual.value + 1) % contenidos.value.length
}

function entrarFullscreen() {
  const el = pantallaRef.value
  if (el && !document.fullscreenElement) {
    el.requestFullscreen?.()
  }
}

onMounted(async () => {
  await fetchContenidos()
  entrarFullscreen()
  pollingInterval = setInterval(fetchContenidos, API_POLLING)
  relojInterval = setInterval(actualizarReloj, 1000)
  actualizarReloj()
})

onUnmounted(() => {
  if (timer) clearTimeout(timer)
  if (pollingInterval) clearInterval(pollingInterval)
  if (relojInterval) clearInterval(relojInterval)
})
</script>

<style scoped>
.pantalla {
  position: fixed;
  inset: 0;
  background: #000;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  z-index: 9999;
}

.contenido-wrapper {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.contenido {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.sin-contenido {
  color: #666;
  font-size: 2rem;
  font-family: sans-serif;
}

.reloj {
  position: fixed;
  bottom: 20px;
  right: 24px;
  color: rgba(255,255,255,0.5);
  font-size: 1.2rem;
  font-family: monospace;
  z-index: 10;
}

.texto-espera {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 1; }
}
</style>
