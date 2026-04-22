-- Hora real de inicio y fin del partido (SQL Server)
IF COL_LENGTH('dbo.partidos', 'inicio_real') IS NULL
BEGIN
    ALTER TABLE dbo.partidos ADD inicio_real DATETIME NULL;
END
GO
IF COL_LENGTH('dbo.partidos', 'fin_real') IS NULL
BEGIN
    ALTER TABLE dbo.partidos ADD fin_real DATETIME NULL;
END
GO
