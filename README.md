# Gestión de Ligas de Fútbol

Sistema web para administrar ligas de fútbol en **PHP** (sin framework) con patrón **MVC** y base de datos **Microsoft SQL Server**.

---

## Estado actual del sistema

### Autenticación y roles

- **Login** con sesión y redirección opcional tras autenticarse.
- Roles de usuario:
  - **Dueño de la liga (`admin`)**: acceso total, finanzas, usuarios, configuración de liga, temporadas y habilitación de nómina por partido.
  - **Organizador**: gestión operativa habitual (equipos, jugadores, temporadas según permisos del menú).
  - **Director técnico (`director_tecnico`)**: vinculado a un **equipo**; panel del club, **mis partidos** y registro de **nómina / asistencia** cuando el administrador lo habilita.
  - **Árbitro (`arbitro`)**: vinculado a un registro de la tabla **árbitros**; ve sus **partidos asignados** y puede **validar la nómina** comparando al jugador con la foto subida por el DT.

### Dashboard

- Vista resumida para administración u organización (métricas y listados).
- Vista **Mi club** para el DT (posición en tabla, goleadores, tarjetas, próximos partidos).
- Vista **Panel árbitro** con próximos partidos y acceso a validación de nómina.

### Equipos y jugadores (CRUD)

- Equipos: datos, logo, listados con DataTables.
- Jugadores: plantilla por equipo, dorsal, posición, foto.

### Temporadas y partidos

- Temporadas por liga, equipos inscritos, generación y gestión de partidos.
- Asignación de **árbitro** al partido (desde la edición del partido en la temporada).
- Estados del partido: programado, en curso, finalizado, suspendido.
- **Token de asistencia** por partido para enlaces de nómina (cuando se usa flujo por token).
- El administrador puede **habilitar el registro de asistencia por DT** por partido (`asistencia_dt_habilitada`).

### Nómina y asistencia (antes del inicio)

- Tabla de jugadores presentes por partido y equipo.
- El DT marca jugadores con **casillas y guardado** o mediante **código QR** por jugador (sesión iniciada como DT).
- Si la base incluye las columnas de migración, el DT debe asociar una **foto de validación** por jugador en nómina (archivo o cámara en móvil). Tras el QR, si falta foto, el sistema puede redirigir a la pantalla de subida de foto.
- Las imágenes se guardan bajo `assets/img/asistencia/{id_partido}/` (crear permisos de escritura en el servidor).
- El **árbitro designado** en el partido accede a **Validar nómina**: ve foto y datos del jugador y registra si **coincide**, **no coincide** o deja **pendiente** (`validacion_arbitro`).

### Finanzas

- Módulo de finanzas para el dueño de la liga (según implementación en `FinanceController`).

### Usuarios

- Alta/edición/baja de usuarios (solo **admin**), con asignación de equipo (DT) o de árbitro (rol árbitro).

---

## Credenciales por defecto

Tras cargar `database.sql`, puedes entrar con:

- **Usuario:** `admin`
- **Contraseña:** `password`

El hash bcrypt incluido en el script corresponde a esa contraseña. Para cambiarla, actualiza la columna `password` en `usuarios` con un nuevo hash generado por `password_hash('tu_clave', PASSWORD_BCRYPT)` en PHP.

---

## Requisitos

1. **Servidor web:** Apache (por ejemplo XAMPP).
2. **PHP:** 7.4 o superior.
3. **Extensiones:** `pdo_sqlsrv` y `sqlsrv` habilitadas en `php.ini`.
4. **SQL Server** accesible desde PHP.

---

## Instalación en un equipo nuevo

1. **Base de datos**
   - Crea la base (por ejemplo `LIGA_FUTBOL`) en SQL Server.
   - Ejecuta el archivo raíz **`database.sql`** completo: incluye tablas, relaciones, columnas de nómina con foto y validación, roles `director_tecnico` y `arbitro`, `equipo_id` / `arbitro_id` en usuarios, token y flag de asistencia en partidos, y el usuario administrador inicial.
   - No necesitas ejecutar los scripts de `migrations/` en una base **nueva**; esos archivos sirven para **actualizar** instalaciones antiguas.

2. **Conexión**
   - Ajusta `config/database.php` (servidor, base, usuario y contraseña).

3. **Aplicación**
   - Coloca el proyecto en `htdocs` (o el virtual host que uses).
   - **URL base (`BASE_URL`):** se calcula sola en cada petición (mismo host o IP, `http`/`https` y carpeta del proyecto). Así, si entras desde el celular con `http://192.168.0.10/LIGA_FUTBOL/`, los enlaces y redirecciones usan esa IP, no `localhost`.
   - Si detrás de un proxy la detección falla, en `config/config.php` puedes definir `BASE_URL_OVERRIDE` con la URL pública completa (con slash final).
   - Opcional: define `ATTENDANCE_QR_SECRET` con un valor secreto distinto en producción.

4. **Carpetas de subida**
   - Asegura permisos de escritura para `assets/img/players/`, `assets/img/teams/` (o la ruta que uses para logos) y `assets/img/asistencia/` (fotos de nómina).

5. **Navegador**
   - Abre la URL del proyecto, por ejemplo `http://localhost/LIGA_FUTBOL/`.

---

## Carpeta `migrations/`

Scripts pensados para **bases ya en uso** (añaden columnas, tablas o amplían restricciones sin recrear todo):

| Archivo | Propósito |
|--------|-----------|
| `sqlserver_usuarios_rol_director_tecnico.sql` | Rol `director_tecnico` y `usuarios.equipo_id` |
| `sqlserver_partidos_asistencia_dt_habilitada.sql` | Columna `partidos.asistencia_dt_habilitada` |
| `sqlserver_partidos_fase.sql` | Columna `partidos.fase` para distinguir `regular`, `octavos`, `cuartos`, `semifinal`, `final` |
| `sqlserver_asistencia_foto_y_arbitro.sql` | Foto y validación en nómina, rol `arbitro`, `usuarios.arbitro_id` |

En instalaciones nuevas, el contenido funcional de estos parches ya está integrado en **`database.sql`**.

---

## Estructura del proyecto

- `assets/` — Estáticos e imágenes subidas (jugadores, logos, asistencia).
- `config/` — `config.php`, `database.php`.
- `controllers/` — Lógica MVC.
- `models/` — Acceso a datos (PDO).
- `views/` — Vistas PHP + Bootstrap 5.
- `routes/web.php` — Rutas (`?url=...`).
- `index.php` — Front controller.
- `database.sql` — Esquema completo SQL Server para instalación limpia.

---

*Desarrollado con código claro y separación MVC.*
