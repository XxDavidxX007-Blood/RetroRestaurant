-- ============================================================
-- RETRO RESTAURANT — Setup completo de base de datos
-- Ejecutar una sola vez al montar el proyecto
-- Orden: 1. Tablas  2. Índices  3. Datos base
-- ============================================================

-- ============================================================
-- 1. TABLAS
-- ============================================================

CREATE TABLE rol (
  id_rol     INT PRIMARY KEY AUTO_INCREMENT,
  nombre_rol VARCHAR(30) NOT NULL
);

CREATE TABLE usuario (
  id_usuario INT          PRIMARY KEY AUTO_INCREMENT,
  nombre     VARCHAR(50)  NOT NULL,
  apellidos  VARCHAR(100) NOT NULL DEFAULT '',
  email      VARCHAR(150) NOT NULL UNIQUE,
  telefono   VARCHAR(30),
  password   VARCHAR(255) NOT NULL,
  foto       VARCHAR(255),
  id_rol     INT          NOT NULL DEFAULT 1,
  FOREIGN KEY (id_rol) REFERENCES rol(id_rol)
);

CREATE TABLE cliente (
  id_cliente INT PRIMARY KEY AUTO_INCREMENT,
  id_usuario INT NOT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE mesero (
  id_mesero  INT PRIMARY KEY AUTO_INCREMENT,
  id_usuario INT NOT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE mesa (
  id_mesa      INT PRIMARY KEY AUTO_INCREMENT,
  numero_mesa  INT         NOT NULL UNIQUE,
  capacidad    INT         NOT NULL,
  estado       VARCHAR(20) NOT NULL DEFAULT 'disponible'
);

CREATE TABLE estado_reserva (
  id_estado_reserva INT PRIMARY KEY AUTO_INCREMENT,
  nombre_estado     VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE reserva (
  id_reserva        INT  PRIMARY KEY AUTO_INCREMENT,
  id_cliente        INT  NOT NULL,
  id_mesa           INT  NOT NULL,
  fecha_reserva     DATE NOT NULL,
  hora_reserva      TIME NOT NULL,
  numero_personas   INT  NOT NULL,
  id_estado_reserva INT  NOT NULL,
  FOREIGN KEY (id_cliente)        REFERENCES cliente(id_cliente),
  FOREIGN KEY (id_mesa)           REFERENCES mesa(id_mesa),
  FOREIGN KEY (id_estado_reserva) REFERENCES estado_reserva(id_estado_reserva)
);

CREATE TABLE categoria_producto (
  id_categoria     INT PRIMARY KEY AUTO_INCREMENT,
  nombre_categoria VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categoria_producto (nombre_categoria) VALUES
('Carnes'),
('Lácteos'),
('Verduras'),
('Frutas'),
('Bebidas'),
('Granos y Cereales'),
('Mariscos'),
('Condimentos'),
('Panadería'),
('Postres');

CREATE TABLE producto (
  id_producto  INT            PRIMARY KEY AUTO_INCREMENT,
  id_categoria INT            NOT NULL,
  nombre       VARCHAR(50)    NOT NULL,
  precio       DECIMAL(10,2)  NOT NULL,
  descripcion  VARCHAR(150),
  imagen       VARCHAR(255),
  unidad       VARCHAR(20)    NOT NULL DEFAULT 'unid',
  es_menu      TINYINT(1)     NOT NULL DEFAULT 0,
  disponible   TINYINT(1)     NOT NULL DEFAULT 1,
  FOREIGN KEY (id_categoria) REFERENCES categoria_producto(id_categoria)
);

CREATE TABLE inventario (
  id_inventario        INT  PRIMARY KEY AUTO_INCREMENT,
  id_producto          INT  NOT NULL UNIQUE,
  cantidad_actual      INT  NOT NULL DEFAULT 0,
  cantidad_minima      INT  NOT NULL,
  fecha_actualizacion  DATE NOT NULL,
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
);

CREATE TABLE movimiento_inventario (
  id_movimiento    INT         PRIMARY KEY AUTO_INCREMENT,
  id_producto      INT         NOT NULL,
  tipo_movimiento  VARCHAR(20) NOT NULL,
  cantidad         INT         NOT NULL,
  fecha_movimiento DATE        NOT NULL,
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
);

CREATE TABLE tipo_pedido (
  id_tipo_pedido INT PRIMARY KEY AUTO_INCREMENT,
  nombre_tipo    VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE estado_pedido (
  id_estado_pedido INT PRIMARY KEY AUTO_INCREMENT,
  nombre_estado    VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE pedido (
  id_pedido        INT  PRIMARY KEY AUTO_INCREMENT,
  id_cliente       INT,
  id_mesero        INT  NOT NULL,
  id_tipo_pedido   INT  NOT NULL,
  fecha_pedido     DATE NOT NULL,
  id_estado_pedido INT  NOT NULL,
  FOREIGN KEY (id_cliente)       REFERENCES cliente(id_cliente),
  FOREIGN KEY (id_mesero)        REFERENCES mesero(id_mesero),
  FOREIGN KEY (id_tipo_pedido)   REFERENCES tipo_pedido(id_tipo_pedido),
  FOREIGN KEY (id_estado_pedido) REFERENCES estado_pedido(id_estado_pedido)
);

CREATE TABLE detalle_pedido (
  id_detalle      INT           PRIMARY KEY AUTO_INCREMENT,
  id_pedido       INT           NOT NULL,
  id_producto     INT           NOT NULL,
  cantidad        INT           NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal        DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (id_pedido)   REFERENCES pedido(id_pedido),
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
);

CREATE TABLE factura (
  id_factura     INT           PRIMARY KEY AUTO_INCREMENT,
  id_pedido      INT           NOT NULL,
  id_cliente     INT,
  fecha          DATE          NOT NULL,
  metodo_pago    VARCHAR(30)   NOT NULL,
  total_factura  DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (id_pedido)  REFERENCES pedido(id_pedido),
  FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente)
);

CREATE TABLE detalle_factura (
  id_detalleFactura INT           PRIMARY KEY AUTO_INCREMENT,
  id_factura        INT           NOT NULL,
  id_producto       INT           NOT NULL,
  cantidad          INT           NOT NULL,
  precio_unitario   DECIMAL(10,2) NOT NULL,
  subtotal          DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (id_factura)  REFERENCES factura(id_factura),
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
);

-- ============================================================
-- 2. ÍNDICES
-- ============================================================

CREATE INDEX idx_usuario_rol       ON usuario(id_rol);
CREATE INDEX idx_cliente_usuario   ON cliente(id_usuario);
CREATE INDEX idx_mesero_usuario    ON mesero(id_usuario);
CREATE INDEX idx_reserva_cliente   ON reserva(id_cliente);
CREATE INDEX idx_reserva_mesa      ON reserva(id_mesa);
CREATE INDEX idx_reserva_fecha     ON reserva(fecha_reserva);
CREATE INDEX idx_producto_categoria ON producto(id_categoria);
CREATE INDEX idx_pedido_cliente    ON pedido(id_cliente);
CREATE INDEX idx_pedido_mesero     ON pedido(id_mesero);
CREATE INDEX idx_pedido_fecha      ON pedido(fecha_pedido);
CREATE INDEX idx_factura_cliente   ON factura(id_cliente);
CREATE INDEX idx_factura_fecha     ON factura(fecha);

-- ============================================================
-- 3. DATOS BASE (catálogos obligatorios)
-- ============================================================

-- Roles
INSERT INTO rol (nombre_rol) VALUES
  ('administrador'),
  ('empleado'),
  ('cliente');

-- Estados de reserva
INSERT INTO estado_reserva (nombre_estado) VALUES
  ('pendiente'),
  ('confirmada'),
  ('cancelada'),
  ('completada'),
  ('no asistio');

-- Mesas del restaurante
INSERT INTO mesa (numero_mesa, capacidad, estado) VALUES
  (1,  2,  'disponible'),
  (2,  2,  'disponible'),
  (3,  4,  'disponible'),
  (4,  4,  'disponible'),
  (5,  4,  'disponible'),
  (6,  6,  'disponible'),
  (7,  6,  'disponible'),
  (8,  8,  'disponible'),
  (9,  8,  'disponible'),
  (10, 10, 'disponible');

-- Estados de pedido
INSERT INTO estado_pedido (nombre_estado) VALUES
  ('pendiente'),
  ('en_preparacion'),
  ('listo'),
  ('entregado'),
  ('cancelado');

-- Tipos de pedido
INSERT INTO tipo_pedido (nombre_tipo) VALUES
  ('mesa'),
  ('domicilio'),
  ('para llevar');

-- ============================================================
-- TABLA NOTIFICACION
-- ============================================================

CREATE TABLE notificacion (
  id_notificacion    INT           PRIMARY KEY AUTO_INCREMENT,
  id_usuario_destino INT           NOT NULL,
  tipo               VARCHAR(30)   NOT NULL DEFAULT 'general',
  titulo             VARCHAR(150)  NOT NULL,
  mensaje            VARCHAR(500)  NOT NULL,
  id_referencia      INT           DEFAULT NULL,
  leida              TINYINT(1)    NOT NULL DEFAULT 0,
  created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario_destino) REFERENCES usuario(id_usuario)
);

CREATE INDEX idx_notif_usuario ON notificacion(id_usuario_destino, leida);

-- ============================================================
-- TABLA PROMOCION
-- ============================================================

CREATE TABLE promocion (
  id_promocion   INT           PRIMARY KEY AUTO_INCREMENT,
  titulo         VARCHAR(100)  NOT NULL,
  descripcion    VARCHAR(255)  NOT NULL,
  descuento      DECIMAL(5,2)  NOT NULL DEFAULT 0,
  fecha_inicio   DATE          NOT NULL,
  fecha_fin      DATE          NOT NULL,
  activa         TINYINT(1)    NOT NULL DEFAULT 1
);

CREATE INDEX idx_promocion_fecha ON promocion(fecha_fin, activa);
