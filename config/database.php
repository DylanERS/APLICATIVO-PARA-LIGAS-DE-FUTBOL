<?php
require_once __DIR__ . '/config.php';

class Database
{
    private $host = "Dylan\SQLEXPRESS";
    private $db_name = "LIGA_FUTBOL";
    private $username = "LIGA_FUTBOL_TEST"; // Windows Authentication or specific user
    private $password = "Santiago22";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            // Option 1: Using Windows Authentication (if username/password are empty)
            $dsn = "sqlsrv:Server=" . $this->host . ";Database=" . $this->db_name;
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Uncomment the line below if you face connection issues with SSL
                // "TrustServerCertificate" => true
            );

            // Connect using user and password if provided
            if ($this->username != "") {
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            }
            else {
                $this->conn = new PDO($dsn, null, null, $options);
            }

        }
        catch (PDOException $exception) {
            echo "Error de conexión a la base de datos: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
