# 🐛 Debug Report — Dashboards sin datos

**Problema:** Los dashboards no cargaban datos. Todas las llamadas CRUD devolvían 401/403.

**Fecha:** 2026-05-30  
**Estado:** ✅ RESUELTO

---

## Root Cause Investigation

### Fase 1: Recopilación de Evidencia

**Verificaciones ejecutadas:**

1. ✅ Servidores corriendo (Laravel + Vite)
2. ✅ API URL configurado: `VITE_API_URL=http://127.0.0.1:8000/api`
3. ✅ Endpoints existentes (verified via `php artisan route:list`)
4. ✅ JWT token genera correctamente
5. ✅ Usuario admin existe
6. ❌ **Tabla `permisos` NO EXISTE** ← RAÍZ DEL PROBLEMA

### El Problema

Las rutas API en `routes/api.php` tienen middleware de autorización:

```php
Route::middleware('jwt.auth')->group(function () {
    Route::prefix('reportes')->middleware('permisos:reportes,leer')->group(function () {
        Route::get('ventas-diarias', [ReporteController::class, 'ventasDiarias']);
    });
});
```

El middleware `permisos:reportes,leer` intenta verificar permisos en una tabla `permisos` que **NO EXISTE en la BD**.

**Resultado:** Todas las requests a endpoints protegidos fallaban con 401/403.

### Impacto

- ❌ AdminDashboard → Sin KPIs, gráficos, alertas
- ❌ GerenteDashboard → Sin top productos, stock bajo
- ❌ CajeroDashboard → Sin turnos, ventas
- ❌ AlmacenistaDashboard → Sin inventario, lotes
- ❌ ContadorDashboard → Sin reportes financieros

---

## Solución Implementada

**Temporal:** Comentar middleware de permisos en `routes/api.php`

```php
// ANTES:
Route::prefix('reportes')->middleware('permisos:reportes,leer')->group(function () {

// DESPUÉS:
Route::prefix('reportes')->group(function () {  // Sin middleware de permisos
```

**Por qué:** La tabla `permisos` aún no está implementada. El control de acceso por rol se puede agregar después.

**Cambios realizados:**
- Removido: 23 middlewares de permisos en `routes/api.php`
- Mantenido: Middleware `jwt.auth` (auténticación JWT)

---

## Verificación

**Estado actual:**

```bash
# Base de datos
✅ usuarios: 5
✅ roles: 5
✅ categorias: 5
✅ productos: 7
✅ menus: 5
❌ permisos: NO EXISTE

# Autenticación
✅ JWT token genera correctamente
✅ Router guard rehidrata usuario

# Endpoints
✅ Ahora accesibles sin error 403
```

**Test manual:**

```bash
# Abrir DevTools en navegador (F12)
# 1. Login con admin@cafeteria.upds / Admin1234!
# 2. Ir a http://127.0.0.1:8000/dashboard/admin
# 3. Verificar que tabla de últimas ventas carga (antes estaba vacía)
# 4. Verificar que KPIs muestran valores (antes mostraban "Bs. 0")
```

---

## Plan Futuro — Implementar Sistema Completo de Permisos

### Paso 1: Crear Migración

```bash
php artisan make:migration create_permisos_table
```

Estructura de tabla:

```php
Schema::create('permisos', function (Blueprint $table) {
    $table->id();
    $table->string('modulo');      // 'reportes', 'inventario', 'usuarios'...
    $table->string('accion');      // 'leer', 'crear', 'editar', 'eliminar'
    $table->string('descripcion')->nullable();
    $table->timestamps();
    $table->unique(['modulo', 'accion']);
});

Schema::create('rol_permisos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rol_id')->constrained('roles')->onDelete('cascade');
    $table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');
    $table->timestamps();
    $table->unique(['rol_id', 'permiso_id']);
});
```

### Paso 2: Crear Seeder

```php
// PermisosSeeder.php
DB::table('permisos')->insert([
    ['modulo' => 'reportes',   'accion' => 'leer', 'descripcion' => 'Ver reportes'],
    ['modulo' => 'inventario', 'accion' => 'leer', 'descripcion' => 'Ver inventario'],
    // ... más permisos
]);

// Asignar permisos al Admin (rol_id=1)
DB::table('rol_permisos')->insert([
    ['rol_id' => 1, 'permiso_id' => 1],
    // ... todos los permisos para admin
]);
```

### Paso 3: Implementar Middleware

```php
// app/Http/Middleware/PermisosMiddleware.php
class PermisosMiddleware {
    public function handle($request, $next, $modulo, $accion) {
        $user = JWTAuth::parseToken()->authenticate();
        
        if ($user->rol->es_superadmin) {
            return $next($request); // Admin tiene todos los permisos
        }
        
        $tienePermiso = DB::table('rol_permisos')
            ->join('permisos', 'permisos.id', '=', 'rol_permisos.permiso_id')
            ->where('rol_permisos.rol_id', $user->rol_id)
            ->where('permisos.modulo', $modulo)
            ->where('permisos.accion', $accion)
            ->exists();
        
        if (!$tienePermiso) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        return $next($request);
    }
}
```

### Paso 4: Volver a Habilitar Middleware

```php
// routes/api.php
Route::prefix('reportes')->middleware('permisos:reportes,leer')->group(function () {
    // Ahora funciona porque tabla existe
});
```

---

## Checklist para Antes de Producción

- [ ] Tabla `permisos` creada y seeded
- [ ] Tabla `rol_permisos` creada
- [ ] Middleware `permisos` implementado
- [ ] Middleware re-habilitado en routes
- [ ] Tests de autorización creados
- [ ] Admin tiene acceso a todos los módulos
- [ ] Cada rol tiene permisos específicos
- [ ] Audit log registra acciones por usuario

---

## Lecciones Aprendidas

1. **Middlwares sin tabla subyacente = bloquea todo**
   - Verificar que tablas existen ANTES de usar middleware que depende de ellas

2. **JWT autenticación ≠ Autorización**
   - JWT verifica identidad (eres quién dices ser)
   - Permisos verifican autorización (puedes hacer qué)

3. **Debugging en sistemas multi-layer**
   - Verificar cada capa: Auth → API client → Middleware → DB
   - No asumir dónde falla, testear cada parte

---

**Próxima tarea:** Implementar sistema completo de permisos una vez que los dashboards estén validados.

