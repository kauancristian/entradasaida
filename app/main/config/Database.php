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
        $configuredHost = getenv('DB_HOST');
        $configuredDatabase = getenv('DB_NAME');
        $configuredUser = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $port = getenv('DB_PORT');

        $requestHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        $isLocalHost = $requestHost === null
            || $requestHost === ''
            || in_array(strtolower((string) $requestHost), ['localhost', '127.0.0.1', '::1'], true);
        $isProduction = strtolower((string) (getenv('APP_ENV') ?: '')) === 'production';
        if (($isProduction || !$isLocalHost) && (
            $configuredHost === false
            || $configuredHost === ''
            || $configuredDatabase === false
            || $configuredDatabase === ''
            || $configuredUser === false
            || $configuredUser === ''
            || $password === false
        )) {
            error_log('As variáveis de conexão do banco de produção não estão configuradas.');
            throw new RuntimeException('A conexão com o banco de dados não está configurada para a hospedagem.');
        }

        $host = $configuredHost ?: 'localhost';
        $database = $configuredDatabase ?: 'entradasaida';
        $user = $configuredUser ?: 'root';
        $password = $password === false ? '' : $password;
        $dsn = 'mysql:host=' . $host
            . ($port !== false && $port !== '' ? ';port=' . $port : '')
            . ';dbname=' . $database . ';charset=utf8mb4';

        try {
            $this->connect = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log('Falha ao conectar ao banco de dados configurado: ' . $e->getMessage());
            throw new RuntimeException('Não foi possível conectar ao banco de dados configurado.', 0, $e);
        }
    }

    public function getConnection()
    {
        return $this->connect;
    }
}
