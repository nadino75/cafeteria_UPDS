<template>
  <div class="bg-card border border-edge rounded-xl p-5 flex flex-col gap-3">
    <p class="text-ink-mute text-xs uppercase tracking-widest font-medium">
      {{ label }}
    </p>
    <p class="font-mono text-3xl font-medium" :class="valueColor">
      {{ formattedValue }}
    </p>
    <div v-if="delta !== null && delta !== undefined" class="flex items-center gap-1.5">
      <span class="text-xs font-medium" :class="deltaColor">
        {{ deltaPrefix }}{{ Math.abs(delta).toFixed(1) }}%
      </span>
      <span class="text-ink-dim text-xs">vs ayer</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:    { type: String, required: true },
  value:    { type: [String, Number], required: true },
  delta:    { type: Number, default: null },
  variante: {
    type: String,
    default: 'neutral',
    validator: v => ['ok', 'err', 'warn', 'neutral'].includes(v),
  },
})

const COLORS = { ok: 'text-ok', err: 'text-err', warn: 'text-warn', neutral: 'text-ink' }

const valueColor = computed(() => COLORS[props.variante])
const deltaColor = computed(() => {
  if (props.delta === null || props.delta === undefined) return ''
  return props.delta >= 0 ? 'text-ok' : 'text-err'
})
const deltaPrefix   = computed(() => (props.delta >= 0 ? '↑ +' : '↓ '))
const formattedValue = computed(() => {
  if (typeof props.value === 'number') return props.value.toLocaleString('es-BO')
  return props.value
})
</script>
