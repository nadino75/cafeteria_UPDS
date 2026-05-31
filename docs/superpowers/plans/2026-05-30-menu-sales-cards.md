# Menú de Ventas con Tarjetas Visuales — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the cashier "Nueva Venta" modal to show aesthetic menu cards with images in a 3-column grid, add image upload (URL + file) in menu management.

**Architecture:** Backend handles image file upload in `MenuController@store`/`@update`, stores in `storage/app/public/menus/`. Frontend adds `MenuCard.vue` component reused in CajeroDashboard and MenusView. MenusView gets dual image input with live preview.

**Tech Stack:** Laravel 11, Vue 3 (Composition API), Tailwind CSS v4, Vite

**No git push until user confirms.**

---
### Task 1: Create storage symlink

- [ ] **Step 1: Create storage symlink**

The `public/storage` directory must link to `storage/app/public` so uploaded images are publicly accessible.

Run: `php artisan storage:link`

Expected output: `The [public/storage] link has been connected.`

---

### Task 2: Backend — Image upload in MenuController

**Files:**
- Modify: `app/Http/Controllers/Api/MenuController.php`
- Modify: `app/Models/Menu.php` (add `Storage` cleanup on delete if needed, but we don't hard-delete menus)

- [ ] **Step 1: Add import and helper method**

Add at the top of `MenuController.php` after existing imports:
```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
```

Add a private helper method for handling image upload:
```php
private function handleImagen(Request $request): ?string
{
    if ($request->hasFile('imagen')) {
        $file = $request->file('imagen');
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('menus', $name, 'public');
        return '/storage/' . $path;
    }
    if ($request->filled('imagen_url')) {
        return $request->imagen_url;
    }
    return null;
}
```

- [ ] **Step 2: Update `store` method validation**

Change the `imagen_url` validation to:
```php
'imagen_url'         => 'nullable|string|max:255',
'imagen'             => 'nullable|image|mimes:jpeg,png,webp|max:2048',
```

- [ ] **Step 3: Update `store` method body**

Replace the `Menu::create(...)` block:
```php
$data = $request->only([
    'nombre', 'descripcion', 'categoria_id', 'precio_venta',
    'disponible_desde', 'disponible_hasta',
]);
$data['imagen_url'] = $this->handleImagen($request);

$menu = DB::transaction(function () use ($request, $data) {
    $menu = Menu::create($data);
    foreach ($request->ingredientes as $ing) {
        $menu->ingredientes()->create([
            'producto_id'  => $ing['producto_id'],
            'cantidad'     => $ing['cantidad'],
            'unidad_medida'=> $ing['unidad_medida'] ?? null,
        ]);
    }
    return $menu;
});
```

- [ ] **Step 4: Update `update` method**

Replace the existing `$menu->update(...)` with image handling:
```php
$data = $request->only([
    'nombre', 'descripcion', 'categoria_id', 'precio_venta',
    'disponible_desde', 'disponible_hasta', 'activo',
]);

if ($request->hasFile('imagen') || $request->filled('imagen_url')) {
    $data['imagen_url'] = $this->handleImagen($request);
}

$menu->update($data);
```

Also update `update` validation rules to match store (add `'imagen' => 'nullable|image|mimes:jpeg,png,webp|max:2048'`).

- [ ] **Step 5: Verify**

Run: `php artisan route:list --path=api/menus`
Expected: Shows `POST /api/menus` and `PUT /api/menus/{menu}` routes.

---

### Task 3: Frontend — Create MenuCard.vue component

**Files:**
- Create: `resources/js/components/MenuCard.vue`

```vue
<template>
  <button @click="$emit('click')"
    class="group flex flex-col bg-card border border-edge hover:border-amber/40 hover:bg-amber/[0.03] rounded-xl overflow-hidden transition-all duration-200 cursor-pointer text-left">
    <!-- Image -->
    <div class="w-full aspect-[4/3] bg-elevated overflow-hidden">
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
    <!-- Info -->
    <div class="p-3 flex flex-col gap-0.5">
      <p class="text-ink text-sm font-medium leading-tight truncate">{{ menu.nombre }}</p>
      <p class="text-amber font-mono text-xs">Bs. {{ Number(menu.precio_venta).toFixed(2) }}</p>
    </div>
  </button>
</template>

<script setup>
defineProps({ menu: { type: Object, required: true } })
defineEmits(['click'])

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
```

- [ ] **Step 1: Create the file** with content above.

---

### Task 4: Frontend — Update CajeroDashboard with MenuCard grid + search

**Files:**
- Modify: `resources/js/views/dashboard/CajeroDashboard.vue`

- [ ] **Step 1: Add import**

After the existing `import AlertBadge from '@/components/AlertBadge.vue'` add:
```js
import MenuCard from '@/components/MenuCard.vue'
```

- [ ] **Step 2: Add search ref and computed filter**

After `const modalNuevaVenta = ref(false)` add:
```js
const busquedaMenu = ref('')

const menusFiltrados = computed(() => {
  const q = busquedaMenu.value.toLowerCase().trim()
  if (!q) return menus.value
  return menus.value.filter(m => m.nombre.toLowerCase().includes(q))
})
```

- [ ] **Step 3: Replace the menu selection grid in template**

Replace this block (lines 158-165):
```html
          <p class="text-ink-mute text-sm mb-2">Selecciona ítems:</p>
          <div class="grid grid-cols-2 gap-2 mb-4 max-h-48 overflow-y-auto">
            <button v-for="m in menus" :key="m.id" @click="agregarItem(m)"
              class="text-left p-3 bg-elevated hover:bg-amber/10 border border-edge hover:border-amber/30 rounded-lg transition-colors">
              <p class="text-ink text-sm font-medium truncate">{{ m.nombre }}</p>
              <p class="text-amber font-mono text-xs mt-0.5">Bs. {{ Number(m.precio_venta).toFixed(2) }}</p>
            </button>
          </div>
```

With:
```html
          <!-- Search -->
          <div class="relative mb-3">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-dim" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input v-model="busquedaMenu" type="text" placeholder="Buscar menú..."
              class="w-full bg-elevated border border-edge rounded-lg pl-10 pr-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
          <!-- Menu cards grid -->
          <p class="text-ink-mute text-sm mb-2">Selecciona un menú:</p>
          <div class="grid grid-cols-3 gap-3 mb-4 max-h-72 overflow-y-auto pr-1">
            <MenuCard v-for="m in menusFiltrados" :key="m.id" :menu="m" @click="agregarItem(m)" />
            <div v-if="menusFiltrados.length === 0"
              class="col-span-3 py-8 text-center text-ink-dim text-sm">Sin resultados</div>
          </div>
```

- [ ] **Step 4: Increase modal max-width if needed**

On line 148, change `max-w-lg` to `max-w-xl` to fit 3-column grid comfortably:
```html
<div class="bg-card border border-edge rounded-2xl w-full max-w-xl p-6 overflow-y-auto max-h-[90vh]">
```

---

### Task 5: Frontend — Update MenusView with dual image input + preview

**Files:**
- Modify: `resources/js/views/menus/MenusView.vue`

- [ ] **Step 1: Add image preview ref and file handler in script**

After `const errorForm = ref(null)` add:
```js
const imagenPreview = ref(null)
const imagenFile = ref(null)
```

After `function cerrarModal()` add:
```js
function onImagenFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  imagenFile.value = file
  const reader = new FileReader()
  reader.onload = (ev) => { imagenPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}
```

- [ ] **Step 2: Replace the imagen_url input and add file input + preview in template**

Replace lines 104-108:
```html
          <div>
            <label class="block text-ink-mute text-sm mb-1">Imagen URL</label>
            <input v-model="form.imagen_url" type="text" placeholder="https://..."
              class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
          </div>
```

With:
```html
          <div>
            <label class="block text-ink-mute text-sm mb-1">Imagen</label>
            <div class="flex flex-col gap-2">
              <input v-model="form.imagen_url" type="text" placeholder="https://..."
                class="w-full bg-elevated border border-edge rounded-lg px-4 py-2.5 text-ink text-sm focus:outline-none focus:border-amber" />
              <label class="flex items-center gap-2 text-ink-mute text-sm cursor-pointer hover:text-ink transition-colors">
                <span class="px-3 py-2 bg-elevated border border-edge rounded-lg text-xs hover:border-amber/30 transition-colors">Subir archivo</span>
                <input type="file" accept="image/jpeg,image/png,image/webp" @change="onImagenFileChange"
                  class="hidden" />
                <span v-if="imagenFile" class="text-xs text-ink-dim">{{ imagenFile.name }}</span>
              </label>
            </div>
            <div v-if="imagenPreview || form.imagen_url" class="mt-2 rounded-lg overflow-hidden border border-edge w-32 h-24 bg-elevated">
              <img v-if="imagenPreview" :src="imagenPreview" class="w-full h-full object-cover" />
              <img v-else :src="form.imagen_url" class="w-full h-full object-cover" @error="($event.target.style.display='none')" />
            </div>
          </div>
```

- [ ] **Step 3: Update the `guardar` function to send FormData when file is present**

Replace the `guardar` function:
```js
async function guardar() {
  guardando.value = true; errorForm.value = null
  try {
    const payload = imagenFile.value ? buildFormData() : form.value
    const headers = imagenFile.value ? { 'Content-Type': 'multipart/form-data' } : {}

    if (modoEdicion.value) {
      payload.append ? payload.append('_method', 'PUT') : null
      await client.post(`/menus/${form.value._id}`, payload, { headers })
    } else {
      await client.post('/menus', payload, { headers })
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
```

- [ ] **Step 4: Reset image state on modal close**

Update `cerrarModal`:
```js
function cerrarModal() {
  modalAbierto.value = false
  errorForm.value = null
  imagenPreview.value = null
  imagenFile.value = null
}
```

- [ ] **Step 5: Show image in the table (optional enhancement)**

Add an image column to the table. After the `<th>Nombre</th>` line (line 24), add:
```html
<th class="text-left px-5 py-3">Imagen</th>
```

After the `<td>` for nombre (line 39), add:
```html
<td class="px-5 py-3">
  <img v-if="m.imagen_url" :src="m.imagen_url" class="w-10 h-10 rounded-lg object-cover border border-edge"
    @error="($event.target.style.display='none')" />
  <span v-else class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-elevated text-ink-dim/30 text-xs">—</span>
</td>
```

Update `colspan` on the empty row: change `colspan="7"` to `colspan="8"`.

---

### Task 6: Build and verify

- [ ] **Step 1: Build**

Run: `npm run build`
Expected: Clean build with no errors.

- [ ] **Step 2: Verify image upload endpoint**

Run: `php artisan route:list --path=api/menus`
Expected: `POST /api/menus` and `PUT /api/menus/{menu}` both listed.

- [ ] **Step 3: Test the flow manually** (optional)
1. Open MenusView → Crear nuevo menú → upload an image → save → verify it shows in table
2. Open Cajero Dashboard → Abrir turno → Nueva venta → see menu cards with images
3. Edit a menu → change image → verify refresh

---

### Files Summary

| Action | File |
|--------|------|
| Modify | `app/Http/Controllers/Api/MenuController.php` |
| Create | `resources/js/components/MenuCard.vue` |
| Modify | `resources/js/views/dashboard/CajeroDashboard.vue` |
| Modify | `resources/js/views/menus/MenusView.vue` |
| Run | `php artisan storage:link` |
