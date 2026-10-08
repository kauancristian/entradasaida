<?php

require_once '../model/model_indexClass.php';

//entradas

if (
    isset($_POST['id_aluno']) && !empty(trim($_POST['id_aluno'])) &&
    isset($_POST['id_tipo_responsavel']) && !empty(trim($_POST['id_tipo_responsavel'])) &&
    isset($_POST['id_tipo_conducente']) && !empty(trim($_POST['id_tipo_conducente'])) &&
    isset($_POST['id_motivo']) && !empty(trim($_POST['id_motivo'])) &&
    isset($_POST['id_usuario']) && !empty(trim($_POST['id_usuario'])) &&
    isset($_POST['data']) && !empty(trim($_POST['data'])) &&
    isset($_POST['hora']) && !empty(trim($_POST['hora']))
) {
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
            header('Location: ../views/entradas/registro_entrada.php?status=success');
            exit();
        case 1:
            header('Location: ../views/entradas/registro_entrada.php?status=ja_registrado');
            exit();
        case 2:
            header('Location: ../views/entradas/registro_entrada.php?status=aluno_nao_encontrado');
            exit();
        case 3:
            header('Location: ../views/entradas/registro_entrada.php?status=erro_interno');
            exit();
        default:
            header('Location: ../views/entradas/registro_entrada.php?status=erro_desconhecido');
            exit();
    }
}

//registro saida-estagio//

//saida 
else if (
    isset($_POST['id_aluno']) && !empty(trim($_POST['id_aluno'])) &&
    isset($_POST['id_tipo_responsavel']) && !empty(trim($_POST['id_tipo_responsavel'])) &&
    isset($_POST['id_tipo_conducente']) && !empty(trim($_POST['id_tipo_conducente'])) &&
    isset($_POST['id_motivo']) && !empty(trim($_POST['id_motivo'])) &&
    isset($_POST['id_usuario']) && !empty(trim($_POST['id_usuario'])) &&
    isset($_POST['data']) && !empty(trim($_POST['data'])) &&
    isset($_POST['hora']) && !empty(trim($_POST['hora']))
) {
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
            header('Location: ../views/entradas/registro_saida.php?status=success');
            exit();
        case 1:
            header('Location: ../views/entradas/registro_saida.php?status=ja_registrado');
            exit();
        case 2:
            header('Location: ../views/entradas/registro_saida.php?status=aluno_nao_encontrado');
            exit();
        case 3:
            header('Location: ../views/entradas/registro_saida.php?status=erro_interno');
            exit();
        default:
            header('Location: ../views/entradas/registro_saida.php?status=erro_desconhecido');
            exit();
    }
}

else if (isset($_POST['id_aluno']) && !empty($_POST['id_aluno']) && isset($_POST['data']) && !empty($_POST['data']) && isset($_POST['hora']) && !empty($_POST['hora'])) {

    $id_aluno = $_POST['id_aluno'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    $date_time = $data . ' ' . $hora;

    $obj = new MainModel();
    $result = $obj->registrarSaidaEstagio($id_aluno, $date_time);

    switch ($result) {
        case 0:
            header('Location: ../views/estagio/saida_Estagio.php?status=success');
            exit();
        case 1:
            header('Location: ../views/estagio/saida_Estagio.php?status=ja_registrado');
            exit();
        case 2:
            header('Location: ../views/estagio/saida_Estagio.php?status=aluno_nao_encontrado');
            exit();
        case 3:
            header('Location: ../views/estagio/saida_Estagio.php?status=erro_interno');
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

    echo "<pre>";
    echo "Dados recebidos via POST:\n";
    var_dump($_POST);
    echo "</pre>";

    switch ($gerar_relatorio) {
        case 'por_aluno':
            header('location:../views/relatorios/aluno_individual.php?id_aluno=' . $id_aluno . '&tipo_relatorio=' . $tipoRelatorio);
            exit();
            break;

        case 'por_alunoEntrada':
            header('location:../views/relatorios/aluno_individualEntrada.php?id_aluno=' . $id_aluno . '&tipo_relatorio=' . $tipoRelatorio);
            exit();
            break;

        case 'por_alunoSaida':
            header('location:../views/relatorios/aluno_individualSaida.php?id_aluno=' . $id_aluno . '&tipo_relatorio=' . $tipoRelatorio);
            exit();
            break;

        case '3_ano_geral':
            header('location:../views/relatorios/ano_geral.php?id_aluno=' . $id_aluno . '&tipoRelatorio=' . $tipoRelatorio);
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
            header('location:../views/relatorios/por_turma.php?id_turma=' . urlencode($id_turma) . '&tipoRelatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'por_turmaEntrada':
            if ($id_turma == 0) {
                echo "Erro: Selecione uma turma válida!";
                exit();
            }
            header('location:../views/relatorios/por_turmaEntrada.php?id_turma=' . urlencode($id_turma) . '&tipoRelatorio=' . urlencode($tipoRelatorio));
            exit();
            break;

        case 'por_turmaSaida':
            if ($id_turma == 0) {
                echo "Erro: Selecione uma turma válida!";
                exit();
            }
            header('location:../views/relatorios/por_turmaSaida.php?id_turma=' . urlencode($id_turma) . '&tipoRelatorio=' . urlencode($tipoRelatorio));
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
