<?php

date_default_timezone_set('America/Sao_Paulo');

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$basePath = $scriptDirectory === '/' || $scriptDirectory === '.'
	? ''
	: rtrim($scriptDirectory, '/');
$inicioUrl = $basePath . '/app/main/views/inicio.php';

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

	header('Location: ' . $inicioUrl . '?section=estagio&status=' . rawurlencode($status), true, 303);
	exit;
}

header('Location: ' . $inicioUrl, true, 303);
exit;
