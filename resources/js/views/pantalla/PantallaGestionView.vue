<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold" style="color: var(--color-ink)">Pantalla Informativa</h1>
      <button
        class="px-4 py-2 rounded-lg font-medium transition-all duration-200"
        style="background: var(--color-primary); color: white;"
        @click="abrirModal(null)"
      >
        + Subir contenido
      </button>
    </div>

    <div v-if="cargando" class="text-center py-12" style="color: var(--color-ink-dim)">
      Cargando...
    </div>

    <div v-else-if="contenidos.length === 0" class="text-center py-12" style="color: var(--color-ink-dim)">
      No hay contenido. Subí el primer video o imagen.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="(item, index) in contenidos"
        :key="item.id"
        class="flex items-center gap-4 p-4 rounded-lg transition-all duration-200"
        :style="{ background: 'var(--color-card)', border: '1px solid var(--color-edge)' }"
      >
        <span class="text-sm font-mono w-8 text-center" style="color: var(--color-ink-dim)">{{ index + 1 }}</span>

        <div class="w-20 h-14 rounded overflow-hidden flex-shrink-0"
             style="background: var(--color-surface)">
          <video v-if="item.tipo === 'video'" :src="storageBase + item.archivo_url" muted class="w-full h-full object-cover" />
          <img v-else :src="storageBase + item.archivo_url" class="w-full h-full object-cover" />
        </div>

        <div class="flex-1 min-w-0">
          <p class="font-medium truncate" style="color: var(--color-ink)">{{ item.titulo }}</p>
          <p class="text-sm" style="color: var(--color-ink-dim)">
            {{ item.tipo === 'video' ? 'Video' : 'Imagen' }} · {{ item.duracion_segundos }}s
          </p>
        </div>

        <button
          class="px-3 py-1 rounded text-sm font-medium transition-all"
          :style="{
            background: item.activo ? 'rgba(34,197,94,0.15)' : 'rgba(100,100,100,0.15)',
            color: item.activo ? '#22c55e' : 'var(--color-ink-dim)'
          }"
          @click="toggle(item)"
        >
          {{ item.activo ? 'Activo' : 'Inactivo' }}
        </button>

        <button class="p-2 rounded-lg transition-all hover:opacity-70"
                style="color: var(--color-ink-dim)" title="Editar"
                @click="abrirModal(item)">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>

        <button class="p-2 rounded-lg transition-all hover:opacity-70" style="color: #ef4444"
                title="Eliminar" @click="eliminar(item)">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4"
           style="background: rgba(0,0,0,0.6)">
        <div class="w-full max-w-lg rounded-xl p-6" :style="{ background: 'var(--color-card)', border: '1px solid var(--color-edge)' }">
          <h2 class="text-lg font-bold mb-4" style="color: var(--color-ink)">
            {{ editando ? 'Editar contenido' : 'Subir nuevo contenido' }}
          </h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1" style="color: var(--color-ink)">Título</label>
              <input v-model="form.titulo"
                     class="w-full px-3 py-2 rounded-lg text-sm"
                     :style="{ background: 'var(--color-surface)', color: 'var(--color-ink)', border: '1px solid var(--color-edge)' }"
                     placeholder="Ej: Promoción del día" />
            </div>

            <div v-if="!editando">
              <label class="block text-sm font-medium mb-1" style="color: var(--color-ink)">Tipo</label>
              <select v-model="form.tipo"
                      class="w-full px-3 py-2 rounded-lg text-sm"
                      :style="{ background: 'var(--color-surface)', color: 'var(--color-ink)', border: '1px solid var(--color-edge)' }">
                <option value="video">Video (MP4)</option>
                <option value="imagen">Imagen (JPEG/PNG)</option>
              </select>
            </div>

            <div v-if="!editando">
              <label class="block text-sm font-medium mb-1" style="color: var(--color-ink)">Archivo</label>
              <input type="file" ref="fileInput" :accept="form.tipo === 'video' ? 'video/mp4' : 'image/jpeg,image/png'"
                     class="w-full text-sm"
                     :style="{ color: 'var(--color-ink)' }"
                     @change="form.archivo = $event.target.files[0]" />
              <p class="text-xs mt-1" style="color: var(--color-ink-dim)">MP4 hasta 100MB · JPEG/PNG hasta 100MB</p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1" style="color: var(--color-ink)">Duración (segundos)</label>
              <input v-model.number="form.duracion_segundos" type="number" min="3" max="3600"
                     class="w-full px-3 py-2 rounded-lg text-sm"
                     :style="{ background: 'var(--color-surface)', color: 'var(--color-ink)', border: '1px solid var(--color-edge)' }" />
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                    :style="{ color: 'var(--color-ink-dim)' }"
                    @click="cerrarModal">Cancelar</button>
            <button class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                    style="background: var(--color-primary); color: white;"
                    :disabled="guardando"
                    @click="guardar">
              {{ guardando ? 'Guardando...' : (editando ? 'Guardar cambios' : 'Subir') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '../../api/client'

const storageBase = window.location.origin

const contenidos = ref([])
const cargando = ref(true)
const modalAbierto = ref(false)
const editando = ref(false)
const guardando = ref(false)
const fileInput = ref(null)

const formDefault = () => ({
  titulo: '',
  tipo: 'video',
  archivo: null,
  duracion_segundos: 10,
})

const form = ref(formDefault())

async function cargar() {
  try {
    const res = await client.get('/pantalla')
    contenidos.value = res.data
  } catch (e) {
    console.error('Error al cargar contenidos:', e)
  } finally {
    cargando.value = false
  }
}

function abrirModal(item) {
  if (item) {
    editando.value = true
    form.value = {
      titulo: item.titulo,
      tipo: item.tipo,
      archivo: null,
      duracion_segundos: item.duracion_segundos,
    }
    form.value._id = item.id
  } else {
    editando.value = false
    form.value = formDefault()
  }
  modalAbierto.value = true
}

function cerrarModal() {
  modalAbierto.value = false
  form.value = formDefault()
}

async function guardar() {
  guardando.value = true
  try {
    if (editando.value) {
      await client.put(`/pantalla/${form.value._id}`, {
        titulo: form.value.titulo,
        duracion_segundos: form.value.duracion_segundos,
      })
    } else {
      const fd = new FormData()
      fd.append('titulo', form.value.titulo)
      fd.append('tipo', form.value.tipo)
      fd.append('archivo', form.value.archivo)
      fd.append('duracion_segundos', form.value.duracion_segundos)
      await client.post('/pantalla', fd)
    }
    cerrarModal()
    await cargar()
  } catch (e) {
    console.error('Error al guardar:', e.response?.data || e)
    alert('Error: ' + JSON.stringify(e.response?.data?.errors || e.response?.data?.message || e.message))
  } finally {
    guardando.value = false
  }
}

async function toggle(item) {
  try {
    await client.patch(`/pantalla/${item.id}/toggle`)
    item.activo = !item.activo
  } catch (e) {
    console.error('Error al toggle:', e)
  }
}

async function eliminar(item) {
  if (!confirm(`¿Eliminar "${item.titulo}"?`)) return
  try {
    await client.delete(`/pantalla/${item.id}`)
    await cargar()
  } catch (e) {
    console.error('Error al eliminar:', e)
  }
}

onMounted(cargar)
</script>
