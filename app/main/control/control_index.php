<?php

require_once '../model/model_indexClass.php';
require_once '../model/sessions.php';

date_default_timezone_set('America/Sao_Paulo');

function dataHoraDoRegistroNaoFutura($data, $hora)
{
    $dataHora = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $data . ' ' . $hora);
    $erros = DateTimeImmutable::getLastErrors();
    if (!$dataHora || ($erros && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))) {
        return false;
    }

    return $dataHora <= new DateTimeImmutable();
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
    $date_time = $data . ' ' . $hora . ':00';

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

    $id_aluno = $_POST['id_aluno'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    if (!is_string($data) || !is_string($hora) || !dataHoraDoRegistroNaoFutura($data, $hora)) {
        header('Location: ../views/inicio.php?section=estagio&status=data_hora_futura');
        exit();
    }

    $date_time = $data . ' ' . $hora;

    $obj = new MainModel();
    $result = $obj->registrarSaidaEstagio($id_aluno, $date_time);

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
        default:
    }
    exit();
}

//relatorios 
else if (isset($_POST['GerarRelatorio']) && isset($_POST['tipo_relatorio'])) {
    $gerar_relatorio = $_POST['GerarRelatorio'];
    $tipoRelatorio = $_POST['tipo_relatorio'];
    $id_aluno = $_POST['id_aluno'] ?? 0;
    $id_turma = $_POST['Turma'] ?? 0;
    $ano = $_POST['Ano'] ?? 0;

    switch ($gerar_relatorio) {
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
