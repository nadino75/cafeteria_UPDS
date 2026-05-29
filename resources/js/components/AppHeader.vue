<template>
  <header
    class="fixed top-0 right-0 z-10 flex items-center justify-between px-6 bg-base border-b border-edge transition-all duration-300"
    style="height: 64px;"
    :style="{ left: headerLeft }"
  >
    <!-- Izquierda: hamburguesa (móvil) + breadcrumb -->
    <div class="flex items-center gap-4">
      <button
        v-if="isMobile"
        @click="$emit('open-sidebar')"
        class="text-ink-mute hover:text-ink transition-colors"
        aria-label="Abrir menú"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
        </svg>
      </button>
      <slot name="breadcrumb">
        <span class="text-ink-mute text-sm">Dashboard</span>
      </slot>
    </div>

    <!-- Derecha: rol badge + nombre + logout -->
    <div class="flex items-center gap-4">
      <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber/10 text-amber border border-amber/20">
        {{ auth.rol }}
      </span>
      <span class="hidden md:block text-ink-mute text-sm truncate max-w-[160px]">
        {{ auth.nombreCompleto }}
      </span>
      <button
        @click="handleLogout"
        class="flex items-center gap-2 text-ink-mute hover:text-err transition-colors text-sm"
        title="Cerrar sesión"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
        </svg>
        <span class="hidden sm:block">Salir</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const props = defineProps({
  collapsed: { type: Boolean, default: false },
  isMobile:  { type: Boolean, default: false },
})
defineEmits(['open-sidebar'])

const auth   = useAuthStore()
const router = useRouter()

const headerLeft = computed(() => {
  if (props.isMobile) return '0px'
  return props.collapsed ? '64px' : '256px'
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
