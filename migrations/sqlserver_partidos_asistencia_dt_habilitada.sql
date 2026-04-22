/*
  SQL Server — El administrador habilita el registro; el DT marca asistencia.

  Ejecutar en la base LIGA_FUTBOL.
*/

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.partidos') AND name = N'asistencia_dt_habilitada'
)
BEGIN
    ALTER TABLE dbo.partidos ADD asistencia_dt_habilitada BIT NOT NULL CONSTRAINT DF_partidos_asistencia_dt_habilitada DEFAULT 0;
END
GO
