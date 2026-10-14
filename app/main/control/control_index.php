<?php

require_once '../model/model_indexClass.php';
require_once '../model/sessions.php';

date_default_timezone_set('America/Sao_Paulo');

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
}

if (in_array($_POST['acao'] ?? '', ['preparar_saida_estagio_qr', 'registrar_saida_estagio_qr'], true)) {
    exigirSessaoOuAcessoLocal();

    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        responderJson(['success' => false, 'message' => 'A sessão expirou. Atualize a página e tente novamente.'], 403);
    }

    $identificadorAluno = $_POST['id_aluno'] ?? '';
    if (!is_string($identificadorAluno) || trim($identificadorAluno) === '') {
        responderJson(['success' => false, 'message' => 'O QR Code não contém a identificação do aluno.'], 400);
    }

    $model = new MainModel();
    try {
        $aluno = $model->obterAlunoPorIdentificadorSaidaEstagio($identificadorAluno);
    } catch (RuntimeException $e) {
        responderJson(['success' => false, 'message' => 'Não foi possível consultar os dados do aluno. Tente novamente.'], 500);
    }
    if (!$aluno) {
        responderJson(['success' => false, 'message' => 'O aluno do QR Code não foi encontrado.'], 404);
    }

    if ($_POST['acao'] === 'preparar_saida_estagio_qr') {
        $agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
        responderJson([
            'success' => true,
            'aluno' => $aluno,
            'data' => $agora->format('Y-m-d'),
            'hora' => $agora->format('H:i:s'),
            'requiresJustification' => $agora->format('H:i:s') < '11:40:00'
        ]);
    }

    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $justificativa = $_POST['justificativa'] ?? '';
    if (
        !is_string($data)
        || !is_string($hora)
        || !is_string($justificativa)
        || strlen($justificativa) > 2000
        || !dataHoraDoRegistroNaoFutura($data, $hora)
    ) {
        responderJson(['success' => false, 'message' => 'Confira a data, o horário e a justificativa informados.'], 400);
    }

    $dateTime = $data . ' ' . (strlen($hora) === 5 ? $hora . ':00' : $hora);
    $resultado = $model->registrarSaidaEstagio((int) $aluno['id_aluno'], $dateTime, $justificativa);
    $statusHttp = $resultado === 0 ? 200 : ($resultado === 3 ? 500 : 400);
    responderJson([
        'success' => $resultado === 0,
        'status' => $resultado,
        'message' => [
            1 => 'Este aluno já possui uma saída para estágio registrada hoje.',
            2 => 'O aluno não foi encontrado.',
            3 => 'Não foi possível registrar a saída. Tente novamente.',
            4 => 'A saída não pode ser registrada porque a justificativa do atraso ainda não foi aprovada.',
            5 => 'Informe a justificativa para uma saída antes das 11h40.'
        ][$resultado] ?? 'A saída para estágio foi registrada.'
    ], $statusHttp);
}

if (($_POST['acao'] ?? '') === 'editar_registro_consulta') {
    $session = new sessions();
    $session->autenticar_session();

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
        'justification' => 'justification',
        'not_found' => 'not_found',
        'invalid' => 'invalid',
        'error' => 'error'
    ][$resultado] ?? 'error';
    header('Location: ' . $redirect . $statusEdicao);
    exit();
}

if (($_POST['acao'] ?? '') === 'validar_justificativa_atraso') {
    $session = new sessions();
    $session->autenticar_session();

    $redirect = '../views/inicio.php?section=atrasos&validacao=erro';
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        header('Location: ' . $redirect);
        exit();
    }

    $idRegistro = filter_var($_POST['id_registro_entrada'] ?? null, FILTER_VALIDATE_INT);
    $decisao = $_POST['decisao'] ?? '';
    $responsavel = is_string($_POST['responsavel_validacao'] ?? null) ? trim($_POST['responsavel_validacao']) : '';
    $observacao = is_string($_POST['observacao_validacao'] ?? null) ? trim($_POST['observacao_validacao']) : '';
    if (!$idRegistro || !in_array($decisao, ['aprovada', 'recusada'], true) || !in_array($responsavel, ['Rosana', 'Adriana'], true) || ($decisao === 'recusada' && $observacao === '') || strlen($observacao) > 2000) {
        header('Location: ' . $redirect);
        exit();
    }

    $model = new MainModel();
    $atualizado = $model->validarJustificativaAtraso($idRegistro, $decisao, $observacao, $responsavel);
    $resultado = $atualizado ? $decisao : 'ja_processada';
    header('Location: ../views/inicio.php?section=atrasos&validacao=' . $resultado);
    exit();
}

if (isset($_POST['cadastrar'])) {
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
    $camposObrigatorios = ['id_aluno', 'nome_responsavel', 'id_tipo_responsavel', 'id_tipo_conducente', 'id_motivo', 'id_usuario', 'data', 'hora'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($_POST[$campo]) || !is_string($_POST[$campo]) || trim($_POST[$campo]) === '') {
            header('Location: ../views/inicio.php?section=entrada&status=campos_obrigatorios');
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
    $id_usuario = trim($_POST['id_usuario']);
    $data = trim($_POST['data']);
    $hora = trim($_POST['hora']);

    if (!dataHoraDoRegistroNaoFutura($data, $hora)) {
        header('Location: ../views/inicio.php?section=entrada&status=data_hora_futura');
        exit();
    }

    // Combina data e hora no formato Y-m-d H:i:s
    $date_time = $data . ' ' . (strlen($hora) === 5 ? $hora . ':00' : $hora);

    // Instancia o modelo e registra a entrada
    $obj = new MainModel();
    $result = $obj->registrarEntrada(
        $nome_responsavel,
        $nome_conducente,
        $id_tipo_conducente,
        $id_tipo_responsavel,
        $date_time,
        $id_motivo,
        $id_usuario,
        $id_aluno
    );

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
        default:
            header('Location: ../views/inicio.php?section=entrada&status=erro_desconhecido');
            exit();
    }
}

//registro saida-estagio//

//saida 
else if (isset($_POST['saida'])) {
    $camposObrigatorios = ['id_aluno', 'nome_responsavel', 'id_tipo_responsavel', 'id_tipo_conducente', 'id_motivo', 'id_usuario', 'data', 'hora'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($_POST[$campo]) || !is_string($_POST[$campo]) || trim($_POST[$campo]) === '') {
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
    $id_usuario = trim($_POST['id_usuario']);
    $data = trim($_POST['data']);
    $hora = trim($_POST['hora']);

    if (!dataHoraDoRegistroNaoFutura($data, $hora)) {
        header('Location: ../views/inicio.php?section=saida&status=data_hora_futura');
        exit();
    }

    // Combina data e hora no formato Y-m-d H:i:s
    $date_time = $data . ' ' . $hora . ':00';

    // Instancia o modelo e registra a entrada
    $obj = new MainModel();
    $result = $obj->registrarSaida(
        $nome_responsavel,
        $nome_conducente,
        $id_tipo_conducente,
        $id_tipo_responsavel,
        $date_time,
        $id_motivo,
        $id_usuario,
        $id_aluno
    );

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
        default:
            header('Location: ../views/inicio.php?section=saida&status=erro_desconhecido');
            exit();
    }
}

else if (isset($_POST['id_aluno']) && !empty($_POST['id_aluno']) && isset($_POST['data']) && !empty($_POST['data']) && isset($_POST['hora']) && !empty($_POST['hora'])) {

    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        header('Location: ../views/inicio.php?section=estagio&status=erro_interno');
        exit();
    }

    $id_aluno = $_POST['id_aluno'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    if (!is_string($data) || !is_string($hora) || !dataHoraDoRegistroNaoFutura($data, $hora)) {
        header('Location: ../views/inicio.php?section=estagio&status=data_hora_futura');
        exit();
    }

    $date_time = $data . ' ' . $hora . ':00';
    $justificativa = $_POST['justificativa'] ?? '';
    if (!is_string($justificativa) || strlen($justificativa) > 2000) {
        header('Location: ../views/inicio.php?section=estagio&status=campos_obrigatorios');
        exit();
    }

    $obj = new MainModel();
    $result = $obj->registrarSaidaEstagio($id_aluno, $date_time, $justificativa);

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
            header('Location: ../views/inicio.php?section=estagio&status=justificativa_nao_aprovada');
            exit();
        case 5:
            header('Location: ../views/inicio.php?section=estagio&status=justificativa_obrigatoria');
            exit();
        default:
            header('Location: ../views/inicio.php?section=estagio&status=erro_desconhecido');
            exit();
    }
}

//relatorios 
else if (isset($_POST['GerarRelatorio']) && isset($_POST['tipo_relatorio'])) {
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
