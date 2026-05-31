# Menú de Ventas con Tarjetas Visuales — Cajero Dashboard

## Resumen
Rediseñar la selección de menús en el modal "Nueva Venta" del cajero para mostrar tarjetas visuales con imagen, nombre y precio, en una grilla de 3 columnas con buscador. Agregar carga de imágenes (URL + archivo) en la gestión de menús.

---

## 1. Imágenes de Menú

### Almacenamiento
- Las imágenes subidas se guardan en `storage/app/public/menus/`
- Se genera un nombre único con `Str::uuid() . '.' . $extension`
- Se crea el symlink `public/storage → storage/app/public` si no existe
- La base de datos guarda la URL pública: `/storage/menus/{uuid}.{ext}`

### Formulario en MenusView (crear/editar)
- Campo **URL**: input de texto para pegar una URL externa
- Campo **Archivo**: input file (`accept="image/*"`) para subir desde la PC
- Si se proporcionan ambos, el archivo subido tiene prioridad
- **Previsualización**: miniatura de la imagen (desde URL o vista previa del archivo) que se actualiza en tiempo real
- Al editar, muestra la imagen existente

### Backend — Image Upload
- Nuevo endpoint `POST /menus/{id}/imagen` o integrado en store/update
- Validación: `nullable|image|mimes:jpeg,png,webp|max:2048`
- Si el menú ya tenía imagen, se elimina el archivo anterior al reemplazar
- El campo `imagen_url` en la DB almacena la ruta pública definitiva

---

## 2. Cajero — Tarjetas de Menú

### Modal "Nueva Venta"
- Reemplazar la grilla actual de botones por un diseño de tarjetas

### Layout
- **Buscador**: input de texto en la parte superior, filtra por nombre del menú
- **Grilla**: `grid grid-cols-3 gap-3` con tarjetas de menú
- **Scroll**: contenedor con scroll vertical si hay muchos menús

### Tarjeta (MenuCard)
```
┌──────────────────┐
│                   │
│     🖼 Imagen     │  ← 100% ancho, ~100px alto, object-cover
│                   │
├──────────────────┤
│  Nombre del Menú  │  ← font-medium, truncado si es largo
│  Bs. 99.99        │  ← precio en amber, font-mono
└──────────────────┘
```
- Sin imagen: placeholder con icono SVG de comida y color de fondo suave
- Hover: borde `border-amber/30` y sombra sutil, cursor pointer
- Click: agrega el ítem al carrito (misma lógica que ahora)

### Componente MenuCard
- Props: `menu` (objeto con nombre, precio_venta, imagen_url)
- Emits: `@click` → agrega al carrito
- Responsive: 3 columnas en desktop, 2 en tablet, 1 en mobile

---

## 3. Cambios Técnicos

### Backend
| Archivo | Cambio |
|---------|--------|
| `MenuController.php` | Agregar manejo de imagen en store/update. Nuevo método `subirImagen()` o integrado. Usar `Storage::disk('public')` |
| `routes/api.php` | Agregar ruta para subida de imagen si es endpoint separado |

### Frontend
| Archivo | Cambio |
|---------|--------|
| `resources/js/components/MenuCard.vue` | **Nuevo** — tarjeta visual de menú |
| `resources/js/views/dashboard/CajeroDashboard.vue` | Reemplazar grilla de botones por MenuCard grid + buscador |
| `resources/js/views/menus/MenusView.vue` | Campo imagen dual (URL + file) + preview |
| `resources/js/views/menus/MenusView.vue` | Mostrar miniatura en tabla de menús |

---

## 4. Flujo de Usuario

### Cajero realizando venta
1. Abre modal "Nueva venta"
2. Ve tarjetas con fotos de cada menú
3. Puede escribir en el buscador para filtrar
4. Click en una tarjeta → se agrega al carrito
5. Continúa agregando o confirma la venta

### Admin/Gerente gestionando menús
1. Abre formulario de menú (nuevo o editar)
2. Para imagen: escribe URL o selecciona archivo desde PC
3. Ve previsualización en tiempo real
4. Guarda → imagen se persiste en servidor o como URL externa

---

## 5. No Incluye (futuro)
- Categorías como pestañas en el modal del cajero (por ahora solo buscador)
- Productos individuales en venta del cajero (solo menús)
- Edición/corte de imagen (solo subida directa)
