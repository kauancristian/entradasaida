<?php
require_once __DIR__ . '/../../model/sessions.php';
require_once __DIR__ . '/../../assets/lib/fpdf/fpdf.php';

date_default_timezone_set('America/Sao_Paulo');

function responderErroComprovante(string $message, int $status): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

$host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
$hostsLocais = ['localhost', '127.0.0.1', '::1'];
$ipsLocais = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
$acessoLocal = in_array(strtolower((string) $host), $hostsLocais, true)
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', $ipsLocais, true);
$session = new sessions();
if ($acessoLocal) {
    $session->simularLoginLocalParaTeste();
} else {
    $session->autenticar_session();
}

$csrfToken = $_SESSION['csrf_token'] ?? '';
$postedToken = $_POST['csrf_token'] ?? '';
if (!is_string($csrfToken) || !is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
    responderErroComprovante('A sessão expirou. Atualize a página e tente novamente.', 403);
}

$tipo = $_POST['tipo'] ?? '';
if (!in_array($tipo, ['entrada', 'atraso', 'saida'], true)) {
    responderErroComprovante('Tipo de comprovante inválido.', 422);
}

function textoComprovante(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    if (!is_string($valor)) {
        return '';
    }

    return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $valor));
}

function textoComprovantePdf(string $valor): string
{
    $convertido = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $valor);
    if ($convertido === false) {
        throw new RuntimeException('Não foi possível converter o texto do comprovante.');
    }

    return $convertido;
}

class PDFComprovanteTermico extends FPDF
{
    public function imprimirCampo(string $texto): void
    {
        if ($texto === '') {
            return;
        }
        $this->SetFont('Arial', '', 9);
        $this->MultiCell(70, 5, textoComprovantePdf($texto), 0, 'L');
        $this->Ln(1);
    }
}

try {
    $campos = [
        'entrada' => [
            ['Nome', textoComprovante('aluno')],
            ['Matrícula', textoComprovante('matricula')],
            ['Turma', textoComprovante('turma')],
            ['Motivo', textoComprovante('motivo')],
            ['Justificativa', textoComprovante('observacao')]
        ],
        'atraso' => [
            ['Aluno', textoComprovante('aluno')],
            ['Matrícula', textoComprovante('matricula')],
            ['Turma', textoComprovante('turma')],
            ['Motivo', textoComprovante('motivo')],
            ['Justificativa do atraso', textoComprovante('justificativa')]
        ],
        'saida' => [
            ['Nome', textoComprovante('aluno')],
            ['Matrícula', textoComprovante('matricula')],
            ['Turma', textoComprovante('turma')],
            ['Responsável', textoComprovante('responsavel')],
            ['Tipo de responsável', textoComprovante('tipoResponsavel')],
            ['Motivo', textoComprovante('motivo')],
            ['Justificativa', textoComprovante('observacao')]
        ]
    ][$tipo];

    $linhasEstimadas = 0;
    foreach ($campos as [$rotulo, $valor]) {
        if ($valor === '') {
            continue;
        }
        $texto = $rotulo . ': ' . $valor;
        foreach (explode("\n", wordwrap($texto, 42, "\n", true)) as $linha) {
            $linhasEstimadas++;
        }
    }
    $altura = max(75, 44 + ($linhasEstimadas * 6));
    $pdf = new PDFComprovanteTermico('P', 'mm', [80, $altura]);
    $pdf->SetMargins(5, 5, 5);
    $pdf->SetAutoPageBreak(true, 5);
    $pdf->AddPage();

    $logoSalaberga = __DIR__ . '/../../assets/img/logo.png';
    if (!is_file($logoSalaberga)) {
        throw new RuntimeException('Logo Salaberga não encontrado.');
    }
    $logoDimensoes = getimagesize($logoSalaberga);
    if ($logoDimensoes === false || $logoDimensoes[0] <= 0) {
        throw new RuntimeException('Não foi possível ler o logo Salaberga.');
    }
    $alturaCabecalho = 9;
    $larguraLogo = 5;
    $alturaLogo = $larguraLogo * $logoDimensoes[1] / $logoDimensoes[0];
    $yCabecalho = 5;
    $pdf->Image(
        $logoSalaberga,
        6,
        $yCabecalho + ($alturaCabecalho - $alturaLogo) / 2,
        $larguraLogo,
        0,
        'PNG'
    );
    $pdf->SetXY(14, 5);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(61, $alturaCabecalho, 'Sistema Salaberga', 0, 1, 'L');
    $pdf->SetY(17);

    $titulo = $tipo === 'saida' ? 'AUTORIZACAO DE SAIDA' : ($tipo === 'atraso' ? 'REGISTRO DE ATRASO' : 'AUTORIZACAO DE ENTRADA');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->MultiCell(70, 6, $titulo, 0, 'C');
    $pdf->Ln(3);

    foreach ($campos as [$rotulo, $valor]) {
        if ($valor !== '') {
            $pdf->imprimirCampo($rotulo . ': ' . $valor);
        }
    }

    $conteudo = $pdf->Output('S');
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="comprovante-termico.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Content-Length: ' . strlen($conteudo));
    echo $conteudo;
} catch (Throwable $error) {
    error_log('Erro ao gerar comprovante térmico: ' . $error->getMessage());
    responderErroComprovante('Não foi possível gerar o comprovante em PDF.', 500);
}
