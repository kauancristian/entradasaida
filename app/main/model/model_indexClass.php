<?php

require_once(__DIR__ . '/../config/Database.php');

class MainModel extends connect
{
    private $ultimoErroSaidaEstagio = null;
    private $ultimoErroAtrasoQr = null;
    private $mensagemRestricao = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function obterCodigoErroSaidaEstagio()
    {
        return $this->ultimoErroSaidaEstagio;
    }

    public function obterCodigoErroAtrasoQr()
    {
        return $this->ultimoErroAtrasoQr;
    }

    public function obterMensagemRestricao()
    {
        return $this->mensagemRestricao;
    }

    private function usuarioSessaoValido($usuarioSessao)
    {
        return is_array($usuarioSessao)
            && isset($usuarioSessao['id'], $usuarioSessao['email'], $usuarioSessao['nome'])
            && is_string($usuarioSessao['id'])
            && is_string($usuarioSessao['email'])
            && is_string($usuarioSessao['nome'])
            && trim($usuarioSessao['id']) !== ''
            && trim($usuarioSessao['email']) !== ''
            && trim($usuarioSessao['nome']) !== ''
            && $this->comprimentoTexto($usuarioSessao['id']) <= 191
            && $this->comprimentoTexto($usuarioSessao['email']) <= 254
            && $this->comprimentoTexto($usuarioSessao['nome']) <= 300;
    }

    private function comprimentoTexto($texto)
    {
        return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
    }

    private function parametrosUsuarioSessao(array $usuarioSessao)
    {
        return [
            'id_usuario_sessao' => trim($usuarioSessao['id']),
            'usuario_nome' => trim($usuarioSessao['nome']),
            'usuario_email' => trim($usuarioSessao['email'])
        ];
    }

    private function restricaoSemSchema(PDOException $e)
    {
        $sqlState = (string) $e->getCode();
        $codigoMySql = (int) ($e->errorInfo[1] ?? 0);

        return in_array($sqlState, ['42S02', '42S22'], true)
            || in_array($codigoMySql, [1054, 1146], true);
    }

    private function validarRestricaoDaTurma($idAluno, $dateTime)
    {
        $this->mensagemRestricao = '';

        try {
            $consulta = $this->connect->prepare(
                'SELECT r.nome, r.tipo_registro, r.modo, r.hora_inicio, r.hora_fim
                 FROM aluno a
                 INNER JOIN restricao_turma rt ON rt.id_turma = a.id_turma
                 INNER JOIN restricao r ON r.id_restricao = rt.id_restricao
                 WHERE a.id_aluno = :id_aluno AND r.ativo = 1
                 ORDER BY r.nome'
            );
            $consulta->execute(['id_aluno' => (int) $idAluno]);
        } catch (PDOException $e) {
            if ($this->restricaoSemSchema($e)) {
                error_log('Tabelas/colunas de restrição ausentes; validação de horário será ignorada: ' . $e->getMessage());
                return true;
            }
            throw $e;
        }
        $hora = substr((string) $dateTime, 11, 8);

        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $restricao) {
            $inicio = $restricao['hora_inicio'];
            $fim = $restricao['hora_fim'];
            $dentroDoIntervalo = $inicio < $fim
                ? ($hora >= $inicio && $hora < $fim)
                : ($hora >= $inicio || $hora < $fim);
            $violacao = $restricao['modo'] === 'bloquear' ? $dentroDoIntervalo : !$dentroDoIntervalo;
            if ($violacao) {
                $this->mensagemRestricao = $restricao['modo'] === 'bloquear'
                    ? 'A restrição "' . $restricao['nome'] . '" bloqueia este registro neste horário.'
                    : 'A restrição "' . $restricao['nome'] . '" permite este registro somente entre '
                        . substr($inicio, 0, 5) . ' e ' . substr($fim, 0, 5) . '.';
                return false;
            }
        }

        return true;
    }

    public function priorizarEntradaQr($idAluno)
    {
        try {
            $consulta = $this->connect->prepare(
                "SELECT r.id_restricao
                 FROM aluno a
                 INNER JOIN restricao_turma rt ON rt.id_turma = a.id_turma
                 INNER JOIN restricao r ON r.id_restricao = rt.id_restricao
                 WHERE a.id_aluno = :id_aluno
                   AND r.ativo = 1
                   AND r.tipo_registro IN ('entrada', 'todos')
                 ORDER BY r.nome"
            );
            $consulta->execute(['id_aluno' => (int) $idAluno]);
        } catch (PDOException $e) {
            if ($this->restricaoSemSchema($e)) {
                error_log('Tabelas/colunas de restrição ausentes; prioridade de entrada será ignorada: ' . $e->getMessage());
                return false;
            }
            error_log('Erro ao verificar prioridade de entrada para o QR: ' . $e->getMessage());
            throw new RuntimeException('Falha ao verificar a restrição da turma.', 0, $e);
        }

        return (bool) $consulta->fetchColumn();
    }

    public function priorizarSaidaEstagioQr($idAluno, $dateTime)
    {
        try {
            $consulta = $this->connect->prepare(
                "SELECT r.modo, r.hora_inicio, r.hora_fim
                 FROM aluno a
                 INNER JOIN restricao_turma rt ON rt.id_turma = a.id_turma
                 INNER JOIN restricao r ON r.id_restricao = rt.id_restricao
                 WHERE a.id_aluno = :id_aluno
                   AND r.ativo = 1
                   AND r.tipo_registro IN ('estagio', 'todos')
                 ORDER BY r.nome"
            );
            $consulta->execute(['id_aluno' => (int) $idAluno]);
        } catch (PDOException $e) {
            if ($this->restricaoSemSchema($e)) {
                error_log('Tabelas/colunas de restrição ausentes; prioridade de saída será ignorada: ' . $e->getMessage());
                return false;
            }
            error_log('Erro ao verificar prioridade de saída para estágio no QR: ' . $e->getMessage());
            throw new RuntimeException('Falha ao verificar a restrição da turma.', 0, $e);
        }

        $hora = substr((string) $dateTime, 11, 8);
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $restricao) {
            if ($restricao['modo'] !== 'permitir') {
                continue;
            }

            $inicio = $restricao['hora_inicio'];
            $fim = $restricao['hora_fim'];
            $dentroDoIntervalo = $inicio < $fim
                ? ($hora >= $inicio && $hora < $fim)
                : ($hora >= $inicio || $hora < $fim);
            if ($dentroDoIntervalo) {
                return true;
            }
        }

        return false;
    }

    public function registrarSaida($nome_responsavel, $nome_conducente, $id_tipo_conducente, $id_tipo_responsavel, $date_time, $id_motivo, $usuarioSessao, $id_aluno)
    {
        try{
            if (!$this->usuarioSessaoValido($usuarioSessao)) {
                return 3;
            }
            // Verifica se o aluno existe
            $stmt_check_aluno = $this->connect->prepare("SELECT id_aluno FROM aluno WHERE id_aluno = :id_aluno");
            $stmt_check_aluno->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
            $stmt_check_aluno->execute();
            if ($stmt_check_aluno->rowCount() === 0) {
                return 2; // Aluno não encontrado
            }
            if (!$this->validarRestricaoDaTurma($id_aluno, $date_time)) {
                return 4;
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
                    id_usuario_sessao,
                    usuario_nome,
                    usuario_email,
                    id_aluno
                ) VALUES (
                    :nome_responsavel,
                    :nome_conducente,
                    :id_tipo_conducente,
                    :id_tipo_responsavel,
                    :date_time,
                    :id_motivo,
                    NULL,
                    :id_usuario_sessao,
                    :usuario_nome,
                    :usuario_email,
                    :id_aluno
                )
            ");
            $stmt_registrar->bindValue(":nome_responsavel", $nome_responsavel);
            $stmt_registrar->bindValue(":nome_conducente", $nome_conducente);
            $stmt_registrar->bindValue(":id_tipo_conducente", $id_tipo_conducente);
            $stmt_registrar->bindValue(":id_tipo_responsavel", $id_tipo_responsavel);
            $stmt_registrar->bindValue(":date_time", $date_time);
            $stmt_registrar->bindValue(":id_motivo", $id_motivo);
            $stmt_registrar->bindValue(":id_aluno", $id_aluno);
            $parametrosUsuario = $this->parametrosUsuarioSessao($usuarioSessao);
            $stmt_registrar->bindValue(':id_usuario_sessao', $parametrosUsuario['id_usuario_sessao']);
            $stmt_registrar->bindValue(':usuario_nome', $parametrosUsuario['usuario_nome']);
            $stmt_registrar->bindValue(':usuario_email', $parametrosUsuario['usuario_email']);

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
        $usuarioSessao,
        $id_aluno
    ) {

        try{
            if (!$this->usuarioSessaoValido($usuarioSessao)) {
                return 3;
            }
            // Verifica se o aluno existe
            $stmt_check_aluno = $this->connect->prepare("SELECT id_aluno FROM aluno WHERE id_aluno = :id_aluno");
            $stmt_check_aluno->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
            $stmt_check_aluno->execute();
            if ($stmt_check_aluno->rowCount() === 0) {
                return 2; // Aluno não encontrado
            }
            if (!$this->validarRestricaoDaTurma($id_aluno, $date_time)) {
                return 4;
            }
            if (
                !$this->priorizarEntradaQr($id_aluno)
                && $this->priorizarSaidaEstagioQr($id_aluno, $date_time)
            ) {
                $resultadoEstagio = $this->registrarSaidaEstagio((int) $id_aluno, $date_time, $usuarioSessao);
                if ($resultadoEstagio === 0) {
                    return 5;
                }
                if ($resultadoEstagio === 1) {
                    return 6;
                }
                return $resultadoEstagio === 2 ? 2 : 3;
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
                    id_usuario_sessao,
                    usuario_nome,
                    usuario_email,
                    id_aluno
                ) VALUES (
                    :nome_responsavel,
                    :nome_conducente,
                    :id_tipo_conducente,
                    :id_tipo_responsavel,
                    :date_time,
                    :id_motivo,
                    NULL,
                    :id_usuario_sessao,
                    :usuario_nome,
                    :usuario_email,
                    :id_aluno
                )
            ");
            $stmt_registrar->bindValue(":nome_responsavel", $nome_responsavel, $nome_responsavel === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_registrar->bindValue(":nome_conducente", $nome_conducente, $nome_conducente === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_registrar->bindValue(":id_tipo_conducente", $id_tipo_conducente, $id_tipo_conducente === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt_registrar->bindValue(":id_tipo_responsavel", $id_tipo_responsavel, $id_tipo_responsavel === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt_registrar->bindValue(":date_time", $date_time);
            $stmt_registrar->bindValue(":id_motivo", $id_motivo);
            $stmt_registrar->bindValue(":id_aluno", $id_aluno);
            $parametrosUsuario = $this->parametrosUsuarioSessao($usuarioSessao);
            $stmt_registrar->bindValue(':id_usuario_sessao', $parametrosUsuario['id_usuario_sessao']);
            $stmt_registrar->bindValue(':usuario_nome', $parametrosUsuario['usuario_nome']);
            $stmt_registrar->bindValue(':usuario_email', $parametrosUsuario['usuario_email']);

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

    public function validarJustificativaAtraso($idRegistro, $decisao, $nomeValidador, $justificativaAtraso, $idMotivo)
    {
        if (!in_array($decisao, ['aprovada', 'recusada'], true)
            || !is_string($justificativaAtraso)
            || !is_string($nomeValidador)
            || trim($nomeValidador) === ''
            || strlen($nomeValidador) > 100
            || filter_var($idMotivo, FILTER_VALIDATE_INT) === false
            || (int) $idMotivo < 1
            || trim($justificativaAtraso) === ''
            || strlen($justificativaAtraso) > 2000) {
            return false;
        }

        $consultaMotivo = $this->connect->prepare('SELECT 1 FROM motivo WHERE id_motivo = :id_motivo');
        $consultaMotivo->execute(['id_motivo' => (int) $idMotivo]);
        if (!$consultaMotivo->fetchColumn()) {
            return false;
        }

        $colunas = $this->connect->query("SHOW COLUMNS FROM registro_entrada")->fetchAll(PDO::FETCH_COLUMN);
        $necessarias = ['status_justificativa', 'observacao_validacao', 'validado_por', 'validado_em'];
        if (count(array_intersect($necessarias, $colunas)) !== count($necessarias)) {
            return false;
        }

        $condicaoAtrasoSemResponsavel = " OR (
                   COALESCE(r.nome_responsavel, '') = ''
                   AND COALESCE(r.nome_conducente, '') = ''
               )";
        $sql = "UPDATE registro_entrada r
                INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                SET r.status_justificativa = :decisao,
                    r.id_motivo = :id_motivo,
                    r.observacao_validacao = NULL,
                    r.justificativa_atraso = COALESCE(:justificativa_atraso, r.justificativa_atraso),
                    r.validado_por = :validador,
                    r.validado_em = NOW()
                WHERE r.id_registro_entrada = :id_registro
                  AND r.status_justificativa = 'pendente'
                  AND (
                      (TIME(r.date_time) >= '07:40:00' AND TIME(r.date_time) < '11:40:00')
                      {$condicaoAtrasoSemResponsavel}
                  )
                  AND a.id_turma IN (9, 10, 11, 12)";
        $stmt = $this->connect->prepare($sql);
        $stmt->execute([
            'decisao' => $decisao,
            'id_motivo' => (int) $idMotivo,
            'justificativa_atraso' => trim($justificativaAtraso) !== '' ? trim($justificativaAtraso) : null,
            'validador' => trim($nomeValidador),
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
            if (!$this->validarRestricaoDaTurma($idAluno, $dateTime)) {
                return 'restricted';
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
            if (!$this->validarRestricaoDaTurma($idAluno, $dateTime)) {
                return 'restricted';
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
            $identificador = preg_replace('/^[\s-]+/u', '', $identificador) ?? $identificador;
            $identificador = preg_replace('/\s+/u', ' ', $identificador) ?? $identificador;
            if ($identificador === '') {
                return null;
            }

            $stmt = $this->connect->prepare(
                'SELECT a.id_aluno, a.nome, a.matricula, a.id_turma, t.turma AS letra_turma
                 FROM aluno a
                 INNER JOIN turma t ON t.id_turma = a.id_turma
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

                        if ($aluno || ctype_digit($identificador)) {
                                return $aluno ?: null;
                        }

                        $stmt = $this->connect->prepare(
                                "SELECT a.id_aluno, a.nome, a.matricula, a.id_turma, t.turma AS letra_turma
                                 FROM aluno a
                                 INNER JOIN turma t ON t.id_turma = a.id_turma
                                 WHERE a.id_turma IN (9, 10, 11, 12)
                                     AND UPPER(TRIM(a.nome)) LIKE CONCAT('% ', UPPER(:identificador))
                                 ORDER BY a.id_aluno
                                 LIMIT 2"
                        );
                        $stmt->execute(['identificador' => $identificador]);
                        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        return count($matches) === 1 ? $matches[0] : null;
        } catch (PDOException $e) {
            error_log('Erro ao localizar aluno para saída de estágio: ' . $e->getMessage());
            throw new RuntimeException('Falha ao consultar aluno para saída de estágio.', 0, $e);
        }
    }

    public function registrarAtrasoQr($idAluno, $dateTime, $justificativa, $usuarioSessao)
    {
        try {
            if (!$this->usuarioSessaoValido($usuarioSessao)) {
                return 6;
            }
            $justificativa = is_string($justificativa) ? trim($justificativa) : '';
            $horaEntrada = substr((string) $dateTime, 11, 8);
            $priorizarEntrada = $this->priorizarEntradaQr($idAluno);
            if (
                strlen($justificativa) > 2000
                || (!$priorizarEntrada && ($horaEntrada < '07:40:00' || $horaEntrada >= '11:40:00'))
                || !ctype_digit((string) $idAluno)
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
            if (!$this->validarRestricaoDaTurma($idAluno, $dateTime)) {
                return 9;
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
                "SELECT id_tipo_responsavel FROM tipo_responsavel
                 WHERE tipo IS NOT NULL
                 ORDER BY (tipo = 'responsável') DESC, id_tipo_responsavel ASC
                 LIMIT 1"
            )->fetchColumn();
            $tipoConducente = $this->connect->query(
                "SELECT id_tipo_conducente FROM tipo_conducente
                 WHERE tipo IS NOT NULL
                 ORDER BY (tipo = 'responsável') DESC, id_tipo_conducente ASC
                 LIMIT 1"
            )->fetchColumn();
            $motivo = $this->connect->query(
                "SELECT id_motivo FROM motivo
                 WHERE motivo IS NOT NULL
                 ORDER BY (motivo = 'outros') DESC, id_motivo ASC
                 LIMIT 1"
            )->fetchColumn();
            if (!$tipoResponsavel || !$tipoConducente || !$motivo) {
                error_log('Não há opções padrão necessárias para registrar atraso pelo QR Code.');
                return 8;
            }

            $colunasEntrada = $this->connect->query('SHOW COLUMNS FROM registro_entrada')
                ->fetchAll(PDO::FETCH_COLUMN);
            $campos = [
                'nome_responsavel',
                'nome_conducente',
                'id_tipo_conducente',
                'id_tipo_responsavel',
                'date_time',
                'id_motivo',
                'id_usuario',
                'id_usuario_sessao',
                'usuario_nome',
                'usuario_email',
                'id_aluno'
            ];
            $valores = [
                ':nome_responsavel',
                ':nome_conducente',
                ':id_tipo_conducente',
                ':id_tipo_responsavel',
                ':date_time',
                ':id_motivo',
                'NULL',
                ':id_usuario_sessao',
                ':usuario_nome',
                ':usuario_email',
                ':id_aluno'
            ];
            $parametros = [
                'nome_responsavel' => '',
                'nome_conducente' => '',
                'id_tipo_conducente' => (int) $tipoConducente,
                'id_tipo_responsavel' => (int) $tipoResponsavel,
                'date_time' => $dateTime,
                'id_motivo' => (int) $motivo,
                'id_aluno' => (int) $idAluno
            ];
            $parametros = array_merge($parametros, $this->parametrosUsuarioSessao($usuarioSessao));

            if (in_array('justificativa_atraso', $colunasEntrada, true)) {
                $campos[] = 'justificativa_atraso';
                $valores[] = ':justificativa_atraso';
                $parametros['justificativa_atraso'] = $justificativa !== '' ? $justificativa : null;
            }
            if (in_array('status_justificativa', $colunasEntrada, true)) {
                $campos[] = 'status_justificativa';
                $valores[] = "'pendente'";
            }
            if (count(array_intersect(['status_justificativa', 'observacao_validacao', 'validado_por', 'validado_em'], $colunasEntrada)) !== 4) {
                error_log('Atraso QR registrado sem todos os campos opcionais de validação.');
            }

            $registrar = $this->connect->prepare(
                'INSERT INTO registro_entrada (' . implode(', ', $campos) . ')
                 VALUES (' . implode(', ', $valores) . ')'
            );
            $registrar->execute($parametros);

            return 0;
        } catch (PDOException $e) {
            $erroBanco = is_array($e->errorInfo) ? $e->errorInfo : [];
            $sqlState = $erroBanco[0] ?? $e->getCode();
            $codigoDriver = $erroBanco[1] ?? '';
            $sqlState = is_string($sqlState) && preg_match('/^[A-Z0-9]{5}$/', $sqlState) ? $sqlState : 'XXXXX';
            $codigoDriver = is_numeric($codigoDriver) ? (string) $codigoDriver : '0';
            $this->ultimoErroAtrasoQr = $sqlState . '/' . $codigoDriver;
            error_log('Erro ao registrar atraso pelo QR Code [' . $this->ultimoErroAtrasoQr . ']: ' . $e->getMessage());
            return 3;
        }
    }

    public function excluirAtrasoPendenteHoje($idRegistro)
    {
        try {
            $stmt = $this->connect->prepare(
                "DELETE r FROM registro_entrada r
                 WHERE r.id_registro_entrada = :id_registro
                   AND DATE(r.date_time) = CURDATE()
                   AND r.status_justificativa = 'pendente'
                   AND r.id_aluno IN (
                       SELECT a.id_aluno
                       FROM aluno a
                       WHERE a.id_turma IN (9, 10, 11, 12)
                         AND (
                             (TIME(r.date_time) >= '07:40:00' AND TIME(r.date_time) < '11:40:00')
                             OR (
                                 COALESCE(r.nome_responsavel, '') = ''
                                 AND COALESCE(r.nome_conducente, '') = ''
                             )
                         )
                   )"
            );
            $stmt->execute(['id_registro' => (int) $idRegistro]);

            return $stmt->rowCount() === 1 ? 0 : 1;
        } catch (PDOException $e) {
            error_log('Erro ao excluir atraso pendente: ' . $e->getMessage());
            throw new RuntimeException('Falha ao excluir o atraso pendente.', 0, $e);
        }
    }

    public function excluirAtrasoHistorico($idRegistro)
    {
        try {
            $colunas = $this->connect->query('SHOW COLUMNS FROM registro_entrada')->fetchAll(PDO::FETCH_COLUMN);
            $condicaoHistorico = in_array('status_justificativa', $colunas, true)
                ? "(DATE(r.date_time) < CURDATE() OR (DATE(r.date_time) = CURDATE() AND r.status_justificativa IN ('aprovada', 'recusada')))"
                : 'DATE(r.date_time) < CURDATE()';
            $stmt = $this->connect->prepare(
                "DELETE r FROM registro_entrada r
                 INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                 WHERE r.id_registro_entrada = :id_registro
                   AND {$condicaoHistorico}
                   AND a.id_turma IN (9, 10, 11, 12)
                   AND (
                       (TIME(r.date_time) >= '07:40:00' AND TIME(r.date_time) < '11:40:00')
                       OR (
                           COALESCE(r.nome_responsavel, '') = ''
                           AND COALESCE(r.nome_conducente, '') = ''
                       )
                   )"
            );
            $stmt->execute(['id_registro' => (int) $idRegistro]);

            return $stmt->rowCount() === 1 ? 0 : 1;
        } catch (PDOException $e) {
            error_log('Erro ao excluir atraso do histórico: ' . $e->getMessage());
            throw new RuntimeException('Falha ao excluir o atraso do histórico.', 0, $e);
        }
    }

    public function atrasoEstagioHoje($idAluno)
    {
        try {
            $colunas = $this->connect->query('SHOW COLUMNS FROM registro_entrada')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('status_justificativa', $colunas, true)) {
                return null;
            }

            $condicaoAtrasoSemResponsavel = " OR (
                       COALESCE(r.nome_responsavel, '') = ''
                       AND COALESCE(r.nome_conducente, '') = ''
                   )";

            $stmt = $this->connect->prepare(
                "SELECT r.id_registro_entrada, r.date_time,
                        COALESCE(r.status_justificativa, 'pendente') AS status_justificativa
                 FROM registro_entrada r
                 INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                 WHERE r.id_aluno = :id_aluno
                   AND DATE(r.date_time) = CURDATE()
                   AND a.id_turma IN (9, 10, 11, 12)
                   AND (
                       (TIME(r.date_time) >= '07:40:00' AND TIME(r.date_time) < '11:40:00')
                       {$condicaoAtrasoSemResponsavel}
                   )
                 ORDER BY r.date_time DESC
                 LIMIT 1"
            );
            $stmt->execute(['id_aluno' => (int) $idAluno]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            return $registro ?: null;
        } catch (PDOException $e) {
            error_log('Erro ao consultar situação de atraso para estágio: ' . $e->getMessage());
            throw new RuntimeException('Falha ao consultar a validação do atraso.', 0, $e);
        }
    }

    public function statusAtrasoEstagioHoje($idAluno)
    {
        $registro = $this->atrasoEstagioHoje($idAluno);

        return $registro['status_justificativa'] ?? null;
    }

    public function registrarSaidaEstagio($aluno, $date_time, $usuarioSessao = null)
    {
        try {
            if (!$this->usuarioSessaoValido($usuarioSessao)) {
                return 3;
            }
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
                if (!$this->validarRestricaoDaTurma($id_aluno, $date_time)) {
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

                $registrar = 'INSERT INTO saida_estagio (
                                  id_aluno,
                                  dae,
                                  id_usuario_sessao,
                                  usuario_nome,
                                  usuario_email
                              ) VALUES (
                                  :id_aluno,
                                  :dae,
                                  :id_usuario_sessao,
                                  :usuario_nome,
                                  :usuario_email
                              )';
                $query = $this->connect->prepare($registrar);

                $query->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
                $query->bindValue(":dae", $date_time, PDO::PARAM_STR);
                $parametrosUsuario = $this->parametrosUsuarioSessao($usuarioSessao);
                $query->bindValue(':id_usuario_sessao', $parametrosUsuario['id_usuario_sessao']);
                $query->bindValue(':usuario_nome', $parametrosUsuario['usuario_nome']);
                $query->bindValue(':usuario_email', $parametrosUsuario['usuario_email']);

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
