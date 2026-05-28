-- ==========================================
-- ☕ SISTEMA DE GESTIÓN CAFETERÍA — BD COMPLETA v2.0
-- ==========================================
-- Incluye: FIFO, Roles granulares, Menús, Turnos+Cierres Diarios,
--          Contabilidad diaria, Reportes, Módulos completos
-- Compatible con MySQL 8+ / MariaDB 10.5+
-- ==========================================

CREATE DATABASE IF NOT EXISTS cafeteria_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE cafeteria_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- 🔐 MÓDULO 1: ROLES Y PERMISOS GRANULARES
-- ==========================================
-- Los permisos se definen por módulo+acción en tabla separada (no como texto)

CREATE TABLE modulos_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) UNIQUE NOT NULL COMMENT 'ej: ventas, inventario, reportes',
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE acciones_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) UNIQUE NOT NULL COMMENT 'ej: crear, leer, editar, eliminar, exportar',
    nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion TEXT,
    es_superadmin BOOLEAN DEFAULT FALSE COMMENT 'Si TRUE, bypasea todos los permisos',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla pivote: qué acciones puede hacer cada rol en cada módulo
CREATE TABLE rol_permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    modulo_id INT NOT NULL,
    accion_id INT NOT NULL,
    UNIQUE KEY uq_rol_modulo_accion (rol_id, modulo_id, accion_id),
    CONSTRAINT fk_rp_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_modulo FOREIGN KEY (modulo_id) REFERENCES modulos_sistema(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_accion FOREIGN KEY (accion_id) REFERENCES acciones_sistema(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 👥 MÓDULO 2: USUARIOS
-- ==========================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    ultimo_login TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log de actividad de usuarios (auditoría)
CREATE TABLE log_actividad (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL COMMENT 'ej: LOGIN, CREAR_VENTA, EDITAR_PRODUCTO',
    modulo VARCHAR(50),
    descripcion TEXT,
    ip_address VARCHAR(45),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 🏷️ MÓDULO 3: CATÁLOGOS BASE
-- ==========================================

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    aplica_a ENUM('producto', 'menu', 'ambos') DEFAULT 'ambos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(20),
    puntos_acumulados INT DEFAULT 0,
    puntos_canjeados INT DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(100) NOT NULL,
    contacto_nombre VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(20),
    direccion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 📦 MÓDULO 4: PRODUCTOS E INVENTARIO FIFO
-- ==========================================

CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(50) UNIQUE COMMENT 'SKU o código de barras',
    categoria_id INT NOT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL COMMENT 'Costo promedio actualizado',
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 5,
    unidad_medida VARCHAR(20) DEFAULT 'unidad',
    requiere_lote BOOLEAN DEFAULT TRUE COMMENT 'Si FALSE, no aplica FIFO (ej: servicio)',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- *** NÚCLEO FIFO: Lotes de inventario ***
-- Cada compra o entrada crea un lote. Las salidas consumen el lote más antiguo primero.
CREATE TABLE lotes_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    compra_id INT NULL COMMENT 'Referencia a la compra que originó este lote',
    numero_lote VARCHAR(50) COMMENT 'Número de lote del proveedor (opcional)',
    fecha_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de ingreso al sistema — ordena el FIFO',
    fecha_vencimiento DATE NULL COMMENT 'Para alertas de caducidad',
    cantidad_inicial INT NOT NULL,
    cantidad_disponible INT NOT NULL COMMENT 'Decrece con cada salida FIFO',
    costo_unitario DECIMAL(10,2) NOT NULL COMMENT 'Costo real de este lote específico',
    estado ENUM('disponible', 'agotado', 'vencido') DEFAULT 'disponible',
    CONSTRAINT fk_lote_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Índice para consultas FIFO eficientes (ordenar por fecha_entrada ASC)
CREATE INDEX idx_lote_fifo ON lotes_inventario (producto_id, estado, fecha_entrada ASC);

-- Movimientos de inventario — ahora vinculados a lotes
CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    lote_id INT NULL COMMENT 'NULL solo si el producto no requiere lote',
    tipo ENUM('entrada', 'salida', 'ajuste', 'merma', 'devolucion') NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(10,2) COMMENT 'Costo del lote en movimientos de salida FIFO',
    motivo VARCHAR(255),
    usuario_id INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    referencia_tipo VARCHAR(50) COMMENT 'venta / compra / ajuste_manual',
    referencia_id INT COMMENT 'ID de la venta o compra relacionada',
    CONSTRAINT fk_mov_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mov_lote FOREIGN KEY (lote_id) REFERENCES lotes_inventario(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 🍽️ MÓDULO 5: MENÚS Y COMBOS
-- ==========================================

CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria_id INT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    imagen_url VARCHAR(255),
    disponible_desde TIME DEFAULT '06:00:00' COMMENT 'Disponibilidad horaria inicio',
    disponible_hasta TIME DEFAULT '22:00:00' COMMENT 'Disponibilidad horaria fin',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un menú se compone de uno o varios productos (receta)
CREATE TABLE menu_ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad DECIMAL(10,3) NOT NULL COMMENT 'Cantidad del insumo para preparar 1 unidad del menú',
    unidad_medida VARCHAR(20),
    UNIQUE KEY uq_menu_producto (menu_id, producto_id),
    CONSTRAINT fk_mi_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    CONSTRAINT fk_mi_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 🔄 MÓDULO 6: TURNOS Y CIERRES DE CAJA
-- ==========================================

CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE COMMENT 'T-20240115-001 — generado por app',
    usuario_apertura INT NOT NULL,
    usuario_cierre INT NULL,
    fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    caja_inicial DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_ventas_esperado DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Calculado al cerrar',
    total_gastos_turno DECIMAL(10,2) DEFAULT 0.00,
    caja_final_esperada DECIMAL(10,2) DEFAULT 0.00,
    caja_final_real DECIMAL(10,2) DEFAULT 0.00,
    diferencia_caja DECIMAL(10,2) GENERATED ALWAYS AS (caja_final_real - caja_final_esperada) STORED,
    observaciones TEXT,
    estado ENUM('abierto', 'en_corte', 'cerrado') DEFAULT 'abierto',
    cierre_diario_id INT NULL COMMENT 'FK asignada después de crear cierre_diario',
    CONSTRAINT fk_turnos_apertura FOREIGN KEY (usuario_apertura) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_turnos_cierre FOREIGN KEY (usuario_cierre) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Corte de caja por turno (detalle de efectivo contado)
CREATE TABLE cortes_caja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NOT NULL UNIQUE,
    usuario_id INT NOT NULL,
    fecha_corte TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Desglose billetes/monedas (opcional, útil para cuadrar caja)
    billetes_200 INT DEFAULT 0,
    billetes_100 INT DEFAULT 0,
    billetes_50 INT DEFAULT 0,
    billetes_20 INT DEFAULT 0,
    billetes_10 INT DEFAULT 0,
    monedas_total DECIMAL(10,2) DEFAULT 0.00,
    total_efectivo_contado DECIMAL(10,2) NOT NULL,
    total_tarjeta DECIMAL(10,2) DEFAULT 0.00,
    total_transferencia DECIMAL(10,2) DEFAULT 0.00,
    total_real DECIMAL(10,2) NOT NULL,
    diferencia DECIMAL(10,2) GENERATED ALWAYS AS (total_real - 0) STORED,
    observaciones TEXT,
    CONSTRAINT fk_corte_turno FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_corte_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cierre diario — consolida todos los turnos del día
CREATE TABLE cierres_diarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    usuario_id INT NOT NULL COMMENT 'Quien realiza el cierre diario (administrador)',
    total_ventas DECIMAL(10,2) DEFAULT 0.00,
    total_ventas_efectivo DECIMAL(10,2) DEFAULT 0.00,
    total_ventas_tarjeta DECIMAL(10,2) DEFAULT 0.00,
    total_ventas_transferencia DECIMAL(10,2) DEFAULT 0.00,
    total_descuentos DECIMAL(10,2) DEFAULT 0.00,
    total_impuestos DECIMAL(10,2) DEFAULT 0.00,
    total_compras DECIMAL(10,2) DEFAULT 0.00,
    total_gastos_operativos DECIMAL(10,2) DEFAULT 0.00,
    utilidad_bruta DECIMAL(10,2) GENERATED ALWAYS AS (total_ventas - total_compras) STORED,
    utilidad_neta DECIMAL(10,2) GENERATED ALWAYS AS (total_ventas - total_compras - total_gastos_operativos) STORED,
    num_ventas INT DEFAULT 0,
    num_turnos INT DEFAULT 0,
    observaciones TEXT,
    estado ENUM('borrador', 'cerrado') DEFAULT 'borrador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cd_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar FK de turnos → cierre_diario (ya que cierre_diario se crea después)
ALTER TABLE turnos
    ADD CONSTRAINT fk_turno_cierre_diario
    FOREIGN KEY (cierre_diario_id) REFERENCES cierres_diarios(id) ON DELETE SET NULL;

-- ==========================================
-- 🛒 MÓDULO 7: VENTAS
-- ==========================================

CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NOT NULL,
    usuario_id INT NOT NULL,
    cliente_id INT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descuento DECIMAL(10,2) DEFAULT 0.00,
    impuesto DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    costo_total DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Suma del costo FIFO de los ítems vendidos',
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'mixto') NOT NULL,
    estado ENUM('completada', 'cancelada', 'pendiente') DEFAULT 'completada',
    nota TEXT,
    CONSTRAINT fk_ventas_turno FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalle de venta: puede ser producto directo O menú
CREATE TABLE detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    -- Tipo de ítem vendido
    tipo_item ENUM('producto', 'menu') NOT NULL DEFAULT 'producto',
    producto_id INT NULL,
    menu_id INT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento_item DECIMAL(10,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL,
    costo_fifo DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Costo real calculado por FIFO al momento de la venta',
    CONSTRAINT fk_dv_venta FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_dv_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_dv_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro FIFO de qué lote se consumió en cada ítem vendido
CREATE TABLE detalle_venta_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detalle_venta_id INT NOT NULL,
    lote_id INT NOT NULL,
    cantidad_consumida INT NOT NULL,
    costo_unitario_lote DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_dvl_detalle FOREIGN KEY (detalle_venta_id) REFERENCES detalle_venta(id) ON DELETE CASCADE,
    CONSTRAINT fk_dvl_lote FOREIGN KEY (lote_id) REFERENCES lotes_inventario(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 🚚 MÓDULO 8: COMPRAS
-- ==========================================

CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE COMMENT 'OC-20240115-001',
    proveedor_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_orden TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_recepcion TIMESTAMP NULL,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    impuesto DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) DEFAULT 0.00,
    estado ENUM('pendiente', 'recibida', 'parcial', 'cancelada') DEFAULT 'pendiente',
    nota TEXT,
    CONSTRAINT fk_compras_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE RESTRICT,
    CONSTRAINT fk_compras_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE detalle_compra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compra_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad_ordenada INT NOT NULL,
    cantidad_recibida INT DEFAULT 0,
    costo_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    -- Al recibir, se crea automáticamente un lote_inventario con esta info
    lote_generado_id INT NULL COMMENT 'ID del lote FIFO creado al recibir este ítem',
    CONSTRAINT fk_dc_compra FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
    CONSTRAINT fk_dc_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_dc_lote FOREIGN KEY (lote_generado_id) REFERENCES lotes_inventario(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 💸 MÓDULO 9: GASTOS OPERATIVOS
-- ==========================================

CREATE TABLE gastos_operativos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NULL,
    categoria ENUM('servicios', 'mantenimiento', 'insumos', 'nomina', 'impuestos', 'otros') NOT NULL,
    descripcion TEXT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    comprobante_url VARCHAR(255),
    usuario_id INT NOT NULL,
    CONSTRAINT fk_gastos_turno FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE SET NULL,
    CONSTRAINT fk_gastos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 📊 MÓDULO 10: CONTABILIDAD DIARIA
-- ==========================================

-- Catálogo de cuentas contables simplificado
CREATE TABLE cuentas_contables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL COMMENT 'ej: 4100, 5100',
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('ingreso', 'egreso', 'activo', 'pasivo', 'capital') NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro diario de contabilidad (asientos)
CREATE TABLE asientos_contables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cierre_diario_id INT NULL COMMENT 'Vinculado al cierre del día',
    fecha DATE NOT NULL,
    numero_asiento VARCHAR(20) UNIQUE COMMENT 'AS-20240115-001',
    descripcion TEXT NOT NULL,
    usuario_id INT NOT NULL,
    estado ENUM('borrador', 'confirmado', 'anulado') DEFAULT 'borrador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asiento_cierre FOREIGN KEY (cierre_diario_id) REFERENCES cierres_diarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_asiento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Líneas del asiento (débito/crédito)
CREATE TABLE lineas_asiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asiento_id INT NOT NULL,
    cuenta_id INT NOT NULL,
    tipo ENUM('debito', 'credito') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    descripcion VARCHAR(255),
    CONSTRAINT fk_la_asiento FOREIGN KEY (asiento_id) REFERENCES asientos_contables(id) ON DELETE CASCADE,
    CONSTRAINT fk_la_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas_contables(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resumen contable diario (snapshot para reportes rápidos)
CREATE TABLE balance_diario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL UNIQUE,
    cierre_diario_id INT NULL,
    -- Ingresos
    ingresos_ventas DECIMAL(10,2) DEFAULT 0.00,
    otros_ingresos DECIMAL(10,2) DEFAULT 0.00,
    total_ingresos DECIMAL(10,2) DEFAULT 0.00,
    -- Costos y Gastos
    costo_mercancia_vendida DECIMAL(10,2) DEFAULT 0.00 COMMENT 'CMV calculado con FIFO',
    gastos_operativos DECIMAL(10,2) DEFAULT 0.00,
    gastos_nomina DECIMAL(10,2) DEFAULT 0.00,
    otros_gastos DECIMAL(10,2) DEFAULT 0.00,
    total_egresos DECIMAL(10,2) DEFAULT 0.00,
    -- Resultado
    utilidad_bruta DECIMAL(10,2) GENERATED ALWAYS AS (ingresos_ventas - costo_mercancia_vendida) STORED,
    utilidad_neta DECIMAL(10,2) GENERATED ALWAYS AS (total_ingresos - total_egresos) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bd_cierre FOREIGN KEY (cierre_diario_id) REFERENCES cierres_diarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 📈 MÓDULO 11: REPORTES (SNAPSHOTS)
-- ==========================================
-- Estas tablas guardan resultados pre-calculados para reportes rápidos
-- sin recalcular cada vez desde las tablas transaccionales.

-- Reporte de ventas por producto (diario)
CREATE TABLE reporte_ventas_producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    producto_id INT NULL,
    menu_id INT NULL,
    nombre_item VARCHAR(100),
    cantidad_vendida INT DEFAULT 0,
    ingresos_total DECIMAL(10,2) DEFAULT 0.00,
    costo_total_fifo DECIMAL(10,2) DEFAULT 0.00,
    margen_bruto DECIMAL(10,2) GENERATED ALWAYS AS (ingresos_total - costo_total_fifo) STORED,
    CONSTRAINT fk_rvp_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL,
    CONSTRAINT fk_rvp_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_rvp_fecha ON reporte_ventas_producto(fecha);

-- Reporte de stock actual con alertas
CREATE TABLE reporte_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    generado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    producto_id INT NOT NULL,
    stock_actual INT,
    stock_minimo INT,
    alerta_stock_bajo BOOLEAN DEFAULT FALSE,
    lotes_proximos_vencer INT DEFAULT 0 COMMENT 'Lotes que vencen en los próximos 7 días',
    valor_inventario DECIMAL(10,2) COMMENT 'stock_actual * costo_promedio',
    CONSTRAINT fk_rs_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reporte de rentabilidad mensual
CREATE TABLE reporte_mensual (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anio YEAR NOT NULL,
    mes TINYINT NOT NULL COMMENT '1-12',
    total_ventas DECIMAL(10,2) DEFAULT 0.00,
    total_costo_mercancia DECIMAL(10,2) DEFAULT 0.00,
    total_gastos_operativos DECIMAL(10,2) DEFAULT 0.00,
    utilidad_bruta DECIMAL(10,2) DEFAULT 0.00,
    utilidad_neta DECIMAL(10,2) DEFAULT 0.00,
    num_ventas INT DEFAULT 0,
    ticket_promedio DECIMAL(10,2) DEFAULT 0.00,
    producto_mas_vendido VARCHAR(100),
    generado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_anio_mes (anio, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 🌱 DATOS INICIALES (SEED)
-- ==========================================

-- Módulos del sistema
INSERT INTO modulos_sistema (clave, nombre) VALUES
('ventas',      'Punto de Venta'),
('inventario',  'Inventario y Stock'),
('compras',     'Compras a Proveedores'),
('menus',       'Gestión de Menús'),
('usuarios',    'Usuarios y Roles'),
('reportes',    'Reportes y Estadísticas'),
('contabilidad','Contabilidad Diaria'),
('turnos',      'Turnos y Cierres de Caja'),
('clientes',    'Clientes'),
('gastos',      'Gastos Operativos');

-- Acciones
INSERT INTO acciones_sistema (clave, nombre) VALUES
('crear',    'Crear / Registrar'),
('leer',     'Ver / Consultar'),
('editar',   'Editar / Actualizar'),
('eliminar', 'Eliminar'),
('exportar', 'Exportar / Imprimir'),
('aprobar',  'Aprobar / Confirmar');

-- Roles predefinidos
INSERT INTO roles (nombre, descripcion, es_superadmin) VALUES
('Administrador', 'Acceso total al sistema',            TRUE),
('Gerente',       'Gestión completa excepto configuración de roles', FALSE),
('Cajero',        'Solo ventas y cierres de turno',     FALSE),
('Almacenista',   'Inventario y compras',               FALSE),
('Contador',      'Contabilidad y reportes solo lectura', FALSE);

-- Cuentas contables básicas
INSERT INTO cuentas_contables (codigo, nombre, tipo) VALUES
('4100', 'Ingresos por Ventas',           'ingreso'),
('4200', 'Otros Ingresos',                'ingreso'),
('5100', 'Costo de Mercancía Vendida',    'egreso'),
('5200', 'Gastos de Operación',           'egreso'),
('5300', 'Gastos de Personal / Nómina',   'egreso'),
('5400', 'Gastos de Mantenimiento',       'egreso'),
('5500', 'Gastos de Servicios',           'egreso'),
('1100', 'Caja / Efectivo',               'activo'),
('1200', 'Inventario de Mercancías',      'activo'),
('2100', 'Cuentas por Pagar Proveedores', 'pasivo');

-- Categorías iniciales
INSERT INTO categorias (nombre, aplica_a) VALUES
('Bebidas Calientes',  'ambos'),
('Bebidas Frías',      'ambos'),
('Repostería',         'ambos'),
('Alimentos',          'ambos'),
('Insumos / Materia Prima', 'producto');

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- ✅ FIN DEL SCRIPT
-- ==========================================
-- RESUMEN DE MÓDULOS:
-- 1. Roles y Permisos Granulares (modulos_sistema + acciones_sistema + rol_permisos)
-- 2. Usuarios + Log de Auditoría
-- 3. Catálogos (categorias, clientes, proveedores)
-- 4. Productos + Lotes FIFO (lotes_inventario, movimientos_inventario)
-- 5. Menús y Combos (menus + menu_ingredientes)
-- 6. Turnos + Cortes de Caja + Cierres Diarios
-- 7. Ventas (vinculadas a turno, usuario, cliente, menú o producto)
-- 8. Detalle de Venta con trazabilidad FIFO (detalle_venta_lotes)
-- 9. Compras → generan lotes FIFO automáticamente
-- 10. Gastos Operativos
-- 11. Contabilidad Diaria (cuentas, asientos, balance_diario)
-- 12. Reportes Snapshot (por producto, stock, mensual)
-- ==========================================
