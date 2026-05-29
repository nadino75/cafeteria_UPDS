<template>
  <div class="min-h-screen bg-surface">

    <AppSidebar
      :collapsed="sidebarCollapsed"
      :open="sidebarOpen"
      :is-mobile="isMobile"
      @toggle="sidebarCollapsed = !sidebarCollapsed"
      @close="sidebarOpen = false"
    />

    <AppHeader
      :collapsed="sidebarCollapsed"
      :is-mobile="isMobile"
      @open-sidebar="sidebarOpen = true"
    />

    <main
      class="transition-all duration-300 pt-16"
      :style="{ marginLeft: mainMargin }"
    >
      <div class="p-6">
        <RouterView />
      </div>
    </main>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppHeader  from '@/components/AppHeader.vue'

const sidebarCollapsed = ref(false)
const sidebarOpen      = ref(false)
const windowWidth      = ref(window.innerWidth)

const isMobile = computed(() => windowWidth.value < 768)

const mainMargin = computed(() => {
  if (isMobile.value) return '0px'
  return sidebarCollapsed.value ? '64px' : '256px'
})

function onResize() {
  windowWidth.value = window.innerWidth
  if (!isMobile.value) sidebarOpen.value = false
}

onMounted(() => window.addEventListener('resize', onResize))
onBeforeUnmount(() => window.removeEventListener('resize', onResize))
</script>
