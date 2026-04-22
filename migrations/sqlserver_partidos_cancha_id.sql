-- Relaciona partidos con canchas (asignación opcional)
IF COL_LENGTH('dbo.partidos', 'cancha_id') IS NULL
BEGIN
    ALTER TABLE dbo.partidos ADD cancha_id INT NULL;
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = 'FK_partidos_cancha'
)
BEGIN
    ALTER TABLE dbo.partidos
    ADD CONSTRAINT FK_partidos_cancha
        FOREIGN KEY (cancha_id) REFERENCES dbo.canchas(id);
END
GO
