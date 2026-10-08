<?php

class connect
{
    protected $connect;

    function __construct()
    {
        $this->connect_database();
    }

    function connect_database()
    {
        $HOST = getenv('DB_HOST') ?: 'localhost';
        $DATABASE = getenv('DB_NAME') ?: 'entradasaida';
        $USER = getenv('DB_USER') ?: 'root';
        $PASSWORD = getenv('DB_PASSWORD') ?: '';

        try {
            $this->connect = new PDO(
                'mysql:host=' . $HOST . ';dbname=' . $DATABASE . ';charset=utf8mb4',
                $USER,
                $PASSWORD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Não foi possível conectar ao banco de dados configurado.', 0, $e);
        }
    }

    public function getConnection()
    {
        return $this->connect;
    }
}
