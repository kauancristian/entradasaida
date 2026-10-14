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

            $stmt_check_saida = $this->connect->prepare("SELECT id_registro_saida FROM registro_saida WHERE id_aluno = :id_aluno AND DATE(date_time) = DATE(:date_time)");
            $stmt_check_saida->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
            $stmt_check_saida->bindValue(":date_time", $date_time);
            $stmt_check_saida->execute();
            if ($stmt_check_saida->rowCount() > 0) {
                return 1;
            }

            $stmt_registrar = $this->connect->prepare("
                INSERT INTO registro_saida (
                    nome_responsavel,
                    nome_conducente,
                    id_tipo_conducente,
                    id_tipo_responsavel,
                    date_time,
                    id_motivo,
                    id_usuario,
                    id_aluno
                ) VALUES (
                    :nome_responsavel,
                    :nome_conducente,
                    :id_tipo_conducente,
                    :id_tipo_responsavel,
                    :date_time,
                    :id_motivo,
                    :id_usuario,
                    :id_aluno
                )
            ");
            $stmt_registrar->bindValue(":nome_responsavel", $nome_responsavel);
            $stmt_registrar->bindValue(":nome_conducente", $nome_conducente);
            $stmt_registrar->bindValue(":id_tipo_conducente", $id_tipo_conducente);
            $stmt_registrar->bindValue(":id_tipo_responsavel", $id_tipo_responsavel);
            $stmt_registrar->bindValue(":date_time", $date_time);
            $stmt_registrar->bindValue(":id_motivo", $id_motivo);
            $stmt_registrar->bindValue(":id_usuario", $id_usuario);
            $stmt_registrar->bindValue(":id_aluno", $id_aluno);

            if ($stmt_registrar->execute()) {
            return 0; // Sucesso
            } else {
                return 3; // Erro interno
            }
        } catch (Exception $e) {
            error_log('Erro ao registrar saída: ' . $e->getMessage());
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
                    date_time,
                    id_motivo,
                    id_usuario,
                    id_aluno
                ) VALUES (
                    :nome_responsavel,
                    :nome_conducente,
                    :id_tipo_conducente,
                    :id_tipo_responsavel,
                    :date_time,
                    :id_motivo,
                    :id_usuario,
                    :id_aluno
                )
            ");
            $stmt_registrar->bindValue(":nome_responsavel", $nome_responsavel);
            $stmt_registrar->bindValue(":nome_conducente", $nome_conducente);
            $stmt_registrar->bindValue(":id_tipo_conducente", $id_tipo_conducente);
            $stmt_registrar->bindValue(":id_tipo_responsavel", $id_tipo_responsavel);
            $stmt_registrar->bindValue(":date_time", $date_time);
            $stmt_registrar->bindValue(":id_motivo", $id_motivo);
            $stmt_registrar->bindValue(":id_usuario", $id_usuario);
            $stmt_registrar->bindValue(":id_aluno", $id_aluno);

            if ($stmt_registrar->execute()) {
                return 0; // Sucesso
            } else {
                return 3; // Erro interno
            }
        } catch (Exception $e) {
            error_log('Erro ao registrar entrada: ' . $e->getMessage());
            return 3;
        }
    }

    public function cadastrarAluno($nome, $matricula, $idTurma, $idCurso)
    {
        try {
            $stmt = $this->connect->prepare('SELECT id_aluno FROM aluno WHERE matricula = :matricula LIMIT 1');
            $stmt->execute(['matricula' => $matricula]);
            if ($stmt->fetchColumn()) {
                return 1;
            }

            $stmt = $this->connect->prepare('SELECT id_turma FROM turma WHERE id_turma = :id_turma');
            $stmt->execute(['id_turma' => $idTurma]);
            if (!$stmt->fetchColumn()) {
                return 2;
            }

            $stmt = $this->connect->prepare('SELECT id_curso FROM curso WHERE id_curso = :id_curso');
            $stmt->execute(['id_curso' => $idCurso]);
            if (!$stmt->fetchColumn()) {
                return 2;
            }

            $stmt = $this->connect->prepare(
                'INSERT INTO aluno (id_turma, matricula, nome, id_curso, ano_corrente)
                 VALUES (:id_turma, :matricula, :nome, :id_curso, YEAR(CURDATE()))'
            );
            $stmt->execute([
                'id_turma' => $idTurma,
                'matricula' => $matricula,
                'nome' => $nome,
                'id_curso' => $idCurso
            ]);

            return 0;
        } catch (PDOException $e) {
            error_log('Erro ao cadastrar aluno: ' . $e->getMessage());
            return 3;
        }
    }

    public function validarJustificativaAtraso($idRegistro, $decisao, $observacao, $nomeValidador)
    {
        if (!in_array($decisao, ['aprovada', 'recusada'], true)
            || !is_string($observacao)
            || !in_array($nomeValidador, ['Rosana', 'Adriana'], true)
            || ($decisao === 'recusada' && trim($observacao) === '')
            || strlen($observacao) > 2000) {
            return false;
        }

        $colunas = $this->connect->query("SHOW COLUMNS FROM registro_entrada")->fetchAll(PDO::FETCH_COLUMN);
        $necessarias = ['status_justificativa', 'observacao_validacao', 'validado_por', 'validado_em'];
        if (count(array_intersect($necessarias, $colunas)) !== count($necessarias)) {
            return false;
        }

        $sql = "UPDATE registro_entrada r
                INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                SET r.status_justificativa = :decisao,
                    r.observacao_validacao = :observacao,
                    r.validado_por = :validador,
                    r.validado_em = NOW()
                WHERE r.id_registro_entrada = :id_registro
                  AND r.status_justificativa = 'pendente'
                  AND DATE(r.date_time) = CURDATE()
                  AND TIME(r.date_time) BETWEEN '07:40:00' AND '11:40:59'
                  AND a.id_turma IN (9, 10, 11, 12)";
        $stmt = $this->connect->prepare($sql);
        $stmt->execute([
            'decisao' => $decisao,
            'observacao' => $observacao !== '' ? $observacao : null,
            'validador' => $nomeValidador,
            'id_registro' => $idRegistro
        ]);

        return $stmt->rowCount() === 1;
    }

    public function editarSaidaAntecipada($idRegistro, $idAluno, $nomeResponsavel, $idTipoResponsavel, $nomeConducente, $idTipoConducente, $idMotivo, $dateTime)
    {
        try {
            $referencias = [
                ['aluno', 'id_aluno', $idAluno],
                ['tipo_responsavel', 'id_tipo_responsavel', $idTipoResponsavel],
                ['tipo_conducente', 'id_tipo_conducente', $idTipoConducente],
                ['motivo', 'id_motivo', $idMotivo]
            ];
            foreach ($referencias as [$tabela, $coluna, $id]) {
                $stmt = $this->connect->prepare("SELECT 1 FROM {$tabela} WHERE {$coluna} = :id LIMIT 1");
                $stmt->execute(['id' => $id]);
                if (!$stmt->fetchColumn()) {
                    return 'invalid';
                }
            }

            $stmt = $this->connect->prepare(
                'SELECT id_aluno, DATE(date_time) AS registro_data
                 FROM registro_saida WHERE id_registro_saida = :id LIMIT 1'
            );
            $stmt->execute(['id' => $idRegistro]);
            $registroAtual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$registroAtual) {
                return 'not_found';
            }

            $dataRegistro = substr($dateTime, 0, 10);
            if ((int) $registroAtual['id_aluno'] !== $idAluno || $registroAtual['registro_data'] !== $dataRegistro) {
                $stmt = $this->connect->prepare(
                    'SELECT 1 FROM registro_saida
                     WHERE id_aluno = :id_aluno AND DATE(date_time) = DATE(:date_time)
                       AND id_registro_saida <> :id_registro LIMIT 1'
                );
                $stmt->execute([
                    'id_aluno' => $idAluno,
                    'date_time' => $dateTime,
                    'id_registro' => $idRegistro
                ]);
                if ($stmt->fetchColumn()) {
                    return 'duplicate';
                }
            }

            $stmt = $this->connect->prepare(
                'UPDATE registro_saida
                 SET id_aluno = :id_aluno,
                     nome_responsavel = :nome_responsavel,
                     id_tipo_responsavel = :id_tipo_responsavel,
                     nome_conducente = :nome_conducente,
                     id_tipo_conducente = :id_tipo_conducente,
                     id_motivo = :id_motivo,
                     date_time = :date_time
                 WHERE id_registro_saida = :id_registro'
            );
            $stmt->execute([
                'id_aluno' => $idAluno,
                'nome_responsavel' => $nomeResponsavel,
                'id_tipo_responsavel' => $idTipoResponsavel,
                'nome_conducente' => $nomeConducente,
                'id_tipo_conducente' => $idTipoConducente,
                'id_motivo' => $idMotivo,
                'date_time' => $dateTime,
                'id_registro' => $idRegistro
            ]);

            return 'success';
        } catch (PDOException $e) {
            error_log('Erro ao editar saída antecipada: ' . $e->getMessage());
            return 'error';
        }
    }

    public function editarSaidaEstagio($idRegistro, $idAluno, $dateTime)
    {
        try {
            $stmt = $this->connect->prepare('SELECT 1 FROM aluno WHERE id_aluno = :id_aluno LIMIT 1');
            $stmt->execute(['id_aluno' => $idAluno]);
            if (!$stmt->fetchColumn()) {
                return 'invalid';
            }

            $stmt = $this->connect->prepare(
                'SELECT id_aluno, DATE(dae) AS registro_data
                 FROM saida_estagio WHERE id_saida = :id LIMIT 1'
            );
            $stmt->execute(['id' => $idRegistro]);
            $registroAtual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$registroAtual) {
                return 'not_found';
            }

            $dataRegistro = substr($dateTime, 0, 10);
            if ((int) $registroAtual['id_aluno'] !== $idAluno || $registroAtual['registro_data'] !== $dataRegistro) {
                $colunasEntrada = $this->connect->query('SHOW COLUMNS FROM registro_entrada')->fetchAll(PDO::FETCH_COLUMN);
                $temStatusJustificativa = in_array('status_justificativa', $colunasEntrada, true);
                $condicaoStatus = $temStatusJustificativa
                    ? " AND (r.status_justificativa IS NULL OR r.status_justificativa <> 'aprovada')"
                    : '';
                $stmt = $this->connect->prepare(
                    "SELECT 1 FROM registro_entrada r
                     INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                     WHERE r.id_aluno = :id_aluno AND DATE(r.date_time) = :data
                       AND TIME(r.date_time) BETWEEN '07:40:00' AND '11:40:59'
                       AND a.id_turma IN (9, 10, 11, 12) {$condicaoStatus}
                     LIMIT 1"
                );
                $stmt->execute(['id_aluno' => $idAluno, 'data' => $dataRegistro]);
                if ($stmt->fetchColumn()) {
                    return 'justification';
                }

                $stmt = $this->connect->prepare(
                    'SELECT 1 FROM saida_estagio
                     WHERE id_aluno = :id_aluno AND DATE(dae) = DATE(:dae)
                       AND id_saida <> :id_saida LIMIT 1'
                );
                $stmt->execute([
                    'id_aluno' => $idAluno,
                    'dae' => $dateTime,
                    'id_saida' => $idRegistro
                ]);
                if ($stmt->fetchColumn()) {
                    return 'duplicate';
                }
            }

            $stmt = $this->connect->prepare(
                'UPDATE saida_estagio SET id_aluno = :id_aluno, dae = :dae WHERE id_saida = :id_saida'
            );
            $stmt->execute([
                'id_aluno' => $idAluno,
                'dae' => $dateTime,
                'id_saida' => $idRegistro
            ]);

            return 'success';
        } catch (PDOException $e) {
            error_log('Erro ao editar saída de estágio: ' . $e->getMessage());
            return 'error';
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

                                $colunasEntrada = $this->connect->query("SHOW COLUMNS FROM registro_entrada")->fetchAll(PDO::FETCH_COLUMN);
                                $temStatusJustificativa = in_array('status_justificativa', $colunasEntrada, true);
                                $condicaoStatus = $temStatusJustificativa ? " AND (r.status_justificativa IS NULL OR r.status_justificativa <> 'aprovada')" : '';
                                $sqlAtrasoNaoAprovado = "SELECT r.id_registro_entrada
                                                                                 FROM registro_entrada r
                                                                                 INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                                                                                 WHERE r.id_aluno = :id_aluno
                                                                                     AND DATE(r.date_time) = CURDATE()
                                                                                     AND TIME(r.date_time) BETWEEN '07:40:00' AND '11:40:59'
                                                                                     AND a.id_turma IN (9, 10, 11, 12)
                                                                                     {$condicaoStatus}
                                                                                 LIMIT 1";
                                $consultaAtraso = $this->connect->prepare($sqlAtrasoNaoAprovado);
                                $consultaAtraso->execute(['id_aluno' => $id_aluno]);
                                if ($consultaAtraso->fetch(PDO::FETCH_ASSOC)) {
                                        return 4;
                                }


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




                $verificarRegistro = $this->connect->prepare(
                    'SELECT id_saida FROM saida_estagio WHERE id_aluno = :id_aluno AND DATE(dae) = CURDATE() LIMIT 1'
                );
                $verificarRegistro->execute(['id_aluno' => $id_aluno]);
                if ($verificarRegistro->fetchColumn()) {
                    return 1;
                }

                // Query SQL para inserir id_aluno e date_time na coluna dae
                $registrar = "INSERT INTO saida_estagio (id_aluno, dae) VALUES (:id_aluno, :dae)";
                $query = $this->connect->prepare($registrar);

                // Vincula os parâmetros
                $query->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
                $query->bindValue(":dae", $date_time, PDO::PARAM_STR);

                $query->execute();
                return 0; 
            } else {

                return 2;
            }
        } catch (PDOException $e) {
            return 3;
        }
    }
    
    
    
    
    
    
    

    
    
    
    
    
};
