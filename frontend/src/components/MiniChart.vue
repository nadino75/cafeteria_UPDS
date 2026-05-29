<template>
  <canvas ref="canvasRef" class="w-full" style="height: 48px;" />
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
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
let chart = null

function buildChart() {
  if (!canvasRef.value) return
  if (chart) chart.destroy()
  chart = new Chart(canvasRef.value, {
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
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: { x: { display: false }, y: { display: false } },
      animation: { duration: 400 },
    },
  })
}

onMounted(buildChart)
watch(() => props.datos, buildChart, { deep: true })
onBeforeUnmount(() => { if (chart) chart.destroy() })
</script>
