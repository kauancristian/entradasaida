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

	$agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
	$registroPendente = $_SESSION['qr_atraso_pendente'] ?? null;
	$horarioEntrada = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
		&& is_array($registroPendente)
		&& (int) ($registroPendente['id_aluno'] ?? 0) === (int) $aluno['id_aluno']
		? substr((string) ($registroPendente['date_time'] ?? ''), 11, 8)
		: $agora->format('H:i:s');
	$ehRegistroAtraso = $horarioEntrada >= '07:40:00' && $horarioEntrada < '11:40:00';
	$erroQr = '';
	$idUsuario = filter_var($_SESSION['id'] ?? null, FILTER_VALIDATE_INT);
	if (!$idUsuario && $acessoLocal) {
		try {
			$idUsuario = $model->obterUsuarioAtivoParaQr();
		} catch (RuntimeException $e) {
			$erroQr = 'Não foi possível localizar um usuário ativo para registrar o teste local.';
		}
	}
	if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
		$csrfToken = $_POST['csrf_token'] ?? '';
		$justificativa = $_POST['justificativa'] ?? '';
		$registroPendente = $_SESSION['qr_atraso_pendente'] ?? null;
		if (
			!is_string($csrfToken)
			|| !hash_equals($_SESSION['csrf_token'], $csrfToken)
			|| !is_array($registroPendente)
			|| (int) ($registroPendente['id_aluno'] ?? 0) !== (int) $aluno['id_aluno']
		) {
			$erroQr = 'A leitura expirou. Leia o crachá novamente.';
		} elseif (!is_string($justificativa) || trim($justificativa) === '' || strlen($justificativa) > 2000) {
			$erroQr = 'Preencha a justificativa do atraso para continuar.';
		} else {
			if (!$idUsuario) {
				$erroQr = 'Entre no sistema com um usuário ativo antes de registrar o atraso.';
			} else {
				$resultado = $model->registrarAtrasoQr(
					(int) $aluno['id_aluno'],
					(string) $registroPendente['date_time'],
					$justificativa,
					$idUsuario
				);
				if ($resultado === 0) {
					unset($_SESSION['qr_atraso_pendente']);
					header('Location: ' . $inicioUrl . '?section=atrasos&status=success', true, 303);
					exit;
				}
				$erroQr = [
					1 => 'Este aluno já possui uma entrada registrada hoje.',
					2 => 'O QR Code não identifica um aluno do 3º ano.',
					3 => 'Não foi possível gravar o atraso. Tente novamente.',
					5 => 'Confira a justificativa e tente ler o crachá novamente.',
					6 => 'O usuário da sessão não está ativo. Entre novamente no sistema.',
					7 => 'O banco ainda não tem o campo de justificativa do atraso. Aplique a atualização SQL e tente novamente.',
					8 => 'Não foi possível preparar os dados obrigatórios do registro.'
				][$resultado] ?? 'Não foi possível concluir o registro. Leia o crachá novamente.';
			}
		}
	}

	if (
		($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
		&& (
			!$ehRegistroAtraso
			|| !is_array($_SESSION['qr_atraso_pendente'] ?? null)
			|| (int) ($_SESSION['qr_atraso_pendente']['id_aluno'] ?? 0) !== (int) $aluno['id_aluno']
		)
	) {
		header('Location: ' . $inicioUrl . '?section=atrasos&status=erro_desconhecido', true, 303);
		exit;
	}

	if ($ehRegistroAtraso) {
		if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
			$_SESSION['qr_atraso_pendente'] = [
				'id_aluno' => (int) $aluno['id_aluno'],
				'date_time' => $agora->format('Y-m-d H:i:s')
			];
		}
		if (!$idUsuario && $erroQr === '') {
			$erroQr = 'Entre no sistema com um usuário ativo antes de registrar o atraso.';
		}

		$nomeAluno = htmlspecialchars((string) $aluno['nome'], ENT_QUOTES, 'UTF-8');
		$matriculaAluno = htmlspecialchars((string) $aluno['matricula'], ENT_QUOTES, 'UTF-8');
		$csrfToken = htmlspecialchars((string) $_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
		$erroHtml = $erroQr !== ''
			? '<p role="alert" style="color:#b91c1c">' . htmlspecialchars($erroQr, ENT_QUOTES, 'UTF-8') . '</p>'
			: '';
		?>
		<!doctype html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title>Registrar atraso</title>
			<style>
				body{font-family:Arial,sans-serif;background:#f3f4f6;margin:0;padding:24px;color:#1f2937}
				main{max-width:480px;margin:8vh auto;background:#fff;padding:28px;border-radius:12px;box-shadow:0 8px 28px #0001}
				h1{margin-top:0;color:#166534}label{display:block;font-weight:600;margin:20px 0 8px}
				textarea{box-sizing:border-box;width:100%;min-height:120px;padding:12px;border:1px solid #9ca3af;border-radius:8px;font:inherit}
				button{margin-top:16px;padding:12px 18px;background:#15803d;color:white;border:0;border-radius:8px;font-weight:700;cursor:pointer}
				.muted{color:#6b7280}
			</style>
		</head>
		<body>
			<main>
				<h1>Registrar atraso</h1>
				<p><strong><?= $nomeAluno ?></strong><br><span class="muted">Matrícula: <?= $matriculaAluno ?></span></p>
				<p>Preencha somente a justificativa do atraso.</p>
				<?= $erroHtml ?>
				<form method="post" action="index.php?id_aluno=<?= rawurlencode((string) $aluno['id_aluno']) ?>">
					<input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
					<label for="justificativa">Justificativa</label>
					<textarea id="justificativa" name="justificativa" maxlength="2000" required autofocus></textarea>
					<button type="submit">Registrar atraso</button>
				</form>
			</main>
		</body>
		</html>
		<?php
		exit;
	}

	$resultado = $model->registrarSaidaEstagio(
		(int) $aluno['id_aluno'],
		$agora->format('Y-m-d H:i:s')
	);
	$statusPorResultado = [
		0 => 'success',
		1 => 'ja_registrado',
		2 => 'aluno_nao_encontrado',
		3 => 'erro_interno',
		4 => 'justificativa_nao_aprovada',
		5 => 'justificativa_obrigatoria'
	];
	$status = $statusPorResultado[$resultado] ?? 'erro_interno';
	$section = $status === 'justificativa_obrigatoria' ? 'estagio' : 'ultimas-saidas';
	header(
		'Location: ' . $inicioUrl . '?section=' . rawurlencode($section) . '&status=' . rawurlencode($status),
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
