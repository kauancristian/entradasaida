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
        try {
            $HOST = 'localhost';
            $DATABASE = 'salaberga_entrada_saida';
            $USER = 'root';
            $PASSWORD = '';
            $this->connect = new PDO('mysql:host=' . $HOST . ';dbname=' . $DATABASE, $USER, $PASSWORD);
        } catch (PDOException $e) {
            $HOST = 'localhost';
            $DATABASE = 'u750204740_entradasaida';
            $USER = 'u750204740_entradasaida';
            $PASSWORD = 'paoComOvo123!@##';
            $this->connect = new PDO('mysql:host=' . $HOST . ';dbname=' . $DATABASE, $USER, $PASSWORD);
        } catch (PDOException $e) {
            die('Erro! O sistema não possui conexão com o banco de dados.');
        }
    }

    public function getConnection()
    {
        return $this->connect;
    }
}
