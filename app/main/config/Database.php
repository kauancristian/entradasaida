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
        $ambienteConfigurado = getenv('APP_ENV');
        $hostHttp = strtolower((string) parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));
        $hostLocal = in_array($hostHttp, ['localhost', '127.0.0.1', '::1'], true);
        $emProducao = strtolower((string) $ambienteConfigurado) === 'production'
            || (($ambienteConfigurado === false || trim($ambienteConfigurado) === '') && $hostHttp !== '' && !$hostLocal);
        $host = getenv('DB_HOST');
        $database = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $port = getenv('DB_PORT');

        if ($emProducao && (!$host || !$database || !$user || $password === false || $password === '')) {
            error_log('Conexão recusada: configure DB_HOST, DB_NAME, DB_USER e DB_PASSWORD em produção.');
            die('Erro! O sistema não possui conexão com o banco de dados.');
        }

        $host = $host ?: 'localhost';
        $database = $database ?: 'entradasaida';
        $user = $user ?: 'root';
        $password = $password === false ? '' : $password;
        $dsn = 'mysql:host=' . $host . ';dbname=' . $database . ($port ? ';port=' . (int) $port : '') . ';charset=utf8mb4';

        try {
            $this->connect = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            error_log('Erro ao conectar ao banco de dados: ' . $e->getMessage());
            die('Erro! O sistema não possui conexão com o banco de dados.');
        }
    }

    public function getConnection()
    {
        return $this->connect;
    }
}