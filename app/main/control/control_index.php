<?php

require_once __DIR__ . '/../model/model_indexClass.php';
require_once __DIR__ . '/../model/sessions.php';
require_once __DIR__ . '/../model/select_model.php';
require_once __DIR__ . '/../model/RestrictionModel.php';

date_default_timezone_set('America/Sao_Paulo');

// Ative novamente após os testes para restaurar o desvio do QR no horário de atraso.
const QR_ATRASO_HABILITADO = true;

function dataHoraDoRegistroNaoFutura($data, $hora)
{
    if (!preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $hora)) {
        return false;
    }
    $horaCompleta = strlen($hora) === 5 ? $hora . ':00' : $hora;
    $dataHora = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $data . ' ' . $horaCompleta);
    $erros = DateTimeImmutable::getLastErrors();
    if (!$dataHora || ($erros && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))) {
        return false;
    }

    return $dataHora <= new DateTimeImmutable();
}

function responderJson(array $dados, int $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

function exigirPermissaoRecepcao($permitido, $respostaJson = false)
{
    if (!(new sessions())->usuarioAtualEhRecepcao() || $permitido) {
        return;
    }

    if ($respostaJson) {
        responderJson(['success' => false, 'message' => 'Seu perfil não tem acesso a esta função.'], 403);
    }

    header('Location: ../views/inicio.php?section=entrada&status=acesso_negado', true, 303);
    exit();
}

function exigirSessaoOuAcessoLocal()
{
    $host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    $hostsLocais = ['localhost', '127.0.0.1', '::1'];
    $ipsLocais = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
    $acessoLocal = in_array(strtolower((string) $host), $hostsLocais, true)
        && in_array($_SERVER['REMOTE_ADDR'] ?? '', $ipsLocais, true);

    if (!$acessoLocal) {
        (new sessions())->autenticar_session();
    }

    return $acessoLocal;
}

function obterDadosUsuarioSessao()
{
    return (new sessions())->obterDadosUsuarioAtual();
}

if (isset($_POST['restriction_action'])) {
    exigirSessaoOuAcessoLocal();
    exigirPermissaoRecepcao(false);
    $redirect = '../views/inicio.php?section=restricoes&restriction_status=';
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        header('Location: ' . $redirect . 'invalid', true, 303);
        exit();
    }

    try {
        $restrictionModel = new RestrictionModel();
        $action = $_POST['restriction_action'];
        if ($action === 'save') {
            $id = is_string($_POST['id_restricao'] ?? null) && $_POST['id_restricao'] !== ''
                ? $_POST['id_restricao']
                : null;
            $restrictionModel->salvarRestricao(
                $id,
                $_POST['nome'] ?? null,
                $_POST['tipo_registro'] ?? '',
                $_POST['modo'] ?? '',
                $_POST['hora_inicio'] ?? '',
                $_POST['hora_fim'] ?? ''
            );
            $status = 'success';
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id_restricao'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || !$restrictionModel->excluirRestricao($id)) {
                throw new InvalidArgumentException('Restrição não encontrada.');
            }
            $status = 'deleted';
        } elseif ($action === 'assign') {
            $ids = $_POST['restricoes'] ?? [];
            if (!is_array($ids)) {
                throw new InvalidArgumentException('A seleção de restrições é inválida.');
            }
            foreach ($ids as $restrictionId) {
                if (!is_string($restrictionId) || !ctype_digit($restrictionId)) {
                    throw new InvalidArgumentException('A seleção de restrições é inválida.');
                }
            }
            $restrictionModel->salvarRestricoesDaTurma($_POST['id_turma'] ?? null, $ids);
            $status = 'assigned';
        } else {
            throw new InvalidArgumentException('Ação inválida.');
        }
    } catch (InvalidArgumentException $e) {
        $status = 'invalid';
    } catch (Throwable $e) {
        error_log('Erro ao atualizar restrições: ' . $e->getMessage());
        $status = 'error';
    }

    header('Location: ' . $redirect . $status, true, 303);
    exit();
}

if (in_array($_POST['acao'] ?? '', ['preparar_saida_estagio_qr', 'registrar_saida_estagio_qr', 'registrar_atraso_qr', 'listar_atrasos_hoje', 'listar_saidas_estagio_hoje', 'listar_consulta_saidas_estagio', 'listar_consulta_saidas_antecipadas', 'listar_ausencias_estagio', 'excluir_saida_estagio_qr', 'excluir_atraso_pendente_qr', 'excluir_atraso_historico', 'excluir_registro_consulta'], true)) {
    $acessoLocal = exigirSessaoOuAcessoLocal();
    exigirPermissaoRecepcao(in_array($_POST['acao'], [
        'preparar_saida_estagio_qr',
        'registrar_saida_estagio_qr',
        'registrar_atraso_qr',
        'listar_ausencias_estagio'
    ], true), true);

    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        responderJson(['success' => false, 'message' => 'A sessão expirou. Atualize a página e tente novamente.'], 403);
    }

    if ($_POST['acao'] === 'listar_saidas_estagio_hoje') {
        try {
            $select = new select_model();
            responderJson(['success' => true, 'saidas' => $select->saidasEstagioHojePorTurma()]);
        } catch (RuntimeException $e) {
            error_log('Erro ao atualizar as saídas de estágio via QR Code: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'A saída foi salva, mas não foi possível atualizar a lista.'], 500);
        }
    }

    if ($_POST['acao'] === 'listar_atrasos_hoje') {
        try {
            $select = new select_model();
            responderJson(['success' => true, 'atrasos' => $select->atrasosHoje()]);
        } catch (RuntimeException $e) {
            error_log('Erro ao atualizar atrasos via QR Code: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível atualizar a lista de atrasos.'], 500);
        }
    }

    if ($_POST['acao'] === 'listar_atrasos_hoje') {
        try {
            $select = new select_model();
            responderJson(['success' => true, 'atrasos' => $select->atrasosHoje()]);
        } catch (RuntimeException $e) {
            error_log('Erro ao atualizar atrasos via QR Code: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível atualizar a lista de atrasos.'], 500);
        }
    }

    if ($_POST['acao'] === 'listar_consulta_saidas_estagio') {
        try {
            $select = new select_model();
            responderJson(['success' => true, 'saidas' => $select->saidasEstagio()]);
        } catch (RuntimeException $e) {
            error_log('Erro ao atualizar a consulta de saídas de estágio: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível atualizar a consulta de saídas de estágio.'], 500);
        }
    }

    if ($_POST['acao'] === 'listar_consulta_saidas_antecipadas') {
        try {
            $select = new select_model();
            responderJson(['success' => true, 'saidas' => $select->saidasAntecipadas()]);
        } catch (PDOException $e) {
            error_log('Erro ao atualizar a consulta de saídas antecipadas: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível atualizar a consulta de saídas antecipadas.'], 500);
        }
    }

    if ($_POST['acao'] === 'listar_ausencias_estagio') {
        $data = $_POST['data'] ?? '';
        $dataSelecionada = is_string($data)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $data, new DateTimeZone('America/Sao_Paulo'))
            : false;
        $errosData = DateTimeImmutable::getLastErrors();
        if (
            !$dataSelecionada
            || ($errosData && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0))
            || $dataSelecionada->format('Y-m-d') !== $data
        ) {
            responderJson(['success' => false, 'message' => 'Selecione uma data válida.'], 400);
        }

        $agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
        if ($dataSelecionada > $agora->setTime(0, 0, 0)) {
            responderJson(['success' => false, 'message' => 'Não é possível consultar uma data futura.'], 400);
        }

        $idTurma = null;
        if (isset($_POST['id_turma']) && $_POST['id_turma'] !== '') {
            $idTurma = filter_var($_POST['id_turma'], FILTER_VALIDATE_INT);
            if (!$idTurma || !in_array($idTurma, [9, 10, 11, 12], true)) {
                responderJson(['success' => false, 'message' => 'Selecione uma turma válida.'], 400);
            }
        }

        $apuracaoDisponivel = $dataSelecionada->format('Y-m-d') !== $agora->format('Y-m-d')
            || $agora->format('H:i:s') >= '13:20:00';
        if (!$apuracaoDisponivel) {
            responderJson([
                'success' => true,
                'apuracao_disponivel' => false,
                'ausencias' => [],
                'message' => 'A apuração das ausências de hoje estará disponível após 13h20.'
            ]);
        }

        try {
            $select = new select_model();
            $ausencias = $select->alunosAusentesEstagioNaData($data, $idTurma);
            responderJson([
                'success' => true,
                'apuracao_disponivel' => true,
                'ausencias' => $ausencias,
                'message' => ''
            ]);
        } catch (RuntimeException $e) {
            error_log('Erro ao consultar ausências de estágio: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível consultar as ausências.'], 500);
        }
    }

    if ($_POST['acao'] === 'excluir_saida_estagio_qr') {
        $idSaida = filter_var($_POST['id_saida'] ?? null, FILTER_VALIDATE_INT);
        if (!$idSaida || $idSaida < 1) {
            responderJson(['success' => false, 'message' => 'O registro selecionado é inválido.'], 400);
        }

        try {
            $model = new MainModel();
            $resultado = $model->excluirSaidaEstagioHoje($idSaida);
        } catch (RuntimeException $e) {
            error_log('Erro ao excluir saída de estágio via QR Code: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível excluir o registro. Tente novamente.'], 500);
        }
        if ($resultado !== 0) {
            responderJson(['success' => false, 'message' => 'O registro não está mais disponível para exclusão. Atualize a lista.'], 404);
        }

        responderJson(['success' => true, 'message' => 'Registro excluído. O aluno já pode ser registrado novamente.']);
    }

    if ($_POST['acao'] === 'excluir_atraso_pendente_qr') {
        $idRegistro = filter_var($_POST['id_registro_entrada'] ?? null, FILTER_VALIDATE_INT);
        if (!$idRegistro || $idRegistro < 1) {
            responderJson(['success' => false, 'message' => 'O atraso selecionado é inválido.'], 400);
        }

        try {
            $model = new MainModel();
            $resultado = $model->excluirAtrasoPendenteHoje($idRegistro);
        } catch (RuntimeException $e) {
            error_log('Erro ao excluir atraso pendente via QR Code: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível excluir o atraso. Tente novamente.'], 500);
        }
        if ($resultado !== 0) {
            responderJson(['success' => false, 'message' => 'Este atraso já foi validado ou não está mais disponível para exclusão. Atualize a lista.'], 409);
        }

        responderJson(['success' => true, 'message' => 'Atraso pendente excluído.']);
    }

    if ($_POST['acao'] === 'excluir_atraso_historico') {
        $idRegistro = filter_var($_POST['id_registro_entrada'] ?? null, FILTER_VALIDATE_INT);
        if (!$idRegistro || $idRegistro < 1) {
            responderJson(['success' => false, 'message' => 'O atraso selecionado é inválido.'], 400);
        }

        try {
            $model = new MainModel();
            $resultado = $model->excluirAtrasoHistorico($idRegistro);
        } catch (RuntimeException $e) {
            error_log('Erro ao excluir atraso do histórico: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível excluir o atraso. Tente novamente.'], 500);
        }
        if ($resultado !== 0) {
            responderJson(['success' => false, 'message' => 'O atraso não está mais disponível para exclusão. Atualize o histórico.'], 404);
        }

        responderJson(['success' => true, 'message' => 'Atraso excluído do histórico.']);
    }

    if ($_POST['acao'] === 'excluir_registro_consulta') {
        $idRegistro = filter_var($_POST['id_registro'] ?? null, FILTER_VALIDATE_INT);
        $tipoRegistro = $_POST['tipo_registro'] ?? '';
        if (!$idRegistro || $idRegistro < 1 || !in_array($tipoRegistro, ['saida', 'estagio'], true)) {
            responderJson(['success' => false, 'message' => 'O registro selecionado é inválido.'], 400);
        }

        try {
            $model = new MainModel();
            $resultado = $model->excluirRegistroConsulta($tipoRegistro, $idRegistro);
        } catch (RuntimeException $e) {
            error_log('Erro ao excluir registro da consulta: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível excluir o registro. Tente novamente.'], 500);
        }
        if ($resultado !== 0) {
            responderJson(['success' => false, 'message' => 'O registro não está mais disponível para exclusão. Atualize a consulta.'], 404);
        }

        responderJson(['success' => true, 'message' => 'Registro excluído com sucesso.']);
    }

    $identificadorAluno = $_POST['id_aluno'] ?? '';
    if (!is_string($identificadorAluno) || trim($identificadorAluno) === '') {
        responderJson(['success' => false, 'message' => 'O QR Code não contém a identificação do aluno.'], 400);
    }

    try {
        $model = new MainModel();
        $aluno = $model->obterAlunoPorIdentificadorSaidaEstagio($identificadorAluno);
    } catch (RuntimeException $e) {
        error_log('Erro ao preparar saída de estágio por QR Code: ' . $e->getMessage());
        responderJson(['success' => false, 'message' => 'Não foi possível consultar os dados do aluno. Tente novamente.'], 500);
    }
    if (!$aluno) {
        responderJson(['success' => false, 'message' => 'O aluno do QR Code não foi encontrado.'], 404);
    }

    if ($_POST['acao'] === 'preparar_saida_estagio_qr') {
        $agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
        $horaAtual = $agora->format('H:i:s');
        try {
            $registroAtraso = $model->atrasoEstagioHoje((int) $aluno['id_aluno']);
            $statusAtraso = $registroAtraso['status_justificativa'] ?? null;
            $priorizarEntrada = $model->priorizarEntradaQr((int) $aluno['id_aluno']);
            $priorizarSaidaEstagio = $model->priorizarSaidaEstagioQr(
                (int) $aluno['id_aluno'],
                $agora->format('Y-m-d H:i:s')
            );
        } catch (RuntimeException $e) {
            error_log('Erro ao consultar validação do atraso pelo QR Code: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível verificar a aptidão para estágio. Tente novamente.'], 500);
        }
        responderJson([
            'success' => true,
            'aluno' => $aluno,
            'data' => $agora->format('Y-m-d'),
            'hora' => $horaAtual,
            'isDelayWindow' => $priorizarEntrada
                || (
                    QR_ATRASO_HABILITADO
                    && $horaAtual >= '07:40:00'
                    && $horaAtual < '11:40:00'
                    && !$priorizarSaidaEstagio
                ),
            'priorizarSaidaEstagio' => $priorizarSaidaEstagio,
            'delayStatus' => $statusAtraso,
            'delayRecord' => $registroAtraso
                ? ['date_time' => $registroAtraso['date_time']]
                : null
        ]);
    }

    if ($_POST['acao'] === 'registrar_atraso_qr') {
        $agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
        $horaAtual = $agora->format('H:i:s');
        try {
            $priorizarEntrada = $model->priorizarEntradaQr((int) $aluno['id_aluno']);
            if (
                !$priorizarEntrada
                && (!QR_ATRASO_HABILITADO || $horaAtual < '07:40:00' || $horaAtual >= '11:40:00')
            ) {
                responderJson(['success' => false, 'message' => 'O registro de atraso pelo QR não está disponível neste horário.'], 409);
            }
            $statusAtraso = $model->statusAtrasoEstagioHoje((int) $aluno['id_aluno']);
        } catch (RuntimeException $e) {
            error_log('Erro ao verificar atraso antes do registro QR: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível verificar o atraso do aluno. Tente novamente.'], 500);
        }
        if ($statusAtraso !== null) {
            responderJson(['success' => false, 'message' => 'Este aluno já possui um atraso registrado hoje.'], 409);
        }

        $usuarioSessao = obterDadosUsuarioSessao();
        if (!$usuarioSessao) {
            responderJson([
                'success' => false,
                'message' => 'Não há um usuário autenticado na sessão para registrar o atraso. Entre novamente no sistema.'
            ], 403);
        }

        try {
            $priorizarSaidaEstagio = $model->priorizarSaidaEstagioQr(
                (int) $aluno['id_aluno'],
                $agora->format('Y-m-d H:i:s')
            );
        } catch (RuntimeException $e) {
            error_log('Erro ao verificar prioridade de saída antes do atraso QR: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível verificar as restrições da turma.'], 500);
        }
        if ($priorizarSaidaEstagio && !$priorizarEntrada) {
            responderJson([
                'success' => false,
                'message' => 'A restrição desta turma direciona o QR para saída de estágio neste horário.'
            ], 409);
        }

        try {
            $resultado = $model->registrarAtrasoQr(
                (int) $aluno['id_aluno'],
                $agora->format('Y-m-d H:i:s'),
                '',
                $usuarioSessao
            );
        } catch (RuntimeException $e) {
            error_log('Erro ao gravar atraso QR: ' . $e->getMessage());
            responderJson(['success' => false, 'message' => 'Não foi possível registrar o atraso. Tente novamente.'], 500);
        }
        if ($resultado === 9) {
            responderJson(['success' => false, 'message' => $model->obterMensagemRestricao()], 409);
        }
        if ($resultado !== 0) {
            $codigoErroBanco = $resultado === 3 ? $model->obterCodigoErroAtrasoQr() : null;
            $mensagens = [
                1 => 'Este aluno já possui uma entrada registrada hoje.',
                2 => 'O aluno não pertence a uma das turmas de estágio autorizadas.',
                3 => 'Falha no banco ao registrar o atraso'
                    . ($codigoErroBanco ? ' (' . $codigoErroBanco . ')' : '')
                    . '. Avise o suporte com esse código.',
                5 => 'O horário informado não está dentro do período de atraso.',
                6 => 'Não há um usuário autenticado na sessão para registrar este atraso.',
                7 => 'A estrutura do banco ainda não está pronta para validar atrasos. Execute a migration necessária.',
                8 => 'Faltam opções padrão de responsável ou motivo no banco.'
            ];
            $statusHttp = $resultado === 3 ? 500 : ($resultado === 6 ? 403 : 409);
            responderJson([
                'success' => false,
                'message' => $mensagens[$resultado] ?? 'Não foi possível registrar o atraso.'
            ], $statusHttp);
        }

        responderJson(['success' => true, 'message' => 'Atraso registrado e aguardando validação.']);
    }

    try {
        $statusAtraso = $model->statusAtrasoEstagioHoje((int) $aluno['id_aluno']);
        $priorizarSaidaEstagio = $model->priorizarSaidaEstagioQr(
            (int) $aluno['id_aluno'],
            (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s')
        );
    } catch (RuntimeException $e) {
        error_log('Erro ao validar aptidão para estágio pelo QR Code: ' . $e->getMessage());
        responderJson(['success' => false, 'message' => 'Não foi possível verificar a aptidão para estágio. Tente novamente.'], 500);
    }
    if (!$priorizarSaidaEstagio && in_array($statusAtraso, ['pendente', 'recusada'], true)) {
        $mensagemAptidao = $statusAtraso === 'recusada'
            ? 'A justificativa do atraso foi recusada. Este aluno não está apto para estagiar hoje.'
            : 'O atraso está aguardando validação. O estágio só será liberado após a aprovação.';
        responderJson(['success' => false, 'message' => $mensagemAptidao], 409);
    }

    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    if (
        !is_string($data)
        || !is_string($hora)
        || !dataHoraDoRegistroNaoFutura($data, $hora)
    ) {
        responderJson(['success' => false, 'message' => 'Confira a data e o horário informados.'], 400);
    }

    $dateTime = $data . ' ' . (strlen($hora) === 5 ? $hora . ':00' : $hora);
    $usuarioSessao = obterDadosUsuarioSessao();
    if (!$usuarioSessao) {
        responderJson(['success' => false, 'message' => 'Não há um usuário autenticado na sessão para registrar a saída. Entre novamente no sistema.'], 403);
    }
    $resultado = $model->registrarSaidaEstagio((int) $aluno['id_aluno'], $dateTime, $usuarioSessao);
    if ($resultado === 4) {
        responderJson(['success' => false, 'status' => $resultado, 'message' => $model->obterMensagemRestricao()], 409);
    }
    $statusHttp = $resultado === 0 ? 200 : ($resultado === 3 ? 500 : 400);
    $mensagemFalhaBanco = 'Não foi possível registrar a saída. Tente novamente.';
    if ($resultado === 3) {
        $codigoErroBanco = $model->obterCodigoErroSaidaEstagio();
        $mensagemFalhaBanco = 'Falha no banco ao registrar a saída'
            . ($codigoErroBanco ? ' (' . $codigoErroBanco . ')' : '')
            . '. Avise o suporte com esse código.';
    }
    responderJson([
        'success' => $resultado === 0,
        'status' => $resultado,
        'message' => [
            1 => 'Este aluno já possui uma saída para estágio registrada hoje.',
            2 => 'O aluno não foi encontrado.',
            3 => $mensagemFalhaBanco
        ][$resultado] ?? 'A saída para estágio foi registrada.'
    ], $statusHttp);
}

if (($_POST['acao'] ?? '') === 'editar_registro_consulta') {
    $session = new sessions();
    $session->autenticar_session();
    exigirPermissaoRecepcao(false);

    $tipoRegistro = $_POST['tipo_registro'] ?? '';
    $section = $tipoRegistro === 'estagio' ? 'consulta-estagio' : 'consulta-saida';
    $redirect = '../views/inicio.php?section=' . $section . '&edit_status=';
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        header('Location: ' . $redirect . 'invalid');
        exit();
    }

    $idRegistro = filter_var($_POST['id_registro'] ?? null, FILTER_VALIDATE_INT);
    $idAluno = filter_var($_POST['id_aluno'] ?? null, FILTER_VALIDATE_INT);
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    if (
        !in_array($tipoRegistro, ['saida', 'estagio'], true)
        || !$idRegistro
        || !$idAluno
        || !is_string($data)
        || !is_string($hora)
        || !dataHoraDoRegistroNaoFutura($data, $hora)
    ) {
        header('Location: ' . $redirect . 'invalid');
        exit();
    }

    $dateTime = $data . ' ' . (strlen($hora) === 5 ? $hora . ':00' : $hora);
    $model = new MainModel();
    if ($tipoRegistro === 'estagio') {
        $resultado = $model->editarSaidaEstagio($idRegistro, $idAluno, $dateTime);
    } else {
        $nomeResponsavel = $_POST['nome_responsavel'] ?? null;
        $nomeConducente = $_POST['nome_conducente'] ?? null;
        $idTipoResponsavel = filter_var($_POST['id_tipo_responsavel'] ?? null, FILTER_VALIDATE_INT);
        $idTipoConducente = filter_var($_POST['id_tipo_conducente'] ?? null, FILTER_VALIDATE_INT);
        $idMotivo = filter_var($_POST['id_motivo'] ?? null, FILTER_VALIDATE_INT);
        if (
            !is_string($nomeResponsavel)
            || !is_string($nomeConducente)
            || trim($nomeResponsavel) === ''
            || strlen($nomeResponsavel) > 100
            || strlen($nomeConducente) > 100
            || !$idTipoResponsavel
            || !$idTipoConducente
            || !$idMotivo
        ) {
            header('Location: ' . $redirect . 'invalid');
            exit();
        }

        $resultado = $model->editarSaidaAntecipada(
            $idRegistro,
            $idAluno,
            trim($nomeResponsavel),
            $idTipoResponsavel,
            trim($nomeConducente),
            $idTipoConducente,
            $idMotivo,
            $dateTime
        );
    }

    $statusEdicao = [
        'success' => 'success',
        'duplicate' => 'duplicate',
        'not_found' => 'not_found',
        'restricted' => 'restricted',
        'invalid' => 'invalid',
        'error' => 'error'
    ][$resultado] ?? 'error';
    header('Location: ' . $redirect . $statusEdicao);
    exit();
}

if (($_POST['acao'] ?? '') === 'validar_justificativa_atraso') {
    exigirSessaoOuAcessoLocal();
    exigirPermissaoRecepcao(false);

    $redirect = '../views/inicio.php?section=atrasos&validacao=erro';
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        header('Location: ' . $redirect);
        exit();
    }

    $idRegistro = filter_var($_POST['id_registro_entrada'] ?? null, FILTER_VALIDATE_INT);
    $idMotivo = filter_var($_POST['id_motivo'] ?? null, FILTER_VALIDATE_INT);
    $decisao = $_POST['decisao'] ?? '';
    $responsavel = is_string($_SESSION['nome'] ?? null) ? trim($_SESSION['nome']) : '';
    $justificativa = is_string($_POST['justificativa_atraso'] ?? null) ? trim($_POST['justificativa_atraso']) : '';
    if (!$idRegistro || !$idMotivo || !in_array($decisao, ['aprovada', 'recusada'], true) || $responsavel === '' || strlen($responsavel) > 100 || $justificativa === '' || strlen($justificativa) > 2000) {
        header('Location: ' . $redirect);
        exit();
    }

    $model = new MainModel();
    $atualizado = $model->validarJustificativaAtraso($idRegistro, $decisao, $responsavel, $justificativa, $idMotivo);
    $resultado = $atualizado ? $decisao : 'ja_processada';
    header('Location: ../views/inicio.php?section=atrasos&validacao=' . $resultado);
    exit();
}

if (isset($_POST['cadastrar'])) {
    exigirPermissaoRecepcao(false);
    $nome = $_POST['nome'] ?? null;
    $matricula = $_POST['matricula'] ?? null;
    $idTurma = filter_var($_POST['id_turma'] ?? null, FILTER_VALIDATE_INT);
    $idCurso = filter_var($_POST['id_curso'] ?? null, FILTER_VALIDATE_INT);
    $redirect = '../views/inicio.php?section=cadastro&status=';

    if (!is_string($nome) || !is_string($matricula)) {
        header('Location: ' . $redirect . 'campos_invalidos');
        exit();
    }

    $nome = trim($nome);
    $matricula = trim($matricula);
    if (
        $nome === ''
        || strlen($nome) > 900
        || !preg_match('/^[\p{L}\p{M}\s\'-]+$/u', $nome)
        || !preg_match('/^\d{7}$/', $matricula)
        || !$idTurma
        || !$idCurso
    ) {
        header('Location: ' . $redirect . 'campos_invalidos');
        exit();
    }

    $resultado = (new MainModel())->cadastrarAluno($nome, $matricula, $idTurma, $idCurso);
    $status = [
        0 => 'success',
        1 => 'matricula_duplicada',
        2 => 'turma_curso_invalidos',
        3 => 'erro_interno'
    ][$resultado] ?? 'erro_interno';
    header('Location: ' . $redirect . $status);
    exit();
}

//entradas

if (isset($_POST['entrada'])) {
    exigirPermissaoRecepcao(true);
    $acessoLocal = exigirSessaoOuAcessoLocal();
    if ($acessoLocal) {
        (new sessions())->simularLoginLocalParaTeste();
    }
    $respostaAssincrona = ($_POST['entrada_ajax'] ?? '') === '1';
    $camposObrigatorios = ['id_aluno', 'id_motivo'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($_POST[$campo]) || !is_string($_POST[$campo]) || trim($_POST[$campo]) === '') {
            if ($respostaAssincrona) {
                responderJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.'], 422);
            }
            header('Location: ../views/inicio.php?section=entrada&status=campos_obrigatorios');
            exit();
        }
    }

    // Registros de atraso não exigem responsável ou acompanhante.
    $id_aluno = trim($_POST['id_aluno']);
    $nome_responsavel = null;
    $id_tipo_responsavel = null;
    $nome_conducente = null;
    $id_tipo_conducente = null;
    $id_motivo = trim($_POST['id_motivo']);
    $usuarioSessao = obterDadosUsuarioSessao();
    if (!$usuarioSessao) {
        if ($respostaAssincrona) {
            responderJson(['success' => false, 'message' => 'Não há um usuário autenticado para registrar este atraso. Entre novamente no sistema.'], 403);
        }
        header('Location: ../views/inicio.php?section=entrada&status=usuario_inativo');
        exit();
    }
    $date_time = (new DateTimeImmutable())->format('Y-m-d H:i:s');

    // Instancia o modelo e registra a entrada
    $obj = new MainModel();
    $result = $obj->registrarEntrada(
        $nome_responsavel,
        $nome_conducente,
        $id_tipo_conducente,
        $id_tipo_responsavel,
        $date_time,
        $id_motivo,
        $usuarioSessao,
        $id_aluno
    );

    if ($respostaAssincrona) {
        $mensagensResultado = [
            0 => 'O atraso do aluno foi registrado com sucesso.',
            1 => 'Já existe um registro para este aluno nesta data.',
            2 => 'O aluno selecionado não foi encontrado.',
            3 => 'Não foi possível salvar no banco. Confira os dados e tente novamente.',
            4 => $obj->obterMensagemRestricao(),
            5 => 'A restrição da turma direcionou o registro para saída de estágio.',
            6 => 'Este aluno já possui uma saída para estágio registrada hoje.'
        ];
        $sucesso = $result === 0 || $result === 5;
        responderJson([
            'success' => $sucesso,
            'message' => $mensagensResultado[$result] ?? 'O registro não foi concluído. Tente novamente.',
            'tipo_registro' => $result === 5 ? 'estagio' : 'entrada'
        ], $sucesso ? 200 : ($result === 3 ? 500 : (in_array($result, [1, 6], true) ? 409 : 422)));
    }

    // Redireciona com base no resultado
    switch ($result) {
        case 0:
            header('Location: ../views/inicio.php?section=entrada&status=success');
            exit();
        case 1:
            header('Location: ../views/inicio.php?section=entrada&status=ja_registrado');
            exit();
        case 2:
            header('Location: ../views/inicio.php?section=entrada&status=aluno_nao_encontrado');
            exit();
        case 3:
            header('Location: ../views/inicio.php?section=entrada&status=erro_interno');
            exit();
        case 4:
            header('Location: ../views/inicio.php?section=entrada&status=restricao_bloqueada');
            exit();
        case 5:
            header('Location: ../views/inicio.php?section=ultimas-saidas&status=success');
            exit();
        case 6:
            header('Location: ../views/inicio.php?section=ultimas-saidas&status=ja_registrado');
            exit();
        default:
            header('Location: ../views/inicio.php?section=entrada&status=erro_desconhecido');
            exit();
    }
}

//registro saida-estagio//

//saida 
else if (isset($_POST['saida'])) {
    exigirPermissaoRecepcao(true);
    $acessoLocal = exigirSessaoOuAcessoLocal();
    if ($acessoLocal) {
        (new sessions())->simularLoginLocalParaTeste();
    }
    $respostaAssincrona = ($_POST['saida_ajax'] ?? '') === '1';
    $camposObrigatorios = ['id_aluno', 'nome_responsavel', 'id_tipo_responsavel', 'id_tipo_conducente', 'id_motivo'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($_POST[$campo]) || !is_string($_POST[$campo]) || trim($_POST[$campo]) === '') {
            if ($respostaAssincrona) {
                responderJson(['success' => false, 'message' => 'Preencha todos os campos obrigatórios.'], 422);
            }
            header('Location: ../views/inicio.php?section=saida&status=campos_obrigatorios');
            exit();
        }
    }

    // Atribui os valores do $_POST às variáveis
    $id_aluno = trim($_POST['id_aluno']);
    $nome_responsavel = trim($_POST['nome_responsavel']);
    $id_tipo_responsavel = trim($_POST['id_tipo_responsavel']);
    $nome_conducente = trim($_POST['nome_conducente']);
    $id_tipo_conducente = trim($_POST['id_tipo_conducente']);
    $id_motivo = trim($_POST['id_motivo']);
    $usuarioSessao = obterDadosUsuarioSessao();
    if (!$usuarioSessao) {
        if ($respostaAssincrona) {
            responderJson(['success' => false, 'message' => 'Não há um usuário autenticado para registrar esta saída. Entre novamente no sistema.'], 403);
        }
        header('Location: ../views/inicio.php?section=saida&status=usuario_inativo');
        exit();
    }

    $date_time = (new DateTimeImmutable())->format('Y-m-d H:i:s');

    // Instancia o modelo e registra a entrada
    $obj = new MainModel();
    $result = $obj->registrarSaida(
        $nome_responsavel,
        $nome_conducente,
        $id_tipo_conducente,
        $id_tipo_responsavel,
        $date_time,
        $id_motivo,
        $usuarioSessao,
        $id_aluno
    );

    if ($respostaAssincrona) {
        $mensagensResultado = [
            0 => 'A saída do aluno foi registrada com sucesso.',
            1 => 'Já existe um registro para este aluno nesta data.',
            2 => 'O aluno selecionado não foi encontrado.',
            3 => 'Não foi possível salvar no banco. Confira os dados e tente novamente.',
            4 => $obj->obterMensagemRestricao()
        ];
        $sucesso = $result === 0;
        responderJson([
            'success' => $sucesso,
            'message' => $mensagensResultado[$result] ?? 'O registro não foi concluído. Tente novamente.'
        ], $sucesso ? 200 : ($result === 3 ? 500 : 422));
    }

    // Redireciona com base no resultado
    switch ($result) {
        case 0:
            header('Location: ../views/inicio.php?section=saida&status=success');
            exit();
        case 1:
            header('Location: ../views/inicio.php?section=saida&status=ja_registrado');
            exit();
        case 2:
            header('Location: ../views/inicio.php?section=saida&status=aluno_nao_encontrado');
            exit();
        case 3:
            header('Location: ../views/inicio.php?section=saida&status=erro_interno');
            exit();
        case 4:
            header('Location: ../views/inicio.php?section=saida&status=restricao_bloqueada');
            exit();
        default:
            header('Location: ../views/inicio.php?section=saida&status=erro_desconhecido');
            exit();
    }
}

else if (isset($_POST['id_aluno']) && !empty($_POST['id_aluno']) && isset($_POST['data']) && !empty($_POST['data']) && isset($_POST['hora']) && !empty($_POST['hora'])) {
    exigirPermissaoRecepcao(true);

    $respostaAssincrona = ($_POST['saida_estagio_ajax'] ?? '') === '1';
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        if ($respostaAssincrona) {
            responderJson(['success' => false, 'message' => 'A sessão expirou. Atualize a página e tente novamente.'], 403);
        }
        header('Location: ../views/inicio.php?section=estagio&status=erro_interno');
        exit();
    }

    $id_aluno = $_POST['id_aluno'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    if (!is_string($data) || !is_string($hora) || !dataHoraDoRegistroNaoFutura($data, $hora)) {
        if ($respostaAssincrona) {
            responderJson(['success' => false, 'message' => 'Não é permitido registrar uma data ou horário futuro. Escolha um horário até o momento atual.'], 422);
        }
        header('Location: ../views/inicio.php?section=estagio&status=data_hora_futura');
        exit();
    }

    $date_time = $data . ' ' . $hora . ':00';
    $obj = new MainModel();
    $usuarioSessao = obterDadosUsuarioSessao();
    if (!$usuarioSessao) {
        if ($respostaAssincrona) {
            responderJson(['success' => false, 'message' => 'Não há um usuário autenticado para registrar a saída. Entre novamente no sistema.'], 403);
        }
        header('Location: ../views/inicio.php?section=estagio&status=usuario_inativo');
        exit();
    }
    $result = $obj->registrarSaidaEstagio($id_aluno, $date_time, $usuarioSessao);

    if ($respostaAssincrona) {
        $mensagensResultado = [
            0 => 'A saída do aluno para estágio foi registrada com sucesso.',
            1 => 'Este aluno já possui uma saída para estágio registrada hoje.',
            2 => 'O aluno selecionado não foi encontrado.',
            3 => 'Não foi possível salvar no banco. Confira os dados e tente novamente.',
            4 => $obj->obterMensagemRestricao()
        ];
        responderJson([
            'success' => $result === 0,
            'message' => $mensagensResultado[$result] ?? 'O registro não foi concluído. Tente novamente.'
        ], $result === 0 ? 200 : ($result === 3 ? 500 : 422));
    }

    switch ($result) {
        case 0:
            header('Location: ../views/inicio.php?section=estagio&status=success');
            exit();
        case 1:
            header('Location: ../views/inicio.php?section=estagio&status=ja_registrado');
            exit();
        case 2:
            header('Location: ../views/inicio.php?section=estagio&status=aluno_nao_encontrado');
            exit();
        case 3:
            header('Location: ../views/inicio.php?section=estagio&status=erro_interno');
            exit();
        case 4:
            header('Location: ../views/inicio.php?section=estagio&status=restricao_bloqueada');
            exit();
        default:
            header('Location: ../views/inicio.php?section=estagio&status=erro_desconhecido');
            exit();
    }
}

//relatorios 
else if (isset($_POST['GerarRelatorio']) && isset($_POST['tipo_relatorio'])) {
    exigirPermissaoRecepcao(false);
    $gerar_relatorio = $_POST['GerarRelatorio'];
    $tipoRelatorio = $_POST['tipo_relatorio'];
    $id_aluno = $_POST['id_aluno'] ?? 0;
    $id_turma = $_POST['Turma'] ?? 0;
    $ano = $_POST['Ano'] ?? 0;

    switch ($gerar_relatorio) {
        case 'atrasos_por_aluno':
        case 'atrasos_por_turma':
        case 'atrasos_por_ano':
            $periodosAtrasos = ['dia_atual', 'ultimos_30_dias', 'ultimos_12_meses'];
            if (!in_array($tipoRelatorio, $periodosAtrasos, true)) {
                header('Location: ../views/inicio.php?section=relatorio-dia&error=invalid_period');
                exit();
            }

            if ($gerar_relatorio === 'atrasos_por_aluno') {
                $filtroAtraso = filter_var($id_aluno, FILTER_VALIDATE_INT);
                $escopoAtraso = 'aluno';
                $parametroAtraso = 'id_aluno';
            } elseif ($gerar_relatorio === 'atrasos_por_turma') {
                $filtroAtraso = filter_var($id_turma, FILTER_VALIDATE_INT);
                $escopoAtraso = 'turma';
                $parametroAtraso = 'id_turma';
                if (!in_array($filtroAtraso, [9, 10, 11, 12], true)) {
                    $filtroAtraso = false;
                }
            } else {
                $filtroAtraso = filter_var($ano, FILTER_VALIDATE_INT);
                $escopoAtraso = 'ano';
                $parametroAtraso = 'ano';
                if ($filtroAtraso !== 3) {
                    $filtroAtraso = false;
                }
            }

            if (!$filtroAtraso) {
                header('Location: ../views/inicio.php?section=relatorio-dia&error=invalid_filter');
                exit();
            }

            header(
                'Location: ../views/relatorios/relatorio_atrasos_pdf.php?escopo=' .
                urlencode($escopoAtraso) . '&' . $parametroAtraso . '=' .
                urlencode($filtroAtraso) . '&tipo_relatorio=' . urlencode($tipoRelatorio)
            );
            exit();

        case 'por_alunoEstagio':
            $alunoEstagio = filter_var($id_aluno, FILTER_VALIDATE_INT);
            if (!$alunoEstagio) {
                header('Location: ../views/relatorios/relatorioSaida_Estagio.php?error=invalid_aluno');
                exit();
            }
            header('Location: ../views/relatorios/saida_estagio_pdf.php?escopo=aluno&id_aluno=' . urlencode($alunoEstagio) . '&tipo_relatorio=' . urlencode($tipoRelatorio));
            exit();

        case '3_ano_geralEstagio':
            header('Location: ../views/relatorios/saida_estagio_pdf.php?escopo=ano&ano=3&tipo_relatorio=' . urlencode($tipoRelatorio));
            exit();

        case 'por_turmaEstagio':
            $turmaEstagio = filter_var($id_turma, FILTER_VALIDATE_INT);
            if (!$turmaEstagio || !in_array($turmaEstagio, [9, 10, 11, 12], true)) {
                header('Location: ../views/relatorios/relatorioSaida_Estagio.php?error=invalid_turma');
                exit();
            }
            header('Location: ../views/relatorios/saida_estagio_pdf.php?escopo=turma&id_turma=' . urlencode($turmaEstagio) . '&tipo_relatorio=' . urlencode($tipoRelatorio));
            exit();

        case 'por_aluno':
            header('location:../views/relatorios/aluno_individual/aluno_individual.php?id_aluno=' . urlencode($id_aluno) . '&tipo_relatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'por_alunoEntrada':
            header('location:../views/relatorios/aluno_individual/aluno_individualEntrada.php?id_aluno=' . urlencode($id_aluno) . '&tipo_relatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'por_alunoSaida':
            header('location:../views/relatorios/aluno_individual/aluno_individualSaida.php?id_aluno=' . urlencode($id_aluno) . '&tipo_relatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case '3_ano_geral':
            header('location:../views/relatorios/ano_geral/ano_geral.php?id_aluno=' . urlencode($id_aluno) . '&tipoRelatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'ano_geralEntrada':
            if ($ano == 0) {
                echo "Erro: Selecione um ano válido!";
                exit();
            }
            header('location:../views/relatorios/ano_geralEntrada.php?ano=' . urlencode($ano) . '&tipo_relatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'ano_geralSaida':
            if ($ano == 0) {
                echo "Erro: Selecione um ano válido!";
                exit();
            }
            header('location:../views/relatorios/ano_geralSaida.php?ano=' . urlencode($ano) . '&tipo_relatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'por_turma':
            if ($id_turma == 0) {
                echo "Erro: Selecione uma turma válida!";
                exit();
            }
            header('location:../views/relatorios/por_turma/por_turma.php?id_turma=' . urlencode($id_turma) . '&tipoRelatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'por_turmaEntrada':
            if ($id_turma == 0) {
                echo "Erro: Selecione uma turma válida!";
                exit();
            }
            header('location:../views/relatorios/por_turma/por_turmaEntrada.php?id_turma=' . urlencode($id_turma) . '&tipoRelatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'por_turmaSaida':
            if ($id_turma == 0) {
                echo "Erro: Selecione uma turma válida!";
                exit();
            }
            header('location:../views/relatorios/por_turma/por_turmaSaida.php?id_turma=' . urlencode($id_turma) . '&tipoRelatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        default:
            echo "Tipo de relatório inválido!";
            return;
    }
} else {
    /*header('location:../views/inicio.php');
    exit();*/
}
