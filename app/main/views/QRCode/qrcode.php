<?php

date_default_timezone_set('America/Sao_Paulo');

define('FPDF_FONTPATH', __DIR__ . '/../../assets/lib/fpdf/font/');
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../assets/lib/fpdf/fpdf.php';
require_once __DIR__ . '/../../assets/lib/phpqrcode/qrlib.php';

function writeQrPngFromMatrix(array $matrix, string $filename, int $scale = 4, int $quietZone = 4): void
{
    if (!function_exists('gzcompress') || $matrix === []) {
        throw new RuntimeException('Não foi possível gerar a imagem do QR Code.');
    }

    $moduleCount = count($matrix);
    $imageWidth = ($moduleCount + 2 * $quietZone) * $scale;
    $imageData = '';

    foreach ($matrix as $matrixRow) {
        if (!is_string($matrixRow) || strlen($matrixRow) !== $moduleCount) {
            throw new RuntimeException('A matriz do QR Code está inválida.');
        }

        $scanline = "\0";
        for ($moduleX = -$quietZone; $moduleX < $moduleCount + $quietZone; $moduleX++) {
            $isDark = $moduleX >= 0
                && $moduleX < $moduleCount
                && $matrixRow[$moduleX] === '1';
            $scanline .= str_repeat($isDark ? "\0" : "\xff", $scale);
        }

        for ($repeat = 0; $repeat < $scale; $repeat++) {
            $imageData .= $scanline;
        }
    }

    $paddingRow = "\0" . str_repeat("\xff", $imageWidth);
    for ($repeat = 0; $repeat < $quietZone * $scale; $repeat++) {
        $imageData = $paddingRow . $imageData . $paddingRow;
    }

    $png = "\x89PNG\r\n\x1a\n";
    foreach ([
        'IHDR' => pack('NNCCCCC', $imageWidth, $imageWidth, 8, 0, 0, 0, 0),
        'IDAT' => gzcompress($imageData)
    ] as $type => $data) {
        $chunk = $type . $data;
        $png .= pack('N', strlen($data)) . $chunk . pack('N', crc32($chunk));
    }
    $png .= pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));

    if (file_put_contents($filename, $png) === false) {
        throw new RuntimeException('Não foi possível salvar temporariamente o QR Code.');
    }
}

function turmaBadgeDirectory(string $turma): string
{
    return [
        '9' => 'img3A',
        '10' => 'img3B',
        '11' => 'img3C',
        '12' => 'img3D',
        '13' => 'areaDev',
        '14' => 'sti',
        '15' => 'reimpressao'
    ][$turma];
}

function turmaBadgeBackground(string $turma): string
{
    return [
        '13' => 'plano_de_fundo_areadev.jpg',
        '14' => 'plano_de_fundo_sti.jpg'
    ][$turma] ?? 'plano_de_fundo.jpg';
}

function localizarFotoAluno(string $diretorio, string $nome, string $matricula): ?string
{
    $nome = strtoupper(trim($nome));
    foreach ([
        $matricula . ' - ' . $nome,
        $matricula . '- ' . $nome,
        $matricula . ' -' . $nome,
        $matricula . '-' . $nome
    ] as $base) {
        foreach (['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG'] as $extensao) {
            $arquivo = $diretorio . DIRECTORY_SEPARATOR . $base . '.' . $extensao;
            if (is_file($arquivo)) {
                return $arquivo;
            }
        }
    }

    return null;
}

function gerarCrachaTurma(): void
{
    $turma = filter_input(INPUT_POST, 'turma', FILTER_VALIDATE_INT);
    $turmasPermitidas = [9, 10, 11, 12, 13, 14, 15];
    if ($turma === false || $turma === null || !in_array($turma, $turmasPermitidas, true)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Selecione uma turma válida para gerar os crachás.';
        return;
    }

    $turma = (string) $turma;
    $temporarios = [];

    try {
        $conexao = (new connect())->getConnection();
        $consulta = $conexao->prepare(
            'SELECT id_aluno, nome, matricula
             FROM aluno
             WHERE id_turma = :turma
               AND ano_corrente = YEAR(CURDATE())
             ORDER BY nome'
        );
        $consulta->execute(['turma' => $turma]);
        $alunos = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new FPDF('L', 'cm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        if ($alunos === []) {
            $pdf->SetFont('Arial', 'B', 13);
            $pdf->Cell(0, 1, mb_convert_encoding('Nenhum aluno encontrado para a turma selecionada.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
            $pdf->Output('I', 'crachas_turma_' . $turma . '.pdf');
            return;
        }

        $assets = __DIR__ . '/../../assets/img';
        $turmaDir = turmaBadgeDirectory($turma);
        $fotosDir = $assets . '/imgAlunos/' . $turmaDir;
        $fundo = $assets . '/' . turmaBadgeBackground($turma);
        $logoFrente = $assets . '/logo_frente_cracha.png';
        $logoVerso = $assets . '/logo_verso_cracha.png';
        $nomesTurma = [
            '9' => 'Enfermagem',
            '10' => 'Informática',
            '11' => 'Meio Ambiente',
            '12' => 'Edificações',
            '13' => 'DESENVOLVEDOR | AREA DEV',
            '14' => 'Suporte de TI',
            '15' => 'Enfermagem'
        ];

        $larguraCracha = 5;
        $alturaCracha = 8;
        $espacoCracha = 0.5;
        $larguraAluno = 2 * $larguraCracha + $espacoCracha;
        $margemX = 1;
        $margemY = 0.8;
        $espacoAluno = 0.8;
        $alunosPorLinha = 2;
        $linhasPorPagina = 2;
        $alturaLinha = $alturaCracha + 0.8;
        $larguraQr = 3.5;
        foreach ($alunos as $indice => $aluno) {
            $linha = intdiv($indice, $alunosPorLinha) % $linhasPorPagina;
            if ($indice > 0 && $indice % ($alunosPorLinha * $linhasPorPagina) === 0) {
                $pdf->AddPage();
            }
            $colunaAluno = $indice % $alunosPorLinha;
            $x = $margemX + $colunaAluno * ($larguraAluno + $espacoAluno);
            $y = $margemY + $linha * $alturaLinha;

            $arquivoTemporario = tempnam(sys_get_temp_dir(), 'qr_');
            if ($arquivoTemporario === false) {
                throw new RuntimeException('Não foi possível criar a imagem temporária do QR Code.');
            }
            $arquivoTemporarioPng = $arquivoTemporario . '.png';
            if (!rename($arquivoTemporario, $arquivoTemporarioPng)) {
                throw new RuntimeException('Não foi possível preparar a imagem temporária do QR Code.');
            }
            $arquivoTemporario = $arquivoTemporarioPng;
            $temporarios[] = $arquivoTemporario;

            $conteudoQr = (string) $aluno['id_aluno'];
            $matrizQr = QRcode::text($conteudoQr, false, QR_ECLEVEL_M, 4);
            writeQrPngFromMatrix($matrizQr, $arquivoTemporario);

            $xQrCracha = $x;
            $xFrenteCracha = $x + $larguraCracha + $espacoCracha;
            foreach ([$xQrCracha, $xFrenteCracha] as $xCracha) {
                if (is_file($fundo)) {
                    $pdf->Image($fundo, $xCracha, $y, $larguraCracha, $alturaCracha);
                } else {
                    $pdf->SetDrawColor(180, 180, 180);
                    $pdf->Rect($xCracha, $y, $larguraCracha, $alturaCracha);
                }
            }

            if (is_file($logoVerso)) {
                $pdf->Image($logoVerso, $xQrCracha + 0.9, $y + 0.35, 3.2);
            }
            $pdf->Image($arquivoTemporario, $xQrCracha + ($larguraCracha - $larguraQr) / 2, $y + 2.15, $larguraQr, $larguraQr);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetXY($xQrCracha + 0.1, $y + 5.85);
            $pdf->Cell($larguraCracha - 0.2, 0.3, mb_convert_encoding('Matrícula: ' . $aluno['matricula'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');

            if (is_file($logoFrente)) {
                $pdf->Image($logoFrente, $xFrenteCracha + 0.5, $y + 0.35, 4);
            }
            $foto = localizarFotoAluno($fotosDir, (string) $aluno['nome'], (string) $aluno['matricula']);
            if ($foto !== null) {
                $pdf->Image($foto, $xFrenteCracha + 1.35, $y + 1.8, 2.3, 3.06);
            } else {
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetXY($xFrenteCracha, $y + 3.4);
                $pdf->Cell($larguraCracha, 0.4, 'SEM FOTO', 0, 0, 'C');
            }

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($xFrenteCracha + 0.1, $y + 5.05);
            $pdf->MultiCell($larguraCracha - 0.2, 0.3, mb_convert_encoding(mb_convert_case(mb_strtolower((string) $aluno['nome']), MB_CASE_TITLE, 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 'C');

            $corCurso = [
                '9' => [213, 46, 63],
                '10' => [54, 82, 209],
                '11' => [124, 194, 160],
                '12' => [107, 116, 124],
                '13' => [0, 0, 0],
                '14' => [54, 82, 209],
                '15' => [213, 46, 63]
            ][$turma];
            $pdf->SetFillColor($corCurso[0], $corCurso[1], $corCurso[2]);
            $pdf->Rect($xFrenteCracha, $y + 6.2, $larguraCracha, 0.45, 'F');
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->SetXY($xFrenteCracha + 0.05, $y + 6.29);
            $pdf->Cell($larguraCracha - 0.1, 0.25, mb_convert_encoding($nomesTurma[$turma], 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($xFrenteCracha, $y + 6.85);
            $pdf->Cell($larguraCracha, 0.3, 'Estagiario(a)', 0, 0, 'C');

            $pdf->SetFont('Arial', '', 7);
            $pdf->SetXY($xQrCracha, $y + $alturaCracha + 0.05);
            $pdf->Cell($larguraCracha, 0.3, mb_convert_encoding((string) $aluno['nome'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
            $pdf->SetXY($xFrenteCracha, $y + $alturaCracha + 0.05);
            $pdf->Cell($larguraCracha, 0.3, mb_convert_encoding((string) $aluno['nome'], 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
        }

        $pdf->Output('I', 'crachas_turma_' . $turma . '.pdf');
    } catch (Throwable $e) {
        error_log('Falha ao gerar crachás QR da turma ' . $turma . ': ' . $e->getMessage());
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
        }
        echo 'Não foi possível gerar os crachás desta turma. Confira o log do PHP para mais detalhes.';
    } finally {
        foreach ($temporarios as $arquivo) {
            if (is_file($arquivo) && !unlink($arquivo)) {
                error_log('Não foi possível remover a imagem temporária do QR Code: ' . $arquivo);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Use o formulário de geração de QR Codes.';
    exit;
}

gerarCrachaTurma();
