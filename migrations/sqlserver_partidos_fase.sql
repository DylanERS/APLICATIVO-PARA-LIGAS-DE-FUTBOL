/*
  Agrega columna partidos.fase para distinguir:
  regular, octavos, cuartos, semifinal, final, eliminatoria.

  Idempotente para SQL Server.
*/

IF COL_LENGTH('partidos', 'fase') IS NULL
BEGIN
    ALTER TABLE partidos
    ADD fase VARCHAR(20) NOT NULL
        CONSTRAINT DF_partidos_fase DEFAULT 'regular';
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.check_constraints
    WHERE name = 'CK_partidos_fase'
      AND parent_object_id = OBJECT_ID('partidos')
)
BEGIN
    ALTER TABLE partidos
    ADD CONSTRAINT CK_partidos_fase
        CHECK (fase IN ('regular', 'octavos', 'cuartos', 'semifinal', 'final', 'eliminatoria'));
END
GO

UPDATE partidos
SET fase = 'regular'
WHERE fase IS NULL;
GO
