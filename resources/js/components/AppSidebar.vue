<template>
  <!-- Overlay móvil -->
  <div
    v-if="open && isMobile"
    class="fixed inset-0 bg-black/60 z-20"
    @click="$emit('close')"
  />

  <!-- Sidebar -->
  <aside
    class="fixed top-0 left-0 h-full z-30 flex flex-col bg-base border-r border-edge transition-all duration-300"
    :class="[
      isMobile
        ? open ? 'w-64 translate-x-0' : 'w-64 -translate-x-full'
        : collapsed ? 'w-16' : 'w-64'
    ]"
  >
    <!-- Logo -->
    <div class="flex items-center gap-3 px-4 py-5 border-b border-edge shrink-0">
      <div class="w-8 h-8 rounded-lg bg-amber flex items-center justify-center shrink-0">
        <span class="text-base text-sm font-bold">C</span>
      </div>
      <span v-if="!collapsed || isMobile" class="font-display text-xl text-ink font-semibold truncate">
        Cafetería
      </span>
    </div>

    <!-- Nav items -->
    <nav class="flex-1 overflow-y-auto py-3 px-2">
      <template v-for="item in navItems" :key="item.label">
        <!-- Separador de grupo -->
        <p
          v-if="item.grupo && (!collapsed || isMobile)"
          class="text-ink-dim text-[10px] uppercase tracking-widest px-3 pt-4 pb-1"
        >
          {{ item.grupo }}
        </p>

        <!-- Link activo -->
        <RouterLink
          v-if="!item.disabled"
          :to="item.ruta"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-0.5 text-ink-mute hover:text-ink hover:bg-elevated transition-colors"
          active-class="bg-amber/10 !text-amber border border-amber/20"
          :title="collapsed && !isMobile ? item.label : undefined"
        >
          <span class="w-5 h-5 shrink-0 flex items-center justify-center" v-html="item.icono" />
          <span v-if="!collapsed || isMobile" class="text-sm font-medium truncate">
            {{ item.label }}
          </span>
        </RouterLink>

        <!-- Disabled -->
        <div
          v-else
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-0.5 text-ink-dim cursor-not-allowed opacity-40"
          :title="collapsed && !isMobile ? item.label : `${item.label} — Próximamente`"
        >
          <span class="w-5 h-5 shrink-0 flex items-center justify-center" v-html="item.icono" />
          <span v-if="!collapsed || isMobile" class="text-sm font-medium truncate">{{ item.label }}</span>
          <span v-if="!collapsed || isMobile" class="ml-auto text-[10px] border border-edge-lit rounded px-1 py-0.5">Pronto</span>
        </div>
      </template>
    </nav>

    <!-- Toggle collapse (solo desktop) -->
    <button
      v-if="!isMobile"
      @click="$emit('toggle')"
      class="flex items-center justify-center py-4 border-t border-edge text-ink-dim hover:text-ink transition-colors shrink-0"
      :title="collapsed ? 'Expandir' : 'Colapsar'"
    >
      <svg
        class="w-4 h-4 transition-transform duration-300"
        :class="collapsed ? '' : 'rotate-180'"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
      </svg>
    </button>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth.js'

defineProps({
  collapsed: { type: Boolean, default: false },
  open:      { type: Boolean, default: false },
  isMobile:  { type: Boolean, default: false },
})
defineEmits(['toggle', 'close'])

const auth = useAuthStore()

// ── SVG icons (Heroicons outline) ────────────────────────────────────────────
const I = {
  grid:     `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>`,
  users:    `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>`,
  tag:      `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>`,
  folder:   `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v8.25A2.25 2.25 0 0 0 4.5 16.5h15a2.25 2.25 0 0 0 2.25-2.25V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>`,
  menu:     `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>`,
  box:      `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>`,
  cart:     `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>`,
  bag:      `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></svg>`,
  clock:    `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`,
  truck:    `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>`,
  banknote: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>`,
  calc:     `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Z" /></svg>`,
  chart:    `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>`,
  shield:   `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>`,
  cog:      `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>`,
}

const NAV_CONFIG = {
  Administrador: [
    { label: 'Dashboard',       ruta: '/dashboard/admin',  icono: I.grid },
    { grupo: 'Usuarios',   label: 'Usuarios',      ruta: '/usuarios',     icono: I.users },
    { grupo: 'Catálogos',  label: 'Productos',     ruta: '/productos',    icono: I.tag },
    {                      label: 'Categorías',    ruta: '/categorias',   icono: I.folder },
    {                      label: 'Menús',          ruta: '/menus',        icono: I.menu },
    { grupo: 'Operaciones',label: 'Inventario',    ruta: '/inventario',   icono: I.box },
    {                      label: 'Ventas',         ruta: '/ventas',       icono: I.cart },
    {                      label: 'Compras',        ruta: '/compras',      icono: I.bag },
    {                      label: 'Turnos',         ruta: '/turnos',       icono: I.clock },
    {                      label: 'Clientes',       ruta: '/clientes',     icono: I.users },
    {                      label: 'Gastos',         ruta: '/gastos',       icono: I.banknote },
    { grupo: 'Finanzas',   label: 'Contabilidad',  ruta: '/contabilidad', icono: I.calc },
    {                      label: 'Reportes',       ruta: '/reportes',     icono: I.chart },
    { grupo: 'Sistema',    label: 'Roles & Permisos', ruta: '/roles',     icono: I.shield, disabled: true },
    {                      label: 'Configuración',  ruta: '/config',       icono: I.cog,    disabled: true },
  ],
  Gerente: [
    { label: 'Dashboard',      ruta: '/dashboard/gerente', icono: I.grid },
    { grupo: 'Usuarios',  label: 'Usuarios',     ruta: '/usuarios',     icono: I.users },
    { grupo: 'Catálogos', label: 'Productos',    ruta: '/productos',    icono: I.tag },
    {                     label: 'Categorías',   ruta: '/categorias',   icono: I.folder },
    {                     label: 'Menús',         ruta: '/menus',        icono: I.menu },
    { grupo: 'Operaciones', label: 'Inventario', ruta: '/inventario',   icono: I.box },
    {                     label: 'Ventas',        ruta: '/ventas',       icono: I.cart },
    {                     label: 'Compras',       ruta: '/compras',      icono: I.bag },
    {                     label: 'Turnos',        ruta: '/turnos',       icono: I.clock },
    {                     label: 'Clientes',      ruta: '/clientes',     icono: I.users },
    {                     label: 'Gastos',        ruta: '/gastos',       icono: I.banknote },
    { grupo: 'Finanzas',  label: 'Contabilidad', ruta: '/contabilidad', icono: I.calc },
    {                     label: 'Reportes',      ruta: '/reportes',     icono: I.chart },
  ],
  Cajero: [
    { label: 'Dashboard', ruta: '/dashboard/cajero', icono: I.grid },
    { grupo: 'Mi turno', label: 'Ventas',   ruta: '/ventas',   icono: I.cart },
    {                    label: 'Turnos',   ruta: '/turnos',   icono: I.clock },
    {                    label: 'Clientes', ruta: '/clientes', icono: I.users },
    { grupo: 'Consulta', label: 'Menús',   ruta: '/menus',    icono: I.menu },
  ],
  Almacenista: [
    { label: 'Dashboard', ruta: '/dashboard/almacenista', icono: I.grid },
    { grupo: 'Inventario', label: 'Inventario',  ruta: '/inventario',  icono: I.box },
    {                      label: 'Categorías',  ruta: '/categorias',  icono: I.folder },
    {                      label: 'Productos',   ruta: '/productos',   icono: I.tag },
    { grupo: 'Compras',    label: 'Compras',     ruta: '/compras',     icono: I.bag },
    {                      label: 'Proveedores', ruta: '/proveedores', icono: I.truck },
  ],
  Contador: [
    { label: 'Dashboard',    ruta: '/dashboard/contador', icono: I.grid },
    { grupo: 'Finanzas', label: 'Contabilidad', ruta: '/contabilidad', icono: I.calc },
    {                    label: 'Reportes',      ruta: '/reportes',     icono: I.chart },
  ],
}

const navItems = computed(() => NAV_CONFIG[auth.rol] ?? [])
</script>
