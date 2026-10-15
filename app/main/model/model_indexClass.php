<?php

require_once(__DIR__ . '/../config/Database.php');

class MainModel extends connect
{
    private $ultimoErroSaidaEstagio = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function obterCodigoErroSaidaEstagio()
    {
        return $this->ultimoErroSaidaEstagio;
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
                  AND TIME(r.date_time) >= '07:40:00'
                  AND TIME(r.date_time) < '11:40:00'
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

    public function obterAlunoPorIdentificadorSaidaEstagio($identificador)
    {
        try {
            $identificador = trim(rawurldecode(str_replace('+', ' ', (string) $identificador)));
            if ($identificador === '') {
                return null;
            }

            $stmt = $this->connect->prepare(
                'SELECT a.id_aluno, a.nome, a.matricula
                 FROM aluno a
                 WHERE CAST(a.id_aluno AS CHAR) = :identificador_id
                    OR a.nome = :identificador_nome
                    OR a.matricula = :identificador_matricula
                 ORDER BY (a.id_turma IN (9, 10, 11, 12)) DESC,
                          (CAST(a.id_aluno AS CHAR) = :ordem_id) DESC,
                          (a.matricula = :ordem_matricula) DESC
                 LIMIT 1'
            );
            $stmt->execute([
                'identificador_id' => $identificador,
                'identificador_nome' => $identificador,
                'identificador_matricula' => $identificador,
                'ordem_id' => $identificador,
                'ordem_matricula' => $identificador
            ]);
            $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

            return $aluno ?: null;
        } catch (PDOException $e) {
            error_log('Erro ao localizar aluno para saída de estágio: ' . $e->getMessage());
            throw new RuntimeException('Falha ao consultar aluno para saída de estágio.', 0, $e);
        }
    }

    public function obterUsuarioAtivoParaQr()
    {
        try {
            $idUsuario = $this->connect->query(
                "SELECT id_usuario FROM usuario WHERE Status = '1' ORDER BY id_usuario LIMIT 1"
            )->fetchColumn();

            return $idUsuario ? (int) $idUsuario : null;
        } catch (PDOException $e) {
            error_log('Erro ao localizar operador ativo para QR Code: ' . $e->getMessage());
            throw new RuntimeException('Falha ao localizar operador para QR Code.', 0, $e);
        }
    }

    public function registrarAtrasoQr($idAluno, $dateTime, $justificativa, $idUsuario)
    {
        try {
            $justificativa = is_string($justificativa) ? trim($justificativa) : '';
            $horaEntrada = substr((string) $dateTime, 11, 8);
            if (
                strlen($justificativa) > 2000
                || $horaEntrada < '07:40:00'
                || $horaEntrada >= '11:40:00'
                || !ctype_digit((string) $idAluno)
                || !ctype_digit((string) $idUsuario)
            ) {
                return 5;
            }

            $verificarAluno = $this->connect->prepare(
                'SELECT id_aluno FROM aluno WHERE id_aluno = :id_aluno AND id_turma IN (9, 10, 11, 12) LIMIT 1'
            );
            $verificarAluno->execute(['id_aluno' => (int) $idAluno]);
            if (!$verificarAluno->fetchColumn()) {
                return 2;
            }

            $verificarUsuario = $this->connect->prepare(
                "SELECT id_usuario FROM usuario WHERE id_usuario = :id_usuario AND Status = '1' LIMIT 1"
            );
            $verificarUsuario->execute(['id_usuario' => (int) $idUsuario]);
            if (!$verificarUsuario->fetchColumn()) {
                return 6;
            }

            $verificarRegistro = $this->connect->prepare(
                'SELECT id_registro_entrada
                 FROM registro_entrada
                 WHERE id_aluno = :id_aluno AND DATE(date_time) = DATE(:date_time)
                 LIMIT 1'
            );
            $verificarRegistro->execute([
                'id_aluno' => (int) $idAluno,
                'date_time' => $dateTime
            ]);
            if ($verificarRegistro->fetchColumn()) {
                return 1;
            }

            $tipoResponsavel = $this->connect->query(
                "SELECT id_tipo_responsavel FROM tipo_responsavel WHERE tipo = 'responsável' LIMIT 1"
            )->fetchColumn();
            $tipoConducente = $this->connect->query(
                "SELECT id_tipo_conducente FROM tipo_conducente WHERE tipo = 'responsável' LIMIT 1"
            )->fetchColumn();
            $motivo = $this->connect->query(
                "SELECT id_motivo FROM motivo WHERE motivo = 'outros' LIMIT 1"
            )->fetchColumn();
            if (!$tipoResponsavel || !$tipoConducente || !$motivo) {
                error_log('Não há opções padrão necessárias para registrar atraso pelo QR Code.');
                return 8;
            }

            $colunasEntrada = $this->connect->query('SHOW COLUMNS FROM registro_entrada')
                ->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('justificativa_atraso', $colunasEntrada, true)) {
                error_log('A coluna justificativa_atraso ainda não existe em registro_entrada.');
                return 7;
            }
            $incluiStatusJustificativa = in_array('status_justificativa', $colunasEntrada, true);
            $campos = 'nome_responsavel, nome_conducente, id_tipo_conducente, id_tipo_responsavel,
                       date_time, id_motivo, id_usuario, id_aluno, justificativa_atraso';
            $valores = ':nome_responsavel, :nome_conducente, :id_tipo_conducente, :id_tipo_responsavel,
                        :date_time, :id_motivo, :id_usuario, :id_aluno, :justificativa_atraso';
            if ($incluiStatusJustificativa) {
                $campos .= ', status_justificativa';
                $valores .= ", 'pendente'";
            }

            $registrar = $this->connect->prepare(
                "INSERT INTO registro_entrada ({$campos}) VALUES ({$valores})"
            );
            $registrar->execute([
                'nome_responsavel' => null,
                'nome_conducente' => null,
                'id_tipo_conducente' => (int) $tipoConducente,
                'id_tipo_responsavel' => (int) $tipoResponsavel,
                'date_time' => $dateTime,
                'id_motivo' => (int) $motivo,
                'id_usuario' => (int) $idUsuario,
                'id_aluno' => (int) $idAluno,
                'justificativa_atraso' => $justificativa !== '' ? $justificativa : null
            ]);

            return 0;
        } catch (PDOException $e) {
            error_log('Erro ao registrar atraso pelo QR Code: ' . $e->getMessage());
            return 3;
        }
    }

    public function registrarSaidaEstagio($aluno, $date_time)
    {
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




                $verificarRegistro = $this->connect->prepare(
                    'SELECT id_saida FROM saida_estagio WHERE id_aluno = :id_aluno AND DATE(dae) = CURDATE() LIMIT 1'
                );
                $verificarRegistro->execute(['id_aluno' => $id_aluno]);
                if ($verificarRegistro->fetchColumn()) {
                    return 1;
                }

                $registrar = 'INSERT INTO saida_estagio (id_aluno, dae)
                              VALUES (:id_aluno, :dae)';
                $query = $this->connect->prepare($registrar);

                $query->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
                $query->bindValue(":dae", $date_time, PDO::PARAM_STR);

                $query->execute();
                return 0; 
            } else {

                return 2;
            }
        } catch (PDOException $e) {
            $erroBanco = is_array($e->errorInfo) ? $e->errorInfo : [];
            $sqlState = $erroBanco[0] ?? $e->getCode();
            $codigoDriver = $erroBanco[1] ?? '';
            $sqlState = is_string($sqlState) && preg_match('/^[A-Z0-9]{5}$/', $sqlState) ? $sqlState : 'XXXXX';
            $codigoDriver = is_numeric($codigoDriver) ? (string) $codigoDriver : '0';
            $this->ultimoErroSaidaEstagio = $sqlState . '/' . $codigoDriver;
            error_log(
                'Erro ao registrar saída de estágio [' . $this->ultimoErroSaidaEstagio . ']: '
                . $e->getMessage()
            );
            return 3;
        }
    }

    public function excluirSaidaEstagioHoje($idSaida)
    {
        try {
            $stmt = $this->connect->prepare(
                'DELETE FROM saida_estagio
                 WHERE id_saida = :id_saida
                   AND DATE(dae) = CURDATE()
                   AND id_aluno IN (
                       SELECT id_aluno FROM aluno WHERE id_turma IN (9, 10, 11, 12)
                   )'
            );
            $stmt->execute(['id_saida' => (int) $idSaida]);

            return $stmt->rowCount() === 1 ? 0 : 1;
        } catch (PDOException $e) {
            error_log('Erro ao excluir saída de estágio: ' . $e->getMessage());
            throw new RuntimeException('Falha ao excluir saída de estágio.', 0, $e);
        }
    }

    public function excluirRegistroConsulta($tipoRegistro, $idRegistro)
    {
        if ($tipoRegistro === 'estagio') {
            $sql = 'DELETE FROM saida_estagio WHERE id_saida = :id_registro';
        } elseif ($tipoRegistro === 'saida') {
            $sql = 'DELETE FROM registro_saida WHERE id_registro_saida = :id_registro';
        } else {
            return 1;
        }

        try {
            $stmt = $this->connect->prepare($sql);
            $stmt->execute(['id_registro' => (int) $idRegistro]);

            return $stmt->rowCount() === 1 ? 0 : 1;
        } catch (PDOException $e) {
            error_log('Erro ao excluir registro da consulta: ' . $e->getMessage());
            throw new RuntimeException('Falha ao excluir registro da consulta.', 0, $e);
        }
    }
    
    
};
