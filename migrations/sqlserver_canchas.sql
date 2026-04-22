-- Canchas donde se juega la liga (registro administrativo).
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'canchas' AND schema_id = SCHEMA_ID('dbo'))
BEGIN
    CREATE TABLE dbo.canchas (
        id INT IDENTITY(1,1) PRIMARY KEY,
        liga_id INT NOT NULL,
        nombre NVARCHAR(150) NOT NULL,
        direccion NVARCHAR(255) NULL,
        notas NVARCHAR(500) NULL,
        activa BIT NOT NULL CONSTRAINT DF_canchas_activa DEFAULT 1,
        created_at DATETIME NOT NULL CONSTRAINT DF_canchas_created DEFAULT GETDATE(),
        CONSTRAINT FK_canchas_liga FOREIGN KEY (liga_id) REFERENCES dbo.ligas(id) ON DELETE CASCADE
    );
    CREATE INDEX IX_canchas_liga ON dbo.canchas(liga_id);
END
GO
