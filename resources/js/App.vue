<template>
  <RouterView />
</template>

<script setup>
import { onMounted } from 'vue'
import { useThemeStore } from '@/stores/theme.js'

const theme = useThemeStore()
onMounted(() => theme.init())
</script>
