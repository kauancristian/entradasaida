<?php

require_once(__DIR__ . '/../config/Database.php');

class MainModel extends connect
{

    public function __construct()
    {
        parent::__construct();
    }

    public function registrarSaida($nome_responsavel, $nome_conducente, $id_tipo_conducente, $id_tipo_responsavel, $date_time, $id_motivo, $id_usuario, $id_aluno)
    {
        try{
            // Verifica se o aluno existe
            $stmt_check_aluno = $this->connect->prepare("SELECT id_aluno FROM aluno WHERE id_aluno = :id_aluno");
            $stmt_check_aluno->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
            $stmt_check_aluno->execute();
            if ($stmt_check_aluno->rowCount() === 0) {
                return 2; // Aluno não encontrado
            }

            // Verifica se já existe uma entrada para o aluno na mesma data
            $stmt_check_entrada = $this->connect->prepare("SELECT id_registro_entrada FROM registro_entrada WHERE id_aluno = :id_aluno AND DATE(date_time) = DATE(:date_time)");
            $stmt_check_entrada->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
            $stmt_check_entrada->bindValue(":date_time", $date_time);
            $stmt_check_entrada->execute();
            if ($stmt_check_entrada->rowCount() > 0) {
                return 1; // Entrada já registrada
            }

            // Insere o novo registro de entrada
            $stmt_registrar = $this->connect->prepare("
                INSERT INTO registro_entrada (
                    nome_responsavel,
                    nome_conducente,
                    id_tipo_conducente,
                    id_tipo_responsavel,
                    dae,
                    id_motivo,
                    id_usuario,
                    id_aluno
                ) VALUES (
                    :nome_responsavel,
                    :nome_conducente,
                    :id_tipo_conducente,
                    :id_tipo_responsavel,
                    :dae,
                    :id_motivo,
                    :id_usuario,
                    :id_aluno
                )
            ");
            $stmt_registrar->bindValue(":nome_responsavel", $nome_responsavel);
            $stmt_registrar->bindValue(":nome_conducente", $nome_conducente);
            $stmt_registrar->bindValue(":id_tipo_conducente", $id_tipo_conducente);
            $stmt_registrar->bindValue(":id_tipo_responsavel", $id_tipo_responsavel);
            $stmt_registrar->bindValue(":dae", $date_time);
            $stmt_registrar->bindValue(":id_motivo", $id_motivo);
            $stmt_registrar->bindValue(":id_usuario", $id_usuario);
            $stmt_registrar->bindValue(":id_aluno", $id_aluno);

            if ($stmt_registrar->execute()) {
            return 0; // Sucesso
            } else {
                return 3; // Erro interno
            }
        }catch(Exception $e){

            return 3;
        }
    }

    public function registrarEntrada(
        $nome_responsavel,
        $nome_conducente,
        $id_tipo_conducente,
        $id_tipo_responsavel,
        $date_time,
        $id_motivo,
        $id_usuario,
        $id_aluno
    ) {

        try{
            // Verifica se o aluno existe
            $stmt_check_aluno = $this->connect->prepare("SELECT id_aluno FROM aluno WHERE id_aluno = :id_aluno");
            $stmt_check_aluno->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
            $stmt_check_aluno->execute();
            if ($stmt_check_aluno->rowCount() === 0) {
                return 2; // Aluno não encontrado
            }

            // Verifica se já existe uma entrada para o aluno na mesma data
            $stmt_check_entrada = $this->connect->prepare("SELECT id_registro_entrada FROM registro_entrada WHERE id_aluno = :id_aluno AND DATE(date_time) = DATE(:date_time)");
            $stmt_check_entrada->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
            $stmt_check_entrada->bindValue(":date_time", $date_time);
            $stmt_check_entrada->execute();
            if ($stmt_check_entrada->rowCount() > 0) {
                return 1; // Entrada já registrada
            }

            // Insere o novo registro de entrada
            $stmt_registrar = $this->connect->prepare("
                INSERT INTO registro_entrada (
                    nome_responsavel,
                    nome_conducente,
                    id_tipo_conducente,
                    id_tipo_responsavel,
                    dae,
                    id_motivo,
                    id_usuario,
                    id_aluno
                ) VALUES (
                    :nome_responsavel,
                    :nome_conducente,
                    :id_tipo_conducente,
                    :id_tipo_responsavel,
                    :dae,
                    :id_motivo,
                    :id_usuario,
                    :id_aluno
                )
            ");
            $stmt_registrar->bindValue(":nome_responsavel", $nome_responsavel);
            $stmt_registrar->bindValue(":nome_conducente", $nome_conducente);
            $stmt_registrar->bindValue(":id_tipo_conducente", $id_tipo_conducente);
            $stmt_registrar->bindValue(":id_tipo_responsavel", $id_tipo_responsavel);
            $stmt_registrar->bindValue(":dae", $date_time);
            $stmt_registrar->bindValue(":id_motivo", $id_motivo);
            $stmt_registrar->bindValue(":id_usuario", $id_usuario);
            $stmt_registrar->bindValue(":id_aluno", $id_aluno);

            if ($stmt_registrar->execute()) {
                return 0; // Sucesso
            } else {
                return 3; // Erro interno
            }
        }catch(Exception $e){

            return 3;
        }
    }

    public function registrarSaidaEstagio($aluno, $date_time)    {
        try {
            // Verifica se o aluno existe na tabela aluno com base no nome
            $verificarAluno = "SELECT id_aluno FROM aluno WHERE nome = :nome";
            $queryVerificar = $this->connect->prepare($verificarAluno);
            $queryVerificar->bindValue(":nome", $aluno, PDO::PARAM_STR);
            $queryVerificar->execute();

            $verificarAluno = "SELECT id_aluno FROM aluno WHERE id_aluno = :id_aluno";
            $queryVerificar_id = $this->connect->prepare($verificarAluno);
            $queryVerificar_id->bindValue(":id_aluno", $aluno, PDO::PARAM_INT);
            $queryVerificar_id->execute();

            // Verifica se o aluno foi encontrado
            if ($queryVerificar->rowCount() > 0 || $queryVerificar_id->rowCount() > 0) {
                // Recupera o id_aluno
                $row2 = $queryVerificar_id->fetch(PDO::FETCH_ASSOC);
                $row = $queryVerificar->fetch(PDO::FETCH_ASSOC);
                $id_aluno = $row['id_aluno'] ?? $row2['id_aluno'];



/*
/////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////// DOCUMENTADO SÓ PARA O PERÍODO DE PRÉ-ESTÁGIO.//////////////////////////
///////////////////////////////// APÓS ISSO, RETIRAR A DOCUMENTAÇÃO DO TRECO ABAIXO /////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////
*/

                // Verifica se o aluno já foi registrado hoje
                /*
                $verificarRegistro = "SELECT id_aluno FROM saida_estagio 
                                    WHERE id_aluno = :id_aluno 
                                    AND DATE(dae) = CURDATE()";
                $queryVerificarRegistro = $this->connect->prepare($verificarRegistro);
                $queryVerificarRegistro->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
                $queryVerificarRegistro->execute();

                if ($queryVerificarRegistro->rowCount() > 0) {

                    return 1;
                }

*/


/////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////




                // Query SQL para inserir id_aluno e date_time na coluna dae
                $registrar = "INSERT INTO saida_estagio (id_aluno, dae) VALUES (:id_aluno, :dae)";
                $query = $this->connect->prepare($registrar);

                // Vincula os parâmetros
                $query->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
                $query->bindValue(":dae", $date_time, PDO::PARAM_STR);

                $query->execute();
                echo 'window.location.reload()';
                return 0; 
            } else {

                return 2;
            }
        } catch (PDOException $e) {
            return 3;
        }
    }
    
    
    
    
    
    
    

    
    
    
    
    
};
