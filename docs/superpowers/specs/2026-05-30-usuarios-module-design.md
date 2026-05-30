# Usuarios CRUD Module — Design Spec

## Route & Access
- **Path:** `/dashboard/usuarios` (child route under AppLayout)
- **Roles:** Administrador, Gerente

## View: `resources/js/views/usuarios/UsuariosView.vue`
Single-file component following the same pattern as `ProductosView.vue`.

### Table
| Column | Detail |
|--------|--------|
| Nombre Completo | Left-aligned, bold |
| Email | Left-aligned, font-mono |
| Rol | Badge style (pill) fetched from `rol.nombre` |
| Activo | Yes/No badge (green/red dot) |
| Acciones | Editar \| Desactivar (inline buttons) |

### Search
- Text input for name/email search (client-side filter)

### Modal (Teleport)
- **nombre_completo** — text input, required
- **email** — email input, required
- **rol_id** — `<select>` dropdown of available roles (hardcoded list matching RegisterView.vue). Administrador role excluded for non-admin users.
- **password** — password input, required on create; hidden by default on edit with a "Cambiar contraseña?" checkbox to reveal

### CRUD Operations
| Action | HTTP | Endpoint |
|--------|------|----------|
| List | GET | `/usuarios` |
| Create | POST | `/usuarios` |
| Edit | PUT | `/usuarios/{id}` |
| Deactivate | DELETE | `/usuarios/{id}` (soft-delete, sets `activo = false`) |

### Error Handling
- 422 validation errors shown inline (same as ProductosView)
- Loading state (`guardando`) disables submit button
- Empty catch with alert for deactivate errors

## Route Registration
Add to `resources/js/router/index.js`:

```js
{
  path: 'usuarios',
  component: () => import('@/views/usuarios/UsuariosView.vue'),
  meta: { rolRequerido: ['Administrador', 'Gerente'] },
},
```

## Sidebar
No changes needed — sidebar already has `ruta: '/usuarios'`. The route just needs to be registered.

## Refs
- ProductosView.vue: reference implementation for pattern
- RegisterView.vue: ROLES array reference
