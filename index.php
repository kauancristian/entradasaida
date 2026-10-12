<?php

date_default_timezone_set('America/Sao_Paulo');

if (isset($_GET['id_aluno']) && is_string($_GET['id_aluno']) && trim($_GET['id_aluno']) !== '') {
	require_once __DIR__ . '/app/main/model/model_indexClass.php';

	$model = new MainModel();
	$resultado = $model->registrarSaidaEstagio(trim($_GET['id_aluno']), date('Y-m-d H:i:s'));
	$statusPorResultado = [
		0 => 'success',
		1 => 'ja_registrado',
		2 => 'aluno_nao_encontrado',
		3 => 'erro_interno',
		4 => 'justificativa_nao_aprovada'
	];
	$status = $statusPorResultado[$resultado] ?? 'erro_desconhecido';

	header('Location: app/main/views/inicio.php?section=estagio&status=' . urlencode($status));
	exit;
}

header('Location: app/main/views/inicio.php');
exit;
