<template>
  <span v-if="valor != null" class="inline-flex items-center gap-0.5 text-xs font-mono"
    :class="clase">
    <svg v-if="valor > 0" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
    </svg>
    <svg v-else-if="valor < 0" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
    <span v-else class="w-3 h-3 inline-flex items-center justify-center">—</span>
    {{ Math.abs(valor).toFixed(1) }}%
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  valor: { type: Number, default: null },
})

const clase = computed(() => {
  if (props.valor == null) return 'text-ink-dim'
  if (props.valor > 0) return 'text-ok'
  if (props.valor < 0) return 'text-err'
  return 'text-ink-dim'
})
</script>
