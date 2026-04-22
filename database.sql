-- =============================================
-- Database Schema for Football League Management
-- =============================================

-- Create the database (execute manually if needed)
-- CREATE DATABASE LIGA_FUTBOL;
-- GO
-- USE LIGA_FUTBOL;
-- GO

-- 1. Usuarios (admin, organizador, director_tecnico, arbitro)
CREATE TABLE usuarios (
    id INT IDENTITY(1,1) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'organizador', 'director_tecnico', 'arbitro')),
    equipo_id INT NULL,
    arbitro_id INT NULL,
    created_at DATETIME DEFAULT GETDATE()
);

-- 2. Ligas
CREATE TABLE ligas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    min_jugadores_partido INT NOT NULL DEFAULT 7,
    duracion_partido_minutos INT NOT NULL DEFAULT 90,
    fecha_inicio DATE,
    fecha_fin DATE,
    estado VARCHAR(20) DEFAULT 'activa' CHECK (estado IN ('activa', 'inactiva', 'finalizada')),
    created_at DATETIME DEFAULT GETDATE()
);

-- 2b. Canchas (sedes donde se juega la liga)
CREATE TABLE canchas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    liga_id INT NOT NULL,
    nombre NVARCHAR(150) NOT NULL,
    direccion NVARCHAR(255) NULL,
    notas NVARCHAR(500) NULL,
    activa BIT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT GETDATE(),
    FOREIGN KEY (liga_id) REFERENCES ligas(id) ON DELETE CASCADE
);

-- 3. Temporadas
CREATE TABLE temporadas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    liga_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    anio INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    dias_juego VARCHAR(150) NOT NULL, -- Ej: 'martes,miercoles'
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    estado VARCHAR(20) DEFAULT 'activa' CHECK (estado IN ('activa', 'finalizada')),
    FOREIGN KEY (liga_id) REFERENCES ligas(id) ON DELETE CASCADE
);

-- Si ya tienes la tabla temporadas creada en una BD existente, ejecuta:
-- ALTER TABLE temporadas ADD fecha_inicio DATE NULL;
-- ALTER TABLE temporadas ADD fecha_fin DATE NULL;
-- ALTER TABLE temporadas ADD dias_juego VARCHAR(150) NULL;
-- ALTER TABLE temporadas ADD hora_inicio TIME NULL;
-- ALTER TABLE temporadas ADD hora_fin TIME NULL;

-- 4. Equipos
CREATE TABLE equipos (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ciudad VARCHAR(100),
    entrenador VARCHAR(100),
    logo VARCHAR(255) DEFAULT 'default_logo.png',
    fecha_registro DATETIME DEFAULT GETDATE()
);

ALTER TABLE usuarios ADD CONSTRAINT FK_usuarios_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id);

-- 5. Relación Equipos-Temporada (Muchos a Muchos)
CREATE TABLE equipos_temporadas (
    equipo_id INT NOT NULL,
    temporada_id INT NOT NULL,
    PRIMARY KEY (equipo_id, temporada_id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
    FOREIGN KEY (temporada_id) REFERENCES temporadas(id) ON DELETE CASCADE
);

-- 6. Jugadores
CREATE TABLE jugadores (
    id INT IDENTITY(1,1) PRIMARY KEY,
    equipo_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    edad INT,
    posicion VARCHAR(50),
    numero INT,
    foto VARCHAR(255) DEFAULT 'default_player.png',
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);

-- 7. Arbitros
CREATE TABLE arbitros (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(30),
    created_at DATETIME DEFAULT GETDATE()
);

ALTER TABLE usuarios ADD CONSTRAINT FK_usuarios_arbitro FOREIGN KEY (arbitro_id) REFERENCES arbitros(id);

-- 8. Partidos
CREATE TABLE partidos (
    id INT IDENTITY(1,1) PRIMARY KEY,
    temporada_id INT NOT NULL,
    equipo_local_id INT NOT NULL,
    equipo_visitante_id INT NOT NULL,
    arbitro_id INT NULL,
    cancha_id INT NULL,
    fecha_hora DATETIME NOT NULL,
    asistencia_token VARCHAR(64) NULL,
    asistencia_dt_habilitada BIT NOT NULL DEFAULT 0,
    inicio_real DATETIME NULL,
    fin_real DATETIME NULL,
    fase VARCHAR(20) NOT NULL DEFAULT 'regular'
        CHECK (fase IN ('regular', 'octavos', 'cuartos', 'semifinal', 'final', 'eliminatoria')),
    estado VARCHAR(20) DEFAULT 'programado' CHECK (estado IN ('programado', 'en curso', 'finalizado', 'suspendido')),
    -- Token único por partido para enlace de nómina (varias filas NULL permitidas en SQL Server)
    CONSTRAINT UQ_partidos_asistencia_token UNIQUE (asistencia_token),
    FOREIGN KEY (temporada_id) REFERENCES temporadas(id),
    FOREIGN KEY (equipo_local_id) REFERENCES equipos(id),
    FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id),
    FOREIGN KEY (arbitro_id) REFERENCES arbitros(id),
    FOREIGN KEY (cancha_id) REFERENCES canchas(id)
);

-- 8b. Jugadores presentes por partido (nómina previa al inicio)
--     foto_asistencia: imagen tomada por el DT para validación en cancha
--     validacion_arbitro: NULL/pendiente, 'confirmado', 'rechazado' (según flujo del árbitro)
CREATE TABLE partido_jugadores_presentes (
    partido_id INT NOT NULL,
    jugador_id INT NOT NULL,
    equipo_id INT NOT NULL,
    foto_asistencia VARCHAR(255) NULL,
    validacion_arbitro VARCHAR(20) NULL,
    CONSTRAINT PK_partido_jugador_presente PRIMARY KEY (partido_id, jugador_id),
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
);

-- 9. Resultados
CREATE TABLE resultados (
    partido_id INT PRIMARY KEY,
    goles_local INT DEFAULT 0,
    goles_visitante INT DEFAULT 0,
    observaciones TEXT,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE
);

-- 10. Goles
CREATE TABLE goles (
    id INT IDENTITY(1,1) PRIMARY KEY,
    partido_id INT NOT NULL,
    jugador_id INT NOT NULL,
    minuto INT NOT NULL,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id)
);

-- 11. Tarjetas
CREATE TABLE tarjetas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    partido_id INT NOT NULL,
    jugador_id INT NOT NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('amarilla', 'roja')),
    minuto INT NOT NULL,
    motivo NVARCHAR(500) NULL,
    FOREIGN KEY (partido_id) REFERENCES partidos(id),
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id)
);

-- 12. Pagos (Finanzas)
CREATE TABLE pagos (
    id INT IDENTITY(1,1) PRIMARY KEY,
    equipo_id INT NOT NULL,
    temporada_id INT NOT NULL,
    concepto VARCHAR(100) NOT NULL, -- Ej: 'Inscripción', 'Arbitraje'
    monto DECIMAL(10, 2) NOT NULL,
    fecha DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id),
    FOREIGN KEY (temporada_id) REFERENCES temporadas(id)
);

-- 13. Multas
CREATE TABLE multas (
    id INT IDENTITY(1,1) PRIMARY KEY,
    equipo_id INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'pagada')),
    fecha DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);

-- =============================================
-- Instalación nueva vs. base de datos ya existente
-- =============================================
-- Este archivo define el esquema COMPLETO para una instalación desde cero en SQL Server.
-- Ejecuta todo el script (crear base USE LIGA_FUTBOL si aplica) en un servidor vacío.
--
-- Si ya tienes una base antigua, NO ejecutes este script entero: usa los parches en la
-- carpeta migrations/ (idempotentes donde es posible), en este orden sugerido:
--   1) sqlserver_usuarios_rol_director_tecnico.sql  — rol director_tecnico + usuarios.equipo_id
--   2) sqlserver_partidos_asistencia_dt_habilitada.sql — partidos.asistencia_dt_habilitada
--   3) sqlserver_asistencia_foto_y_arbitro.sql — foto/validación en nómina, rol arbitro, usuarios.arbitro_id
--   4) sqlserver_partidos_inicio_fin_real.sql — partidos.inicio_real / fin_real (hora real de inicio y cierre)
-- (Los puntos 1 y 3 amplían el CHECK de usuarios.role; el script 3 deja el rol final:
--  admin, organizador, director_tecnico, arbitro.)
--
--   5) sqlserver_ligas_duracion_partido_minutos.sql — ligas.duracion_partido_minutos
--   6) sqlserver_canchas.sql — tabla canchas (sedes de la liga)
--   7) sqlserver_partidos_cancha_id.sql — partidos.cancha_id + FK a canchas
--   8) sqlserver_partidos_fase.sql — partidos.fase (regular / octavos / cuartos / semifinal / final)
--
-- Objetos ya incluidos arriba (no hace falta migración en instalación limpia):
--   ligas.min_jugadores_partido, ligas.duracion_partido_minutos, tabla canchas,
--   partidos.arbitro_id, partidos.asistencia_token,
--   partidos.fase,
--   partidos.asistencia_dt_habilitada, partido_jugadores_presentes (con foto_asistencia, validacion_arbitro),
--   usuarios.equipo_id, usuarios.arbitro_id, roles admin|organizador|director_tecnico|arbitro.

-- =============================================
-- Insert Sample Data
-- =============================================

-- Administrador por defecto: usuario "admin", contraseña "password" (hash bcrypt de ejemplo).
-- Para otra clave: UPDATE usuarios SET password = '<hash bcrypt>' WHERE username = 'admin';
INSERT INTO usuarios (username, password, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
