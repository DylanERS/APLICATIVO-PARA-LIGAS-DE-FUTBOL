-- Si la base ya existía sin esta columna, ejecuta este script en SQL Server Management Studio.
IF COL_LENGTH('dbo.ligas', 'min_jugadores_partido') IS NULL
BEGIN
    ALTER TABLE dbo.ligas ADD min_jugadores_partido INT NOT NULL
        CONSTRAINT DF_ligas_min_jugadores_partido DEFAULT 7 WITH VALUES;
END
GO
