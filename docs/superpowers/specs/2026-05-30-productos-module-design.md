# Productos Module — Design Spec

**Date:** 2026-05-30  
**Status:** Approved  
**Project:** Cafetería UPDS — SPA (Laravel + Vue 3 + Tailwind v4)

---

## 1. Problem

The sidebar has a link to `/dashboard/productos` but no Vue route or component exists. All sidebar CRUD links redirect to `/login` via the catch-all router, making it impossible to navigate away from the dashboard.

## 2. Scope

Build the **Productos** CRUD module only: list, create, edit, soft-delete (deactivate). This is the first of ~15 modules to be built incrementally.

## 3. Architecture

### 3.1 Route

Add a child route under the existing `/dashboard` layout in `router/index.js`:

```
path: 'productos',
component: () => import('@/views/productos/ProductosView.vue'),
meta: { rolRequerido: 'Administrador' }
```

The route inherits `AppLayout` (sidebar + header) and respects the role guard.

**Sidebar fix:** `AppSidebar.vue` currently links to `/productos` but the route is at `/dashboard/productos`. Update all sidebar entries for Administrador, Gerente, and Almacenista from `ruta: '/productos'` to `ruta: '/dashboard/productos'` to match.

### 3.2 Component

Single file: `resources/js/views/productos/ProductosView.vue`

Contains:
- Table listing all products (with inline actions)
- Reusable modal for create/edit
- Confirm dialog for deactivate

No composable or Pinia store — keeps complexity low and matches the inline pattern used by all 5 dashboard views.

### 3.3 Data Flow

```
onMounted → cargarProductos() → GET /productos → render table
abrirModal(producto?) → populate form fields (empty for create, filled for edit)
guardar() → POST /productos (create) | PUT /productos/{id} (update) → close modal → refresh table
desactivar(id) → confirm('¿Desactivar?') → DELETE /productos/{id} → refresh table
```

All calls use the existing `client.js` (axios instance with JWT interceptor). Error responses (422 validation) are shown inline in the modal.

### 3.4 API Endpoints (already exist)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/productos` | List (with `?activo=true`, `?categoria_id=` filters) |
| GET | `/productos/{id}` | Single product with category + lots |
| POST | `/productos` | Create |
| PUT | `/productos/{id}` | Update |
| DELETE | `/productos/{id}` | Soft-delete (sets `activo = false`) |
| GET | `/categorias` | Categories for filter dropdown + form select |

## 4. UI Design

### 4.1 Color / Font System

Uses existing Café Roast theme tokens:
- Background: `bg-card`, `bg-surface`, `bg-elevated`
- Borders: `border-edge`
- Text: `text-ink`, `text-ink-mute`, `text-ink-dim`
- Accent: `bg-amber`, `hover:bg-amber-bright`
- Semantic: `text-ok` (activo), `text-err` (inactivo/delete), `text-warn`
- Font: `font-display` (headings), `font-sans` (body), `font-mono` (numbers)

### 4.2 Page Layout

```
┌──────────────────────────────────────────────┐
│  Productos                        [+ Nuevo]  │  ← header with button
│  [Buscar...]  [Categoría: ▼]                 │  ← filters
├──────────────────────────────────────────────┤
│  Nombre │ Código │ Cat. │ Precio │ Costo │...│  ← table header
│  ───────┼────────┼──────┼────────┼───────┼───┤
│  Café   │ INS-01 │ Ins. │ Bs. 0  │ 8.50  │...│  ← rows with
│  Leche  │ INS-02 │ Ins. │ Bs. 0  │ 4.20  │...│     [Editar][Desactivar]
│  ...    │ ...    │ ...  │ ...    │ ...   │...│
└──────────────────────────────────────────────┘
```

### 4.3 Table Columns

| Column | Key | Format |
|--------|-----|--------|
| Nombre | `nombre` | text |
| Código | `codigo` | mono |
| Categoría | `categoria.nombre` | badge-like |
| Precio Venta | `precio_venta` | `Bs. X.XX` mono amber |
| Costo Unit. | `costo_unitario` | `Bs. X.XX` mono |
| Stock | `stock_actual` | mono, red if ≤ stock_minimo |
| Activo | `activo` | badge "Sí" (ok) / "No" (err) |
| Acciones | — | `[Editar]` `[Desactivar]` buttons |

### 4.4 Modal Form (Create / Edit)

```
┌──────────────────────────────────┐
│  ✕  Nuevo Producto              │
│                                  │
│  Nombre *         [____________] │
│  Código           [____________] │
│  Categoría *      [▼__________] │
│  Precio venta *   [____________] │
│  Costo unitario * [____________] │
│  Stock mínimo     [____________] │
│  Unidad medida    [▼__________] │
│  Requiere lote    [☐]           │
│                                  │
│  [Cancelar]        [Guardar]    │
│                                  │
│  ⚠ Error messages shown here   │
└──────────────────────────────────┘
```

Validation errors appear as red text below the relevant field, sourced from API 422 response.

### 4.5 Deactivate Flow

Clicking `[Desactivar]` shows a native `confirm()` dialog: "¿Estás seguro de desactivar {nombre}?". On confirm, calls `DELETE /productos/{id}` and refreshes the table. This is a soft-delete — the product's `activo` field is set to `false`.

## 5. Consistency with Existing Code

- **Pattern:** Same as dashboards — inline `ref`, `onMounted`, direct `client.get/post` calls
- **Imports:** Same component library (`StatCard`, `AlertBadge` from existing shared components)
- **Styling:** Uses Tailwind v4 utility classes with the `@theme` tokens already defined
- **No new dependencies:** Uses existing axios client, no extra npm packages

## 6. Error Handling

| Scenario | Behavior |
|----------|----------|
| API 422 validation | Show field-level errors in modal |
| API 500 / network | Show generic "Error al guardar" in modal |
| Empty list | Show "Sin productos registrados" in table area |
| Deactivate API fail | Show error toast / alert (future enhancement: toast system) |

## 7. Files Changed

| File | Action |
|------|--------|
| `resources/js/router/index.js` | Add `/dashboard/productos` child route |
| `resources/js/components/AppSidebar.vue` | Update 3 sidebar entries from `/productos` → `/dashboard/productos` |
| `resources/js/views/productos/ProductosView.vue` | **Create** — full page with table + modal |

## 8. Out of Scope

- Toast/notification system (all errors shown inline)
- Image upload for products
- Bulk import/export
- Audit log for changes
- Pagination (backend returns all; can be added later if needed)

## 9. Future Modules

After Productos, the next modules to build (in suggested order):
1. Categorías (simplest CRUD, 3 fields)
2. Usuarios (already has RegisterView, needs list + edit)
3. Menús (needs ingredientes sub-table)
4. Proveedores (simple CRUD)
5. Clientes (with puntos)
