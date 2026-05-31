<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="font-display text-3xl text-ink font-semibold">Menús</h1>
        <p class="text-ink-mute text-sm mt-1">Gestión de menús del catálogo</p>
      </div>
      <button @click="abrirModal()"
        class="px-4 py-2 bg-amber hover:bg-amber-bright text-base text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        + Nuevo menú
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
              <th class="text-center px-5 py-3">Imagen</th>
              <th class="text-left px-5 py-3">Categoría</th>
              <th class="text-right px-5 py-3">Precio Venta</th>
              <th class="text-center px-5 py-3">Ingredientes</th>
              <th class="text-center px-5 py-3">Disponible</th>
              <th class="text-center px-5 py-3">Activo</th>
              <th class="text-center px-5 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="menusFiltrados.length === 0">
              <td colspan="8" class="px-5 py-8 text-center text-ink-mute">Sin menús registrados</td>
            </tr>
            <tr v-for="m in menusFiltrados" :key="m.id"
              class="border-t border-edge hover:bg-elevated/30 transition-colors">
              <td class="px-5 py-3 text-ink font-medium">{{ m.nombre }}</td>
              <td class="px-5 py-3 text-center">
                <img v-if="m.imagen_url" :src="m.imagen_url" class="w-10 h-10 rounded-lg object-cover border border-edge inline-block"
                  @error="($event.target.style.display='none')" />
                <span v-else class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-elevated text-ink-dim/30 text-xs">—</span>
              </td>
              <td class="px-5 py-3">
                <span class="inline-flex px-2 py-0.5 rounded text-xs bg-elevated text-ink-mute border border-edge">
                  {{ m.categoria?.nombre || '—' }}
                </span>
              </td>
              <td class="px-5 py-3 text-right font-mono text-amber">Bs. {{ Number(m.precio_venta).toFixed(2) }}</td>
              <td class="px-5 py-3 text-center text-ink-dim text-xs">{{ m.ingredientes?.length ?? 0 }}</td>
              <td class="px-5 py-3 text-center text-ink-dim text-xs">
                {{ m.disponible_desde ? m.disponible_desde.slice(0,5) : '—' }} - {{ m.disponible_hasta ? m.disponible_hasta.slice(0,5) : '—' }}
              </td>
              <td class="px-5 py-3 text-center">
                <span v-if="m.activo" class="inline-flex items-center gap-1 text-ok text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-ok" /> Sí
                </span>
                <span v-else class="inline-flex items-center gap-1 text-err text-xs">
                  <span class="w-1.5 h-1.5 rounded-full bg-err" /> No
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <button @click="abrirModal(m)"
                    class="text-ink-mute hover:text-amber text-xs font-medium transition-colors">Editar</button>
                  <span class="text-edge-lit">|</span>
                  <button @click="desactivar(m)"
                    class="text-ink-mute hover:text-err text-xs font-medium transition-colors">Desactivar</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  <Teleport to="body">
    <div v-if="modalAbierto" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center p-4 pt-8 overflow-y-auto">
      <div class="bg-card border border-edge rounded-2xl w-full max-w-lg p-6 my-4">
        <h3 class="font-display text-xl text-ink font-medium mb-4">
          {{ modoEdicion ? 'Editar menú' : 'Nuevo menú' }}
        </h3>

        <div class="space-y-3">
          <div>
            <label class="block text-ink-mute text-sm mb-1">Nombre *</label>
            <input v-model="form.nombre" type="text"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Descripción</label>
            <textarea v-model="form.descripcion" rows="2"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber"></textarea>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Categoría</label>
            <select v-model="form.categoria_id"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber">
              <option value="">Sin categoría</option>
              <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Precio venta *</label>
            <input v-model.number="form.precio_venta" type="number" min="0" step="0.01"
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <div>
            <label class="block text-ink-mute text-sm mb-1">Imagen</label>
            <div class="flex flex-col gap-2">
              <input v-model="form.imagen_url" type="text" placeholder="https://..."
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
              <label class="flex items-center gap-2 text-ink-mute text-sm cursor-pointer hover:text-ink transition-colors">
                <span class="px-3 py-2 bg-elevated border border-edge rounded-lg text-xs hover:border-amber/30 transition-colors">Subir archivo</span>
                <input type="file" accept="image/jpeg,image/png,image/webp" @change="onImagenFileChange" class="hidden" />
                <span v-if="imagenFile" class="text-xs text-ink-dim truncate">{{ imagenFile.name }}</span>
              </label>
            </div>
            <div v-if="imagenPreview || form.imagen_url" class="mt-2 rounded-lg overflow-hidden border border-edge w-32 h-24 bg-elevated">
              <img v-if="imagenPreview" :src="imagenPreview" class="w-full h-full object-cover" />
              <img v-else :src="form.imagen_url" class="w-full h-full object-cover" @error="($event.target.style.display='none')" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-ink-mute text-sm mb-1">Disponible desde</label>
              <input v-model="form.disponible_desde" type="time"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
            <div>
              <label class="block text-ink-mute text-sm mb-1">Disponible hasta</label>
              <input v-model="form.disponible_hasta" type="time"
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
            </div>
          </div>

          <!-- Ingredientes -->
          <div class="pt-3 border-t border-edge">
            <div class="flex items-center justify-between mb-2">
              <label class="text-ink-mute text-sm font-medium">Ingredientes *</label>
              <button @click="agregarIngrediente" type="button"
                class="text-xs text-amber hover:text-amber-bright font-medium transition-colors">+ Agregar</button>
            </div>
            <div v-for="(ing, i) in form.ingredientes" :key="i"
              class="flex items-start gap-2 mb-2 bg-elevated/50 p-2 rounded-lg">
              <select v-model="ing.producto_id" class="flex-1 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber">
                <option value="" disabled>Producto...</option>
                <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
              </select>
              <input v-model.number="ing.cantidad" type="number" min="0.001" step="0.001" placeholder="Cant."
                class="w-20 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
              <input v-model="ing.unidad_medida" type="text" placeholder="Ud."
                class="w-20 bg-elevated border border-edge rounded-lg px-3 py-2 text-ink text-xs focus:outline-none focus:border-amber" />
              <button @click="form.ingredientes.splice(i, 1)" type="button"
                class="text-err hover:text-err/70 p-1" title="Eliminar">✕</button>
            </div>
            <p v-if="form.ingredientes.length === 0" class="text-ink-dim text-xs italic">Agrega al menos un ingrediente</p>
          </div>
        </div>

        <p v-if="errorForm" class="text-err text-sm mt-4">{{ errorForm }}</p>

        <div class="flex gap-3 mt-5">
          <button @click="cerrarModal"
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

const menus         = ref([])
const categorias    = ref([])
const productos     = ref([])
const filtro        = ref('')

const menusFiltrados = computed(() => {
  const q = filtro.value.toLowerCase()
  return menus.value.filter(m => m.nombre.toLowerCase().includes(q))
})

onMounted(() => Promise.all([cargarMenus(), cargarCategorias(), cargarProductos()]))

async function cargarMenus() {
  try {
    const { data } = await client.get('/menus')
    menus.value = data.data ?? []
  } catch { menus.value = [] }
}

async function cargarCategorias() {
  try {
    const { data } = await client.get('/categorias')
    categorias.value = data.data ?? []
  } catch { categorias.value = [] }
}

async function cargarProductos() {
  try {
    const { data } = await client.get('/productos')
    productos.value = data.data ?? []
  } catch { productos.value = [] }
}

const modalAbierto = ref(false)
const modoEdicion  = ref(false)
const guardando    = ref(false)
const errorForm    = ref(null)
const imagenPreview = ref(null)
const imagenFile   = ref(null)
const form         = ref({
  nombre: '',
  descripcion: '',
  categoria_id: '',
  precio_venta: null,
  imagen_url: '',
  disponible_desde: '',
  disponible_hasta: '',
  ingredientes: [],
})

function agregarIngrediente() {
  form.value.ingredientes.push({ producto_id: '', cantidad: null, unidad_medida: '' })
}

function abrirModal(menu = null) {
  errorForm.value = null
  if (menu) {
    modoEdicion.value = true
    form.value = {
      nombre: menu.nombre,
      descripcion: menu.descripcion || '',
      categoria_id: menu.categoria_id || '',
      precio_venta: menu.precio_venta,
      imagen_url: menu.imagen_url || '',
      disponible_desde: menu.disponible_desde || '',
      disponible_hasta: menu.disponible_hasta || '',
      ingredientes: (menu.ingredientes || []).map(i => ({
        producto_id: i.producto_id,
        cantidad: i.cantidad,
        unidad_medida: i.unidad_medida || '',
      })),
      _id: menu.id,
    }
  } else {
    modoEdicion.value = false
    form.value = { nombre: '', descripcion: '', categoria_id: '', precio_venta: null, imagen_url: '', disponible_desde: '', disponible_hasta: '', ingredientes: [] }
  }
  modalAbierto.value = true
}

function cerrarModal() {
  modalAbierto.value = false
  errorForm.value = null
  imagenPreview.value = null
  imagenFile.value = null
}

function onImagenFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  imagenFile.value = file
  const reader = new FileReader()
  reader.onload = (ev) => { imagenPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}

async function guardar() {
  guardando.value = true; errorForm.value = null
  try {
    const hasFile = imagenFile.value !== null
    const payload = hasFile ? buildFormData() : { ...form.value }
    const config  = hasFile ? { headers: { 'Content-Type': 'multipart/form-data' } } : {}

    if (modoEdicion.value) {
      if (hasFile) {
        payload.append('_method', 'PUT')
        await client.post(`/menus/${form.value._id}`, payload, config)
      } else {
        await client.put(`/menus/${form.value._id}`, payload, config)
      }
    } else {
      await client.post('/menus', payload, config)
    }
    cerrarModal()
    await cargarMenus()
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) {
      errorForm.value = Object.values(errs).flat().join('. ')
    } else {
      errorForm.value = e.response?.data?.message || 'Error al guardar el menú.'
    }
  } finally { guardando.value = false }
}

function buildFormData() {
  const fd = new FormData()
  fd.append('imagen', imagenFile.value)
  ;['nombre', 'descripcion', 'categoria_id', 'precio_venta', 'imagen_url',
    'disponible_desde', 'disponible_hasta'].forEach(k => {
    if (form.value[k] != null && form.value[k] !== '') fd.append(k, form.value[k])
  })
  form.value.ingredientes.forEach((ing, i) => {
    fd.append(`ingredientes[${i}][producto_id]`, ing.producto_id)
    fd.append(`ingredientes[${i}][cantidad]`, ing.cantidad)
    fd.append(`ingredientes[${i}][unidad_medida]`, ing.unidad_medida || '')
  })
  return fd
}

async function desactivar(menu) {
  if (!confirm(`¿Estás seguro de desactivar "${menu.nombre}"?`)) return
  try {
    await client.delete(`/menus/${menu.id}`)
    await cargarMenus()
  } catch {
    alert('Error al desactivar el menú.')
  }
}
</script>
