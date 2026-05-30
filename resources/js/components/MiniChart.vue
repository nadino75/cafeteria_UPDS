<template>
  <div ref="wrapperRef" class="w-full h-full min-h-[48px]">
    <canvas ref="canvasRef" class="w-full h-full" />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import {
  Chart,
  LineController, BarController,
  CategoryScale, LinearScale,
  PointElement, LineElement, BarElement,
  Filler,
} from 'chart.js'

Chart.register(
  LineController, BarController,
  CategoryScale, LinearScale,
  PointElement, LineElement, BarElement,
  Filler,
)

const props = defineProps({
  datos: { type: Array, required: true },
  tipo:  { type: String, default: 'line', validator: v => ['line', 'bar'].includes(v) },
  color: { type: String, default: '#D4821E' },
})

const canvasRef = ref(null)
const wrapperRef = ref(null)
let chart = null

function buildChart() {
  if (!canvasRef.value || !wrapperRef.value) return
  if (chart) { chart.destroy(); chart = null }

  const ctx = canvasRef.value.getContext('2d')
  if (!ctx) return

  chart = new Chart(ctx, {
    type: props.tipo,
    data: {
      labels: props.datos.map((_, i) => i),
      datasets: [{
        data: props.datos,
        borderColor: props.color,
        backgroundColor: props.tipo === 'line' ? `${props.color}22` : `${props.color}88`,
        fill: props.tipo === 'line',
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 0,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      resizeObserver: true,
      devicePixelRatio: 1,
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: { x: { display: false }, y: { display: false } },
      animation: { duration: 300 },
    },
  })
}

onMounted(() => nextTick(buildChart))

watch(() => props.datos, () => nextTick(buildChart), { deep: true })

onBeforeUnmount(() => { if (chart) { chart.destroy(); chart = null } })
</script>
