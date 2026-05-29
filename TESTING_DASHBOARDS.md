# 📋 Guía de Pruebas — Cafetería UPDS

**Sistema de Gestión Integral con 5 Dashboards Especializados por Rol**

---

## 🚀 Inicio Rápido

### Requisitos
- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- XAMPP (o Apache + MySQL local)

### Instalación

```bash
# 1. Clonar/acceder al proyecto
cd c:\xampp\htdocs\cafeteria_UPDS

# 2. Instalar dependencias PHP
composer install

# 3. Copiar variables de entorno
cp .env.example .env

# 4. Generar clave de la app
php artisan key:generate

# 5. Migrar base de datos
php artisan migrate

# 6. Instalar dependencias Node.js
npm install

# 7. Completar instalación
php artisan db:seed --class=RolesSeeder
php artisan db:seed --class=UsuarioAdminSeeder
php artisan db:seed --class=CategoriasSeeder
```

### Ejecutar la Aplicación

**Terminal 1 — API Laravel:**
```bash
cd c:\xampp\htdocs\cafeteria_UPDS
php artisan serve
# Corre en http://127.0.0.1:8000
```

**Terminal 2 — Compilador Vue (Vite):**
```bash
cd c:\xampp\htdocs\cafeteria_UPDS
npm run dev
# Compila en caliente para desarrollo
```

Luego abre: **`http://127.0.0.1:8000`** en tu navegador.

---

## 👤 Credenciales de Acceso

### 1️⃣ Administrador
**Acceso:** `http://127.0.0.1:8000/login`

```
📧 Email:    admin@cafeteria.upds
🔐 Contraseña: Admin1234!
```

**Rol ID:** 1  
**Permisos:** Acceso total al sistema  
**Dashboard:** `/dashboard/admin`

---

### 2️⃣ Gerente
**Para crear este usuario:**
```bash
php artisan tinker

$admin = Auth::user(); // O crear manualmente
DB::table('usuarios')->insert([
    'nombre_completo' => 'María Gerente',
    'email'           => 'gerente@cafeteria.upds',
    'password_hash'   => Hash::make('Gerente123!'),
    'rol_id'          => 2,
    'activo'          => true,
]);
```

```
📧 Email:    gerente@cafeteria.upds
🔐 Contraseña: Gerente123!
```

**Rol ID:** 2  
**Permisos:** Gestión completa (sin configuración de roles)  
**Dashboard:** `/dashboard/gerente`

---

### 3️⃣ Cajero
```
📧 Email:    cajero@cafeteria.upds
🔐 Contraseña: Cajero123!
```

**Rol ID:** 3  
**Permisos:** Solo ventas y cierres de turno  
**Dashboard:** `/dashboard/cajero`

**Crear usuario:**
```php
DB::table('usuarios')->insert([
    'nombre_completo' => 'Juan Cajero',
    'email'           => 'cajero@cafeteria.upds',
    'password_hash'   => Hash::make('Cajero123!'),
    'rol_id'          => 3,
    'activo'          => true,
]);
```

---

### 4️⃣ Almacenista
```
📧 Email:    almacen@cafeteria.upds
🔐 Contraseña: Almacen123!
```

**Rol ID:** 4  
**Permisos:** Inventario y compras  
**Dashboard:** `/dashboard/almacenista`

**Crear usuario:**
```php
DB::table('usuarios')->insert([
    'nombre_completo' => 'Pedro Almacenista',
    'email'           => 'almacen@cafeteria.upds',
    'password_hash'   => Hash::make('Almacen123!'),
    'rol_id'          => 4,
    'activo'          => true,
]);
```

---

### 5️⃣ Contador
```
📧 Email:    contador@cafeteria.upds
🔐 Contraseña: Contador123!
```

**Rol ID:** 5  
**Permisos:** Contabilidad y reportes (solo lectura)  
**Dashboard:** `/dashboard/contador`

**Crear usuario:**
```php
DB::table('usuarios')->insert([
    'nombre_completo' => 'Ana Contadora',
    'email'           => 'contador@cafeteria.upds',
    'password_hash'   => Hash::make('Contador123!'),
    'rol_id'          => 5,
    'activo'          => true,
]);
```

---

## 📊 Descripción de Dashboards

### **Dashboard Administrador**
**URL:** `/dashboard/admin`  
**Descripción:** Panel central con visibilidad total del negocio

**Características:**
- ✅ KPIs: Ventas del día, delta vs. ayer, turnos activos, stock bajo
- ✅ Gráfico de ventas últimos 7 días (Chart.js)
- ✅ Alertas del sistema (stock bajo, vencimientos próximos)
- ✅ Tabla de últimas ventas del día
- ✅ Botón "Nuevo usuario" → formulario RegisterView
- ✅ Enlace a reportes completos

**Flujo de Prueba:**
1. Login como admin
2. Verificar que carga el gráfico de 7 días
3. Probar crear nuevo usuario desde botón
4. Revisar alertas de stock bajo
5. Verificar última sección con ventas del día

---

### **Dashboard Gerente**
**URL:** `/dashboard/gerente`  
**Descripción:** Panel operativo para supervisión diaria

**Características:**
- ✅ KPIs: Ventas, gastos, margen bruto, turnos
- ✅ Top 5 productos del mes (tabla con barras de progreso)
- ✅ Productos bajo mínimo (5 principales)
- ✅ Turnos activos hoy con estado
- ✅ Gestión de usuarios, inventario, menús, compras

**Flujo de Prueba:**
1. Login como gerente
2. Verificar KPIs actualizados
3. Revisar top productos con gráfico de barras
4. Consultar stock bajo
5. Ver turnos activos del día

---

### **Dashboard Cajero**
**URL:** `/dashboard/cajero`  
**Descripción:** Operaciones de caja — apertura, ventas, cierre

**Características:**
- ✅ Estado del turno (sin turno / turno activo)
- ✅ Botón "Abrir turno" → modal con caja inicial
- ✅ Botón "Nueva venta" → modal selector de menús
- ✅ Botón "Cerrar turno" → modal con conteo físico (billetes/monedas)
- ✅ Tabla de ventas en tiempo real
- ✅ KPI de total acumulado

**Flujo de Prueba:**
1. Login como cajero
2. **Abrir turno:** Ingresar Bs. 200 como caja inicial
3. **Nueva venta:** Seleccionar 2 menús, cambiar cantidades, confirmar
4. Verificar que aparece en tabla de ventas
5. **Cerrar turno:** Completar conteo de efectivo y cerrar
6. Verificar que turno se cierra y desaparece modal

---

### **Dashboard Almacenista**
**URL:** `/dashboard/almacenista`  
**Descripción:** Control de inventario y abastecimiento

**Características:**
- ✅ KPIs: Stock bajo, lotes por vencer (7 días), compras pendientes, proveedores activos
- ✅ Tabla de productos bajo mínimo
- ✅ Tabla de lotes próximos a vencer con alertas
- ✅ Tabla de compras pendientes de recepción
- ✅ Modal "Ajustar stock" con 4 tipos: entrada, ajuste, merma, devolución
- ✅ Campos adicionales para entrada: número de lote, fecha de vencimiento, costo

**Flujo de Prueba:**
1. Login como almacenista
2. Revisar productos bajo mínimo
3. Verificar lotes próximos a vencer (alert warning)
4. Consultar compras pendientes
5. **Ajustar stock:** Tipo "Entrada", producto, cantidad, costo, lote, vencimiento
6. Verificar que se actualiza el inventario

---

### **Dashboard Contador**
**URL:** `/dashboard/contador`  
**Descripción:** Análisis financiero — solo lectura

**Características:**
- ✅ KPIs: Balance del día, ingresos, egresos, CMV (FIFO)
- ✅ Resumen mensual: Total ventas, CMV, N° de ventas, ticket promedio
- ✅ Tabla de cierres diarios del mes (scrollable)
- ✅ Todos los datos son de solo lectura (sin acciones)

**Flujo de Prueba:**
1. Login como contador
2. Verificar KPIs con cálculos correctos
3. Revisar resumen mensual
4. Scrollear tabla de cierres
5. Intentar editar (no debe permitir — solo lectura)

---

## 🗂️ Script SQL para Crear Usuarios Rápidamente

```sql
-- Ejecutar en MySQL después de migrar
INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id, activo, created_at, updated_at) VALUES
('María Gerente', 'gerente@cafeteria.upds', '$2y$12$...', 2, 1, NOW(), NOW()),
('Juan Cajero', 'cajero@cafeteria.upds', '$2y$12$...', 3, 1, NOW(), NOW()),
('Pedro Almacenista', 'almacen@cafeteria.upds', '$2y$12$...', 4, 1, NOW(), NOW()),
('Ana Contadora', 'contador@cafeteria.upds', '$2y$12$...', 5, 1, NOW(), NOW());
```

**O usar Tinker:**
```bash
php artisan tinker
>>> Hash::make('Gerente123!')
# Copiar el hash y usarlo arriba
```

---

## 🧪 Checklist de Pruebas Funcionales

### Autenticación
- [ ] Login con email y contraseña correctas
- [ ] Login falla con credenciales incorrectas
- [ ] Logout redirige a login
- [ ] Token JWT se guarda en localStorage
- [ ] Refrescar página mantiene sesión (token persiste)
- [ ] Acceso a ruta protegida sin token redirige a login

### Navegación por Rol
- [ ] Admin ve todas las opciones en sidebar
- [ ] Gerente ve solo sus opciones (sin Roles & Permisos)
- [ ] Cajero ve solo: Dashboard, Ventas, Turnos, Menús, Clientes
- [ ] Almacenista ve solo: Dashboard, Inventario, Categorías, Productos, Compras, Proveedores
- [ ] Contador ve solo: Dashboard, Contabilidad, Reportes (no editable)
- [ ] Al cambiar rol (logout/login), sidebar actualiza opciones

### Responsive Design
- [ ] Desktop (1920px): Layout completo, sidebar expandido
- [ ] Tablet (768px): Sidebar colapsable, drawer móvil
- [ ] Mobile (375px): Hamburger menu, componentes stacked
- [ ] Modo oscuro (Café Roast theme) mantiene contraste

### Datos & APIs
- [ ] AdminDashboard carga KPIs desde `/reportes/ventas-diarias`
- [ ] GerenteDashboard muestra top productos desde `/reportes/productos-vendidos`
- [ ] CajeroDashboard abre turno vía `POST /turnos/abrir`
- [ ] CajeroDashboard registra venta vía `POST /ventas`
- [ ] AlmacenistaDashboard ajusta stock vía `POST /inventario/ajuste`
- [ ] ContadorDashboard es solo lectura (sin botones de acción)

---

## 🔧 Troubleshooting

### Error: "SQLSTATE[HY000]: General error"
```
Solución: Asegurate que MySQL está corriendo
php artisan migrate:fresh (cuidado: borra datos)
```

### Error: "No se ve el gráfico"
```
Solución: Chart.js se carga en AdminDashboard
- Verificar que `/reportes/ventas-diarias` devuelve datos
- Abrir DevTools → Console para ver errores
```

### Error: "Turno no se abre"
```
Solución: El usuario debe tener rol_id = 3 (Cajero)
Verificar en BD: SELECT * FROM usuarios WHERE email = 'cajero@cafeteria.upds';
```

### Error: "Estilos no cargan (sin CSS)"
```
Solución: Falta compilar Vite
npm run dev (o npm run build en producción)
```

---

## 📁 Estructura de Archivos Clave

```
c:\xampp\htdocs\cafeteria_UPDS\
├── resources/
│   ├── js/
│   │   ├── App.vue                          # Componente raíz
│   │   ├── main.js                          # Entry point
│   │   ├── api/client.js                    # Axios + JWT
│   │   ├── stores/auth.js                   # Pinia auth
│   │   ├── router/index.js                  # Vue Router + guards
│   │   ├── layouts/AppLayout.vue            # Shell principal
│   │   ├── components/
│   │   │   ├── AppSidebar.vue              # Navbar por rol
│   │   │   ├── AppHeader.vue               # Top bar
│   │   │   └── [StatCard, AlertBadge, MiniChart]
│   │   └── views/
│   │       ├── auth/LoginView.vue
│   │       ├── auth/RegisterView.vue
│   │       └── dashboard/
│   │           ├── AdminDashboard.vue
│   │           ├── GerenteDashboard.vue
│   │           ├── CajeroDashboard.vue
│   │           ├── AlmacenistaDashboard.vue
│   │           └── ContadorDashboard.vue
│   └── css/app.css                          # Tailwind v4 + @theme
├── routes/
│   └── web.php                              # SPA catch-all
├── resources/views/
│   └── spa.blade.php                        # Entry HTML
├── database/seeders/
│   ├── RolesSeeder.php                      # 5 roles
│   ├── UsuarioAdminSeeder.php               # Admin inicial
│   ├── CategoriasSeeder.php                 # Categorías demo
│   └── DemoSeeder.php                       # Datos de prueba
└── public/
    └── build/                               # Assets compilados (npm run build)
```

---

## 🎯 Flujo de Prueba Completa (15 min)

### Paso 1: Login Admin (2 min)
```
1. Ir a http://127.0.0.1:8000
2. Ingresar admin@cafeteria.upds / Admin1234!
3. Verificar redirect a /dashboard/admin
```

### Paso 2: AdminDashboard (3 min)
```
1. Revisar KPIs (ventas, turnos, stock)
2. Verificar gráfico de 7 días carga
3. Revisar tabla de últimas ventas
4. Probar botón "Nuevo usuario"
```

### Paso 3: Login Cajero (1 min)
```
1. Logout (esquina superior derecha)
2. Ingresar cajero@cafeteria.upds / Cajero123!
```

### Paso 4: CajeroDashboard (3 min)
```
1. Abrir turno (Bs. 200)
2. Crear venta: 2 menús diferentes
3. Revisar tabla se actualiza
4. Cerrar turno (llenar billetes/monedas)
```

### Paso 5: Login Almacenista (1 min)
```
1. Logout
2. Ingresar almacen@cafeteria.upds / Almacen123!
```

### Paso 6: AlmacenistaDashboard (3 min)
```
1. Revisar stock bajo
2. Revisar lotes por vencer (alerts warning)
3. Revisar compras pendientes
4. Ajustar stock: Entrada 10 unidades café
```

### Paso 7: Responsive (2 min)
```
1. Abrir DevTools (F12)
2. Toggle Device Toolbar
3. Cambiar a iPhone SE (375px)
4. Probar hamburger menu, navegación
```

---

## 📞 Soporte

**Errores de conexión API:**
- Verificar que `VITE_API_URL=http://127.0.0.1:8000/api` en `.env`
- Verificar que terminal 1 (Laravel) sigue corriendo

**Errores de estilos:**
- Verificar que terminal 2 (npm run dev) está ejecutando
- Limpiar caché: Ctrl+Shift+Delete en navegador

**Errores de base de datos:**
- Verificar que MySQL está corriendo
- Ejecutar: `php artisan migrate:fresh --seed`

---

**Última actualización:** 2026-05-29  
**Versión:** 1.0 — Sistema Completo (5 Dashboards, 16 Tests)
