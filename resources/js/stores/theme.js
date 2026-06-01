import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const isDark = ref(true)

  function init() {
    const saved = localStorage.getItem('cafeteria_theme')
    if (saved === 'light' || saved === 'dark') {
      isDark.value = saved === 'dark'
    }
    apply()
  }

  function toggle() {
    isDark.value = !isDark.value
    localStorage.setItem('cafeteria_theme', isDark.value ? 'dark' : 'light')
    apply()
  }

  function apply() {
    document.documentElement.classList.toggle('light', !isDark.value)
  }

  return { isDark, init, toggle }
})
