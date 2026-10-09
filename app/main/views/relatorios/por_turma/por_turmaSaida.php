<?php
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/../../../config/Database.php');

class PDFRelatorioAtrasosTurma extends FPDF
{
    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, 'Pagina ' . $this->PageNo() . '/{nb} | Gerado em ' . date('d/m/Y H:i'), 0, 0, 'R');
    }
}

class RelatorioAtrasosPorTurma
{
    private $connect;
    private $turma;
    private $periodoNome;
    private $corTurma;

    public function __construct()
    {
        $database = new connect();
        $this->connect = $database->getConnection();
        $this->pdf();
    }

    private function textoPdf($texto)
    {
        return mb_convert_encoding((string) $texto, 'ISO-8859-1', 'UTF-8');
    }

    private function intervaloDatas($tipoRelatorio)
    {
        $agora = new DateTimeImmutable();
        if ($tipoRelatorio === 'dia_atual') {
            return [
                $agora->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $agora->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            ];
        }

        if ($tipoRelatorio === 'ultimos_30_dias') {
            return [
                $agora->modify('-30 days')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $agora->format('Y-m-d H:i:s')
            ];
        }

        return [
            $agora->modify('-12 months')->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            $agora->format('Y-m-d H:i:s')
        ];
    }

    private function imprimirCabecalho($pdf)
    {
        $pdf->SetFillColor(238, 238, 238);
        $pdf->Rect(0, 0, $pdf->GetPageWidth(), 28, 'F');
        $pdf->Image(__DIR__ . '/../../../assets/img/logo.png', 12, 8.5, 8, 11);

        $pdf->SetXY(24, 5);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(255, 165, 0);
        $pdf->Cell(0, 8, $this->textoPdf('Relatório de atrasos por turma'), 0, 1, 'L');

        $pdf->SetXY(24, 14);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(110, 110, 110);
        $pdf->Cell(0, 6, $this->textoPdf($this->periodoNome), 0, 1, 'L');

        $pdf->SetFillColor($this->corTurma['r'], $this->corTurma['g'], $this->corTurma['b']);
        $pdf->Rect(12, 32, $pdf->GetPageWidth() - 24, 9, 'F');
        $pdf->SetXY(16, 33);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, $this->textoPdf(strtoupper($this->turma['nome']) . ' - ALUNOS COM ATRASO'), 0, 1, 'L');
        $pdf->SetY(46);
    }

    private function imprimirCabecalhoTabela($pdf, $colunas)
    {
        $pdf->SetFillColor(240, 249, 244);
        $pdf->SetDrawColor(210, 218, 213);
        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('Arial', 'B', 9);
        foreach ($colunas as $coluna) {
            $pdf->Cell($coluna[1], 8, $this->textoPdf($coluna[0]), 1, 0, 'C', true);
        }
        $pdf->Ln();
    }

    private function linhasTexto($pdf, $texto, $largura)
    {
        $texto = $this->textoPdf($texto);
        $limite = $largura - 4;
        $linhas = [];
        $linha = '';
        foreach (explode(' ', $texto) as $palavra) {
            $candidata = $linha === '' ? $palavra : $linha . ' ' . $palavra;
            if ($pdf->GetStringWidth($candidata) <= $limite) {
                $linha = $candidata;
                continue;
            }
            if ($linha !== '') {
                $linhas[] = $linha;
            }
            $linha = '';
            while ($pdf->GetStringWidth($palavra) > $limite) {
                $parte = '';
                foreach (str_split($palavra) as $caractere) {
                    if ($pdf->GetStringWidth($parte . $caractere) > $limite) {
                        break;
                    }
                    $parte .= $caractere;
                }
                $linhas[] = $parte;
                $palavra = substr($palavra, strlen($parte));
            }
            $linha = $palavra;
        }
        if ($linha !== '' || empty($linhas)) {
            $linhas[] = $linha;
        }
        return $linhas;
    }

    private function imprimirLinhaTabela($pdf, $valores, $colunas, $indice)
    {
        $pdf->SetFont('Arial', '', 9);
        $linhasPorColuna = [];
        $quantidadeLinhas = 1;
        foreach ($colunas as $indiceColuna => $coluna) {
            $linhasPorColuna[$indiceColuna] = $this->linhasTexto($pdf, $valores[$indiceColuna], $coluna[1]);
            $quantidadeLinhas = max($quantidadeLinhas, count($linhasPorColuna[$indiceColuna]));
        }
        $alturaLinha = 4.2;
        $altura = $quantidadeLinhas * $alturaLinha + 2;

        if ($pdf->GetY() + $altura > $pdf->GetPageHeight() - 16) {
            $pdf->AddPage();
            $this->imprimirCabecalho($pdf);
            $this->imprimirCabecalhoTabela($pdf, $colunas);
        }

        $x = $pdf->GetX();
        $inicioX = $x;
        $y = $pdf->GetY();
        foreach ($colunas as $indiceColuna => $coluna) {
            $tom = $indice % 2 === 0 ? 255 : 245;
            $pdf->SetFillColor($tom, $tom, $tom);
            $pdf->Rect($x, $y, $coluna[1], $altura, 'DF');
            $pdf->SetXY($x + 2, $y + 1);
            $pdf->MultiCell($coluna[1] - 4, $alturaLinha, implode("\n", $linhasPorColuna[$indiceColuna]), 0, 'L');
            $x += $coluna[1];
        }
        $pdf->SetXY($inicioX, $y + $altura);
    }

    private function statusJustificativaDisponivel()
    {
        $colunas = $this->connect->query('SHOW COLUMNS FROM registro_entrada')->fetchAll(PDO::FETCH_COLUMN);
        return in_array('status_justificativa', $colunas, true);
    }

    public function pdf()
    {
        $periodos = [
            'dia_atual' => 'Dia atual',
            'ultimos_30_dias' => 'Últimos 30 dias',
            'ultimos_12_meses' => 'Últimos 12 meses'
        ];
        $idTurma = filter_var($_GET['id_turma'] ?? null, FILTER_VALIDATE_INT);
        $tipoRelatorio = $_GET['tipoRelatorio'] ?? '';
        if (!$idTurma || $idTurma < 1 || $idTurma > 12 || !isset($periodos[$tipoRelatorio])) {
            header('Location: ../relatorioSaida.php?error=invalid_parameters');
            exit();
        }

        $consultaTurma = $this->connect->prepare('SELECT id_turma, ano, turma FROM turma WHERE id_turma = :id_turma');
        $consultaTurma->execute(['id_turma' => $idTurma]);
        $this->turma = $consultaTurma->fetch(PDO::FETCH_ASSOC);
        if (!$this->turma) {
            header('Location: ../relatorioSaida.php?error=invalid_turma');
            exit();
        }
        $this->turma['nome'] = $this->turma['ano'] . ' ' . strtoupper($this->turma['turma']);

        $cores = [
            1 => ['r' => 255, 'g' => 165, 'b' => 0],
            2 => ['r' => 255, 'g' => 215, 'b' => 0],
            3 => ['r' => 0, 'g' => 128, 'b' => 0],
            4 => ['r' => 0, 'g' => 191, 'b' => 255],
            5 => ['r' => 255, 'g' => 105, 'b' => 180],
            6 => ['r' => 147, 'g' => 112, 'b' => 219],
            7 => ['r' => 75, 'g' => 0, 'b' => 130],
            8 => ['r' => 255, 'g' => 69, 'b' => 0],
            9 => ['r' => 220, 'g' => 53, 'b' => 69],
            10 => ['r' => 65, 'g' => 105, 'b' => 225],
            11 => ['r' => 13, 'g' => 202, 'b' => 240],
            12 => ['r' => 108, 'g' => 117, 'b' => 125]
        ];
        $this->corTurma = $cores[$idTurma];
        $this->periodoNome = $periodos[$tipoRelatorio];
        list($inicio, $fim) = $this->intervaloDatas($tipoRelatorio);

        $statusSelect = $this->statusJustificativaDisponivel()
            ? 'r.status_justificativa AS status_justificativa'
            : "'pendente' AS status_justificativa";
        $sql = "SELECT a.nome AS nome_aluno,
                       CONCAT(t.ano, ' ', UPPER(t.turma)) AS nome_turma,
                       r.date_time,
                       r.nome_responsavel,
                       r.nome_conducente,
                       m.motivo,
                       {$statusSelect}
                FROM registro_entrada r
                INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                INNER JOIN turma t ON t.id_turma = a.id_turma
                LEFT JOIN motivo m ON m.id_motivo = r.id_motivo
                WHERE a.id_turma = :id_turma
                  AND r.date_time BETWEEN :inicio AND :fim
                  AND TIME(r.date_time) BETWEEN '07:40:00' AND '11:40:59'
                ORDER BY r.date_time ASC, a.nome ASC";
        $consulta = $this->connect->prepare($sql);
        $consulta->execute(['id_turma' => $idTurma, 'inicio' => $inicio, 'fim' => $fim]);
        $dados = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new PDFRelatorioAtrasosTurma('L', 'mm', 'A4');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $this->imprimirCabecalho($pdf);

        $colunas = [
            ['Aluno', 55],
            ['Turma', 28],
            ['Data', 24],
            ['Hora', 16],
            ['Responsável', 42],
            ['Acompanhante', 32],
            ['Motivo', 34],
            ['Justificativa', 42]
        ];
        $this->imprimirCabecalhoTabela($pdf, $colunas);

        if (empty($dados)) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(145, 145, 145);
            $pdf->Cell(0, 12, $this->textoPdf('Nenhum atraso encontrado para esta turma e período.'), 0, 1, 'C');
        } else {
            $statusNomes = ['pendente' => 'Pendente', 'aprovada' => 'Aprovada', 'recusada' => 'Recusada'];
            foreach ($dados as $indice => $dado) {
                $status = $dado['status_justificativa'] ?? 'pendente';
                $valores = [
                    $dado['nome_aluno'] ?? 'Aluno não encontrado',
                    $dado['nome_turma'] ?? 'Turma não informada',
                    date('d/m/Y', strtotime($dado['date_time'])),
                    date('H:i', strtotime($dado['date_time'])),
                    $dado['nome_responsavel'] ?: 'Não informado',
                    $dado['nome_conducente'] ?: 'Não informado',
                    $dado['motivo'] ?: 'Não informado',
                    $statusNomes[$status] ?? 'Pendente'
                ];
                $this->imprimirLinhaTabela($pdf, $valores, $colunas, $indice);
            }
        }

        $pdf->Output('I', 'relatorio_atrasos_por_turma.pdf');
    }
}

if (isset($_GET['id_turma'], $_GET['tipoRelatorio'])) {
    new RelatorioAtrasosPorTurma();
} else {
    header('Location: ../relatorioSaida.php?error=missing_params');
    exit();
}