/*
  LIGA_FUTBOL — Permitir rol director_tecnico en usuarios (SQL Server)

  Ejecuta este script contra tu base de datos (por ejemplo en SSMS).
  El nombre del CHECK viejo puede variar; el tuyo en el error era:
  CK__usuarios__role__38996AB5

  Si el DROP falla por nombre incorrecto, ejecuta antes:

  SELECT cc.name, cc.definition
  FROM sys.check_constraints cc
  WHERE cc.parent_object_id = OBJECT_ID(N'dbo.usuarios');

  y sustituye el nombre en el DROP de abajo.
*/

-- Quitar CHECK antiguo (solo admin / organizador)
IF EXISTS (
    SELECT 1 FROM sys.check_constraints
    WHERE parent_object_id = OBJECT_ID(N'dbo.usuarios')
      AND name = N'CK__usuarios__role__38996AB5'
)
    ALTER TABLE dbo.usuarios DROP CONSTRAINT CK__usuarios__role__38996AB5;
GO

-- Por si en un intento previo quedó este nombre:
IF EXISTS (
    SELECT 1 FROM sys.check_constraints
    WHERE parent_object_id = OBJECT_ID(N'dbo.usuarios')
      AND name = N'CK_usuarios_role'
)
    ALTER TABLE dbo.usuarios DROP CONSTRAINT CK_usuarios_role;
GO

ALTER TABLE dbo.usuarios
    ADD CONSTRAINT CK_usuarios_role CHECK (role IN (N'admin', N'organizador', N'director_tecnico'));
GO

-- Columna para vincular al DT con su equipo (si aún no existe)
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.usuarios') AND name = N'equipo_id'
)
BEGIN
    ALTER TABLE dbo.usuarios ADD equipo_id INT NULL;
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys WHERE name = N'FK_usuarios_equipo'
)
BEGIN
    ALTER TABLE dbo.usuarios
        ADD CONSTRAINT FK_usuarios_equipo FOREIGN KEY (equipo_id) REFERENCES dbo.equipos(id);
END
GO
