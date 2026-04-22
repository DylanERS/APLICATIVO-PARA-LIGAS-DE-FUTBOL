-- Causal de amonestación/expulsión en tarjetas (SQL Server)
IF COL_LENGTH('dbo.tarjetas', 'motivo') IS NULL
BEGIN
    ALTER TABLE dbo.tarjetas ADD motivo NVARCHAR(500) NULL;
END
GO
