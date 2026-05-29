<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
    :class="clases"
  >
    {{ texto }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  texto:     { type: String, required: true },
  severidad: {
    type: String,
    default: 'info',
    validator: v => ['ok', 'warn', 'err', 'info'].includes(v),
  },
})

const CLASES = {
  ok:   'bg-ok/10   text-ok   border-ok/30',
  warn: 'bg-warn/10 text-warn border-warn/30',
  err:  'bg-err/10  text-err  border-err/30',
  info: 'bg-info/10 text-info border-info/30',
}

const clases = computed(() => CLASES[props.severidad])
</script>
