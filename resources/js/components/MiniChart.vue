<template>
  <div ref="wrapperRef" style="width:100%;height:100%;min-height:48px;position:relative;">
    <canvas ref="canvasRef" style="width:100%;height:100%;display:block;" />
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
  const w = wrapperRef.value.clientWidth
  const h = wrapperRef.value.clientHeight
  if (w < 10 || h < 10) return

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
      devicePixelRatio: 1,
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: { x: { display: false }, y: { display: false } },
      animation: { duration: 300 },
    },
  })
}

function tryBuild(attempts = 0) {
  if (attempts > 20) return
  if (!wrapperRef.value) return
  const w = wrapperRef.value.clientWidth
  const h = wrapperRef.value.clientHeight
  if (w >= 10 && h >= 10) {
    buildChart()
  } else {
    setTimeout(() => tryBuild(attempts + 1), 80)
  }
}

onMounted(() => {
  nextTick(() => tryBuild(0))
})

watch(() => props.datos, () => {
  nextTick(() => tryBuild(0))
}, { deep: true })

onBeforeUnmount(() => {
  if (chart) { chart.destroy(); chart = null }
})
</script>
