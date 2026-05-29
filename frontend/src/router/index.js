import { createRouter, createWebHistory } from 'vue-router'
import { h } from 'vue'

const Placeholder = { render: () => h('div', { style: 'display:none' }) }

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: Placeholder }
  ]
})

export default router
