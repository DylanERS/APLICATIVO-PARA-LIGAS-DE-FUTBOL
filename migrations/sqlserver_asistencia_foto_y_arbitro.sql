/*
  LIGA_FUTBOL — Foto de validación en nómina (DT) + rol árbitro + vínculo usuario–árbitro

  Ejecuta en SQL Server contra tu base (ej. LIGA_FUTBOL).

  1) Ampliar CHECK de role para incluir 'arbitro' (igual que director_tecnico:
     si falla el DROP, lista constraints con la consulta del final).
  2) Columna usuarios.arbitro_id (FK a arbitros).
  3) Columnas en partido_jugadores_presentes: foto_asistencia, validacion_arbitro.
*/

DECLARE @ckName SYSNAME;
DECLARE @dropSql NVARCHAR(512);

SELECT @ckName = cc.name
FROM sys.check_constraints cc
INNER JOIN sys.columns c ON c.object_id = cc.parent_object_id AND c.column_id = cc.parent_column_id
WHERE cc.parent_object_id = OBJECT_ID(N'dbo.usuarios') AND c.name = N'role';

IF @ckName IS NOT NULL
BEGIN
    SET @dropSql = N'ALTER TABLE dbo.usuarios DROP CONSTRAINT ' + QUOTENAME(@ckName) + N';';
    EXEC (@dropSql);
END
GO

ALTER TABLE dbo.usuarios
    ADD CONSTRAINT CK_usuarios_role CHECK (role IN (N'admin', N'organizador', N'director_tecnico', N'arbitro'));
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.usuarios') AND name = N'arbitro_id'
)
BEGIN
    ALTER TABLE dbo.usuarios ADD arbitro_id INT NULL;
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys WHERE name = N'FK_usuarios_arbitro'
)
BEGIN
    ALTER TABLE dbo.usuarios
        ADD CONSTRAINT FK_usuarios_arbitro FOREIGN KEY (arbitro_id) REFERENCES dbo.arbitros(id);
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.partido_jugadores_presentes') AND name = N'foto_asistencia'
)
BEGIN
    ALTER TABLE dbo.partido_jugadores_presentes ADD foto_asistencia VARCHAR(255) NULL;
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.partido_jugadores_presentes') AND name = N'validacion_arbitro'
)
BEGIN
    ALTER TABLE dbo.partido_jugadores_presentes ADD validacion_arbitro VARCHAR(20) NULL;
END
GO

/*
  Si necesitas el nombre exacto del CHECK viejo:
  SELECT cc.name, cc.definition
  FROM sys.check_constraints cc
  INNER JOIN sys.columns c ON c.object_id = cc.parent_object_id AND c.column_id = cc.parent_column_id
  WHERE cc.parent_object_id = OBJECT_ID(N'dbo.usuarios') AND c.name = N'role';
*/
