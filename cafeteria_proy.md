# ☕ Sistema de Gestión Cafetería — Contexto de Proyecto para Claude Code

> Este archivo es leído automáticamente por Claude Code al inicio de cada sesión.
> Colócalo en la raíz del proyecto. No lo borres ni lo renombres.

---

## 🗂️ Descripción del proyecto

Sistema de gestión integral para cafetería desarrollado con arquitectura web moderna.
Controla ventas por turnos, inventario con modalidad FIFO, menús, compras a proveedores,
contabilidad diaria y reportes de rentabilidad.

**Stack tecnológico:**
- Backend: Node.js + Express (o el framework que el equipo defina al iniciar)
- Base de datos: MySQL 8+ — esquema en `database/DB_cafeteria_COMPLETA.sql`
- Frontend: React + Vite (o definido por el equipo)
- ORM sugerido: Prisma o Sequelize
- Autenticación: JWT con roles granulares

---

## 🏗️ Arquitectura de la base de datos

La BD se llama `cafeteria_db`. El script maestro está en:
```
database/DB_cafeteria_COMPLETA.sql
```

### Módulos principales (12 en total)

| # | Módulo | Tablas clave |
|---|--------|-------------|
| 1 | Roles y permisos granulares | `roles`, `modulos_sistema`, `acciones_sistema`, `rol_permisos` |
| 2 | Usuarios y auditoría | `usuarios`, `log_actividad` |
| 3 | Catálogos base | `categorias`, `clientes`, `proveedores` |
| 4 | Productos | `productos` |
| 5 | **Inventario FIFO** | `lotes_inventario`, `movimientos_inventario` |
| 6 | Menús y recetas | `menus`, `menu_ingredientes` |
| 7 | Turnos y cierres | `turnos`, `cortes_caja`, `cierres_diarios` |
| 8 | Ventas | `ventas`, `detalle_venta`, `detalle_venta_lotes` |
| 9 | Compras | `compras`, `detalle_compra` |
| 10 | Gastos operativos | `gastos_operativos` |
| 11 | Contabilidad diaria | `cuentas_contables`, `asientos_contables`, `lineas_asiento`, `balance_diario` |
| 12 | Reportes snapshot | `reporte_ventas_producto`, `reporte_stock`, `reporte_mensual` |

### Reglas críticas de la BD que SIEMPRE debes respetar

**FIFO — nunca saltes esta lógica:**
```sql
-- Al descontar inventario, SIEMPRE usar el lote más antiguo disponible primero:
SELECT * FROM lotes_inventario
WHERE producto_id = ? AND estado = 'disponible'
ORDER BY fecha_entrada ASC
FOR UPDATE;
```
Cuando se recibe una compra (`detalle_compra.cantidad_recibida > 0`), se debe crear
automáticamente un registro en `lotes_inventario` y actualizar `detalle_compra.lote_generado_id`.

**Turnos — flujo obligatorio:**
```
abierto → en_corte → cerrado
```
No se puede abrir un nuevo turno si existe uno `abierto` para el mismo usuario.
Al cerrar un turno siempre se crea un registro en `cortes_caja`.
El `cierre_diario` consolida todos los turnos del día y se crea una vez por fecha.

**Permisos — siempre verificar así:**
```sql
SELECT 1 FROM rol_permisos rp
JOIN modulos_sistema m ON rp.modulo_id = m.id
JOIN acciones_sistema a ON rp.accion_id = a.id
JOIN usuarios u ON u.rol_id = rp.rol_id
WHERE u.id = :usuario_id
  AND m.clave = :modulo   -- ej: 'ventas'
  AND a.clave = :accion   -- ej: 'crear'
LIMIT 1;
```
Si el rol tiene `es_superadmin = TRUE`, bypasear esta consulta y permitir todo.

---

## 📐 Convenciones de código

### Nomenclatura
- Archivos: `kebab-case` → `lotes-inventario.service.js`
- Clases/componentes: `PascalCase` → `LotesInventarioService`
- Variables y funciones: `camelCase` → `calcularCostoFIFO()`
- Constantes globales: `UPPER_SNAKE_CASE` → `ESTADO_TURNO`
- Rutas API: `kebab-case` plural → `/api/lotes-inventario`

### Estructura de carpetas esperada
```
cafeteria-sistema/
├── CLAUDE.md                   ← este archivo
├── REVIEW.md                   ← criterios de revisión de código
├── database/
│   ├── DB_cafeteria_COMPLETA.sql
│   └── seeds/                  ← datos de prueba
├── backend/
│   ├── src/
│   │   ├── modules/
│   │   │   ├── auth/
│   │   │   ├── ventas/
│   │   │   ├── inventario/     ← lógica FIFO aquí
│   │   │   ├── turnos/
│   │   │   ├── compras/
│   │   │   ├── menus/
│   │   │   ├── contabilidad/
│   │   │   └── reportes/
│   │   ├── middleware/
│   │   │   ├── auth.middleware.js
│   │   │   └── permisos.middleware.js
│   │   ├── shared/
│   │   └── app.js
│   ├── .env.example
│   └── package.json
├── frontend/
│   ├── src/
│   │   ├── pages/
│   │   ├── components/
│   │   └── stores/             ← Zustand o Pinia
│   └── package.json
└── docker-compose.yml
```

### Patrones obligatorios
- Todas las rutas protegidas pasan por `auth.middleware.js` (verifica JWT)
- Todas las rutas de módulos pasan por `permisos.middleware.js` (verifica `rol_permisos`)
- Las operaciones de inventario FIFO siempre dentro de una transacción MySQL (`BEGIN / COMMIT / ROLLBACK`)
- Los errores se lanzan con clases personalizadas: `AppError`, `ValidationError`, `PermissionError`
- Nunca exponer `password_hash` en respuestas de API

---

## 🔐 Roles del sistema y sus permisos típicos

Los roles vienen precargados en la BD. Sus permisos se configuran en `rol_permisos`.

| Rol | Módulos principales | Restricciones |
|-----|---------------------|---------------|
| **Administrador** | Todo (`es_superadmin=TRUE`) | Ninguna |
| **Gerente** | Todos menos configurar roles | No puede asignar permisos |
| **Cajero** | `ventas`, `turnos`, `clientes` | Solo leer inventario y menús |
| **Almacenista** | `inventario`, `compras`, `proveedores` | No accede a ventas ni contabilidad |
| **Contador** | `contabilidad`, `reportes` (solo lectura) | No puede crear ni editar |

---

## 🧠 Lógica de negocio crítica — siempre implementar así

### Venta completa (flujo paso a paso)
1. Verificar que existe un turno `abierto` para el `usuario_id`
2. Validar stock disponible por producto/menú (sumando `lotes_inventario.cantidad_disponible`)
3. Si el ítem es un `menu`, descontar cada ingrediente de `menu_ingredientes` por FIFO
4. Si el ítem es un `producto` directo, descontar de `lotes_inventario` por FIFO
5. Registrar cada lote consumido en `detalle_venta_lotes`
6. Guardar `costo_fifo` en `detalle_venta` y `costo_total` en `ventas`
7. Actualizar `productos.stock_actual`
8. Si `cliente_id` presente, sumar puntos en `clientes.puntos_acumulados`
9. Todo en una sola transacción MySQL

### Cierre de turno
1. Calcular `total_ventas_esperado` = suma de todas las ventas del turno
2. Calcular `total_gastos_turno` = suma de gastos operativos del turno
3. Registrar corte físico en `cortes_caja` (billetes contados)
4. Calcular `diferencia_caja` (columna GENERATED)
5. Cambiar `estado` del turno a `cerrado`
6. Si es el último turno del día, crear/actualizar `cierres_diarios`

### Cierre diario
1. Agregar todos los turnos del día hacia `cierres_diarios`
2. Generar `balance_diario` con CMV (costo de mercancía vendida real por FIFO)
3. Crear asientos contables automáticos para ingresos y egresos del día
4. Actualizar snapshots en `reporte_ventas_producto` y `reporte_mensual`

---

## ⚠️ Patrones a EVITAR

- ❌ No usar `DELETE` en ventas, turnos ni movimientos de inventario — usar soft delete o estado `cancelada/anulado`
- ❌ No calcular totales en el frontend — siempre calcular en backend y guardar en BD
- ❌ No modificar `lotes_inventario.cantidad_disponible` directamente sin registrar en `movimientos_inventario`
- ❌ No saltarse la verificación de permisos aunque el endpoint "parezca poco sensible"
- ❌ No hacer operaciones de inventario fuera de transacciones (riesgo de stock negativo)
- ❌ No exponer el endpoint de reportes sin caché — usar los snapshots de `reporte_*`

---

## 🚀 Comandos del proyecto

```bash
# Instalar dependencias
npm install

# Levantar BD con Docker
docker-compose up -d mysql

# Ejecutar script de BD
mysql -u root -p cafeteria_db < database/DB_cafeteria_COMPLETA.sql

# Ejecutar seeds de prueba
npm run seed

# Desarrollo backend
npm run dev:backend

# Desarrollo frontend
npm run dev:frontend

# Tests
npm run test
npm run test:coverage

# Lint
npm run lint
npm run lint:fix
```

---

## 📋 Variables de entorno requeridas

```env
# Base de datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cafeteria_db
DB_USER=cafeteria_user
DB_PASSWORD=

# JWT
JWT_SECRET=
JWT_EXPIRES_IN=8h

# App
PORT=3000
NODE_ENV=development

# Logs
LOG_LEVEL=info
```

---

## 🧪 Estrategia de testing

- **Unitarios**: servicios FIFO, cálculo de cierres de caja, validación de permisos
- **Integración**: flujo completo de venta, flujo de turno, flujo de compra→lote
- **E2E**: login → venta → cierre turno → cierre diario
- Cobertura mínima esperada: 70% en módulos de inventario y ventas

---

## 📎 Archivos de referencia clave

| Archivo | Propósito |
|---------|-----------|
| `database/DB_cafeteria_COMPLETA.sql` | Esquema completo con seeds iniciales |
| `backend/src/modules/inventario/fifo.service.js` | Lógica central FIFO |
| `backend/src/middleware/permisos.middleware.js` | Guard de permisos por rol |
| `backend/src/modules/turnos/cierre.service.js` | Lógica de cierres y consolidación |
| `REVIEW.md` | Criterios de revisión de código para `/code-review` |

---

## 💬 Instrucciones para Claude Code

- Cuando generes migraciones o queries, usa la nomenclatura exacta de las tablas del esquema
- Si detectas una operación de inventario, pregunta si debe aplicar FIFO antes de implementar
- Para cualquier endpoint nuevo, siempre añadir el guard de permisos correspondiente
- Al generar tests, incluir casos de borde: stock en 0, turno ya cerrado, lote vencido
- Usar `@database/DB_cafeteria_COMPLETA.sql` como referencia de esquema al generar queries
- Si hay duda entre rendimiento y consistencia de datos → siempre priorizar consistencia
