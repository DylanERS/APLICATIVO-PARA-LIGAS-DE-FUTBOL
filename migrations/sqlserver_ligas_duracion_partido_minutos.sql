-- Duración oficial de cada partido (minutos), configurable en Config. Liga.
IF COL_LENGTH('dbo.ligas', 'duracion_partido_minutos') IS NULL
BEGIN
    ALTER TABLE dbo.ligas ADD duracion_partido_minutos INT NOT NULL
        CONSTRAINT DF_ligas_duracion_partido_minutos DEFAULT 90 WITH VALUES;
END
GO
