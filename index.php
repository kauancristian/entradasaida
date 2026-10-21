<?php
date_default_timezone_set('America/Sao_Paulo');

$inicioUrl = 'app/main/views/inicio.php';

if (array_key_exists('id_aluno', $_GET)) {
	require_once __DIR__ . '/app/main/model/sessions.php';
	require_once __DIR__ . '/app/main/model/model_indexClass.php';

	$session = new sessions();
	$host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
	$hostsLocais = ['localhost', '127.0.0.1', '::1'];
	$ipsLocais = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
	$acessoLocal = in_array(strtolower((string) $host), $hostsLocais, true)
		&& in_array($_SERVER['REMOTE_ADDR'] ?? '', $ipsLocais, true);
	if (!$acessoLocal) {
		$session->autenticar_session();
	}
	$usuarioSessao = $session->obterDadosUsuarioAtual();
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));


              		}

	$identificador = $_GET['id_aluno'];
	$status = 'aluno_nao_encontrado';

	if (!is_string($identificador) || trim($identificador) === '') {
		header('Location: ' . $inicioUrl . '?section=atrasos&status=' . $status, true, 303);
		exit;
	} else {
		try {
			$model = new MainModel();
			$aluno = $model->obterAlunoPorIdentificadorSaidaEstagio(trim($identificador));
		} catch (RuntimeException $e) {
			error_log('Erro ao consultar aluno pelo QR Code: ' . $e->getMessage());
			header('Location: ' . $inicioUrl . '?section=atrasos&status=erro_interno', true, 303);
			exit;
		}
	}

	if (!$aluno) {
		header('Location: ' . $inicioUrl . '?section=atrasos&status=aluno_nao_encontrado', true, 303);
		exit;
	}
	if (!$usuarioSessao) {
		header('Location: ' . $inicioUrl . '?section=atrasos&status=usuario_inativo', true, 303);
		exit;
	}

	$agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
	$horarioEntrada = $agora->format('H:i:s');
	$ehRegistroAtraso = $horarioEntrada >= '07:40:00' && $horarioEntrada < '11:40:00';
	if ($ehRegistroAtraso) {
		$resultado = $model->registrarAtrasoQr(
			(int) $aluno['id_aluno'],
			$agora->format('Y-m-d H:i:s'),
			'',
			$usuarioSessao
		);
		$statusPorResultado = [
			0 => 'success',
			1 => 'ja_registrado',
			2 => 'aluno_nao_encontrado',
			3 => 'erro_interno',
			5 => 'erro_desconhecido',
			6 => 'usuario_inativo',
			7 => 'qr_atraso_estrutura',
			8 => 'qr_atraso_opcoes'
		];
		$status = $statusPorResultado[$resultado] ?? 'erro_interno';
		header('Location: ' . $inicioUrl . '?section=atrasos&status=' . rawurlencode($status), true, 303);
		exit;
	}

	$resultado = $model->registrarSaidaEstagio(
		(int) $aluno['id_aluno'],
		$agora->format('Y-m-d H:i:s'),
		$usuarioSessao
	);
	$statusPorResultado = [
		0 => 'success',
		1 => 'ja_registrado',
		2 => 'aluno_nao_encontrado',
		3 => 'erro_interno'
	];
	$status = $statusPorResultado[$resultado] ?? 'erro_interno';
	header(
		'Location: ' . $inicioUrl . '?section=ultimas-saidas&status=' . rawurlencode($status),
		true,
		303
	);
	exit;
}

$host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
$hostsLocais = ['localhost', '127.0.0.1', '::1'];
$ipsLocais = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
if (
	in_array(strtolower((string) $host), $hostsLocais, true)
		&& in_array($_SERVER['REMOTE_ADDR'] ?? '', $ipsLocais, true)
) {
	header('Location: ' . $inicioUrl, true, 303);
	exit;
}

exit;
