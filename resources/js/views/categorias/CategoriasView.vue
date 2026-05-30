<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Categorías</h1>
        <p class="text-ink-mute text-sm mt-1">Clasificación de productos y menús</p>
      </div>
      <button @click="abrirModal()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nueva categoría
      </button>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <input v-model="filtro" type="text" placeholder="Buscar por nombre..."
        class="w-full sm:w-64 bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
    </div>

    <div class="bg-card border border-edge rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-ink-dim text-xs uppercase tracking-wider bg-elevated/50">
              <th class="text-left px-5 py-3">Nombre</th>
              <th class="text-left px-5 py-3">Descripción</th>
              <th class="text-left px-5 py-3">Aplica a</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="categoriasFiltradas.length === 0">
              <td colspan="4" class="px-5 py-8 text-center text-ink-mute">Sin categorías registradas</td>
            </tr>
            <tr v-for="c in categoriasFiltradas" :key="c.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-medium">{{ c.nombre }}</td>
              <td class="px-5 py-3 text-ink-dim text-xs">{{ c.descripcion || '—' }}</td>
              <td class="px-5 py-3">
                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-elevated text-ink-mute border border-edge">
                  {{ { producto: 'Producto', menu: 'Menú', ambos: 'Ambos' }[c.aplica_a] || c.aplica_a }}
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button @click="abrirModal(c)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">Editar</button>
                  <span class="text-edge-lit">|</span>
                  <button @click="eliminar(c)"
                    class="text-ink-mute hover:text-err text-xs font-medium transition-colors">Eliminar</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  <Teleport to="body">
    <div v-if="modalAbierto" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-md p-6">
        <h3 class="font-display text-xl text-ink font-medium mb-4">
          {{ modoEdicion ? 'Editar categoría' : 'Nueva categoría' }}
        </h3>
        <div class="space-y-3">
          <div>
            <label class="block text-ink-mute text-sm mb-1">Nombre *</label>
            <input v-model="form.nombre" type="text" maxlength="50"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Descripción</label>
            <textarea v-model="form.descripcion" rows="2"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber"></textarea>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Aplica a *</label>
            <select v-model="form.aplica_a"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="producto">Producto</option>
              <option value="menu">Menú</option>
              <option value="ambos">Ambos</option>
            </select>
          </div>
        </div>
        <p v-if="errorForm" class="text-err text-sm mt-4">{{ errorForm }}</p>
        <div class="flex gap-3 mt-5">
          <button @click="modalAbierto = false; errorForm = null"
            class="flex-1 border border-edge text-ink-mute py-2.5 rounded-lg text-sm hover:text-ink transition-colors">Cancelar</button>
          <button @click="guardar" :disabled="guardando"
            class="flex-1 bg-amber hover:bg-amber-bright text-base font-medium py-2.5 rounded-lg text-sm disabled:opacity-50 transition-colors">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '@/api/client.js'

const categorias          = ref([])
const filtro             = ref('')

const categoriasFiltradas = computed(() => {
  const q = filtro.value.toLowerCase()
  return categorias.value.filter(c => c.nombre.toLowerCase().includes(q))
})

onMounted(() => cargarCategorias())

async function cargarCategorias() {
  try {
    const { data } = await client.get('/categorias')
    categorias.value = data.data ?? []
  } catch { categorias.value = [] }
}

const modalAbierto = ref(false)
const modoEdicion  = ref(false)
const guardando    = ref(false)
const errorForm    = ref(null)
const form         = ref({ nombre: '', descripcion: '', aplica_a: 'producto' })

function abrirModal(categoria = null) {
  errorForm.value = null
  if (categoria) {
    modoEdicion.value = true
    form.value = { nombre: categoria.nombre, descripcion: categoria.descripcion || '', aplica_a: categoria.aplica_a || 'producto', _id: categoria.id }
  } else {
    modoEdicion.value = false
    form.value = { nombre: '', descripcion: '', aplica_a: 'producto' }
  }
  modalAbierto.value = true
}

async function guardar() {
  guardando.value = true; errorForm.value = null
  try {
    if (modoEdicion.value) {
      await client.put(`/categorias/${form.value._id}`, form.value)
    } else {
      await client.post('/categorias', form.value)
    }
    modalAbierto.value = false
    await cargarCategorias()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorForm.value = Object.values(errs).flat().join('. ')
    } else {
      errorForm.value = e.response?.data?.message || 'Error al guardar la categoría.'
    }
  } finally { guardando.value = false }
}

async function eliminar(categoria) {
  if (!confirm(`¿Eliminar categoría "${categoria.nombre}"?`)) return
  try {
    await client.delete(`/categorias/${categoria.id}`)
    await cargarCategorias()
  } catch (e) {
    alert(e.response?.data?.message || 'Error al eliminar la categoría.')
  }
}
</script>
