<template>
  <button @click="$emit('click')"
    class="group flex flex-col bg-card border border-edge hover:border-amber/40 hover:bg-amber/[0.03] rounded-xl overflow-hidden transition-all duration-200 cursor-pointer text-left">
    <div class="w-full aspect-[4/3] bg-elevated overflow-hidden relative">
      <img v-if="menu.imagen_url" :src="menu.imagen_url" :alt="menu.nombre"
        class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300"
        loading="lazy" @error="onImgError" />
      <div v-else class="w-full h-full flex items-center justify-center text-ink-dim/30">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    </div>
    <div class="p-3 flex flex-col gap-0.5">
      <p class="text-ink text-sm font-medium leading-tight truncate">{{ menu.nombre }}</p>
      <div class="flex items-center justify-between">
        <span class="text-amber font-mono text-xs">Bs. {{ Number(menu.precio_venta).toFixed(2) }}</span>
        <span v-if="porciones !== null"
          class="text-[10px] font-bold px-1.5 py-0.5 rounded leading-tight"
          :class="porciones > 0 ? 'bg-ok/15 text-ok' : 'bg-err/15 text-err'">
          {{ porciones }}
        </span>
      </div>
    </div>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ menu: { type: Object, required: true } })
defineEmits(['click'])

const porciones = computed(() => {
  if (props.menu.tipo !== 'preparado' || !props.menu.ingredientes?.length) return null
  return Math.min(...props.menu.ingredientes.map(i =>
    Math.floor((i.producto?.stock_actual ?? 0) / i.cantidad)
  ))
})

function onImgError(e) {
  e.target.style.display = 'none'
  e.target.parentElement.innerHTML = `
    <div class="w-full h-full flex items-center justify-center text-ink-dim/30">
      <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </div>`
}
</script>
