<?php
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/_layout_pdf.php');
require_once(__DIR__ . '/../../config/Database.php');

class PDFRelatorioAtrasos extends FPDF
{
    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, 'Pagina ' . $this->PageNo() . '/{nb} | Gerado em ' . date('d/m/Y H:i'), 0, 0, 'R');
    }
}

class SaidaPDFAnoGeral
{
    private $connect;
    private $ano;
    private $anoNome;
    private $periodoNome;
    private $corAno;

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

    private function imprimirCabecalho($pdf)
    {
        aplicarCabecalhoRelatorio($pdf, 'Relatório de atrasos por ano', $this->anoNome . ' | ' . $this->periodoNome);
        $y = $pdf->GetY();
        $pdf->SetFillColor($this->corAno['r'], $this->corAno['g'], $this->corAno['b']);
        $pdf->Rect(12, $y, $pdf->GetPageWidth() - 24, 9, 'F');
        $pdf->SetXY(16, $y + 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, $this->textoPdf(strtoupper($this->anoNome) . ' - ALUNOS COM ATRASO'), 0, 1, 'L');
        $pdf->SetY($y + 14);
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

    private function expressaoStatusJustificativa()
    {
        $colunas = $this->connect->query('SHOW COLUMNS FROM registro_entrada')->fetchAll(PDO::FETCH_COLUMN);
        return in_array('status_justificativa', $colunas, true)
            ? 'r.status_justificativa AS status_justificativa'
            : "'pendente' AS status_justificativa";
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

    private function textoAlunoUmaLinha($pdf, $texto, $largura)
    {
        $texto = $this->textoPdf($texto);
        $limite = $largura - 4;
        $tamanhoFonte = 9;
        $pdf->SetFont('Arial', '', $tamanhoFonte);

        while ($tamanhoFonte > 6 && $pdf->GetStringWidth($texto) > $limite) {
            $tamanhoFonte -= 0.5;
            $pdf->SetFont('Arial', '', $tamanhoFonte);
        }

        if ($pdf->GetStringWidth($texto) > $limite) {
            while ($texto !== '' && $pdf->GetStringWidth(rtrim($texto) . '...') > $limite) {
                $texto = substr($texto, 0, -1);
            }
            $texto = rtrim($texto) . '...';
        }

        return [$texto, $tamanhoFonte];
    }

    private function imprimirLinhaTabela($pdf, $valores, $colunas, $indice)
    {
        [$nomeAluno, $tamanhoFonteNome] = $this->textoAlunoUmaLinha($pdf, $valores[0], $colunas[0][1]);
        $linhasPorColuna = [];
        $quantidadeLinhas = 1;
        foreach ($colunas as $indiceColuna => $coluna) {
            if ($indiceColuna === 0) {
                $linhasPorColuna[$indiceColuna] = [$nomeAluno];
                continue;
            }
            $pdf->SetFont('Arial', '', 9);
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
            $largura = $coluna[1];
            $tom = $indice % 2 === 0 ? 255 : 245;
            $pdf->SetFillColor($tom, $tom, $tom);
            $pdf->Rect($x, $y, $largura, $altura, 'DF');
            $pdf->SetXY($x + 2, $y + 1);
            if ($indiceColuna === 0) {
                $pdf->SetFont('Arial', '', $tamanhoFonteNome);
                $pdf->Cell($largura - 4, $alturaLinha, $linhasPorColuna[$indiceColuna][0], 0, 0, 'L');
            } else {
                $pdf->SetFont('Arial', '', 9);
                $pdf->MultiCell($largura - 4, $alturaLinha, implode("\n", $linhasPorColuna[$indiceColuna]), 0, 'L');
            }
            $x += $largura;
        }
        $pdf->SetXY($inicioX, $y + $altura);
    }

    public function pdf()
    {
        $anos = [
            1 => ['nome' => '1º Ano', 'banco' => '1 ano', 'cor' => ['r' => 255, 'g' => 165, 'b' => 0]],
            2 => ['nome' => '2º Ano', 'banco' => '2 ano', 'cor' => ['r' => 220, 'g' => 53, 'b' => 69]],
            3 => ['nome' => '3º Ano', 'banco' => '3 ano', 'cor' => ['r' => 0, 'g' => 122, 'b' => 51]]
        ];
        $periodos = [
            'dia_atual' => 'Dia atual',
            'ultimos_30_dias' => 'Últimos 30 dias',
            'ultimos_12_meses' => 'Últimos 12 meses'
        ];

        $anoSelecionado = filter_var($_GET['ano'] ?? null, FILTER_VALIDATE_INT);
        $tipoRelatorio = $_GET['tipo_relatorio'] ?? '';
        if (!isset($anos[$anoSelecionado]) || !isset($periodos[$tipoRelatorio])) {
            header('Location: ../relatorioSaida.php?error=invalid_parameters');
            exit();
        }

        $this->ano = $anos[$anoSelecionado]['banco'];
        $this->anoNome = $anos[$anoSelecionado]['nome'];
        $this->corAno = $anos[$anoSelecionado]['cor'];
        $this->periodoNome = $periodos[$tipoRelatorio];
        list($inicio, $fim) = $this->intervaloDatas($tipoRelatorio);

        $sql = "SELECT a.nome AS nome_aluno,
                       CONCAT(t.ano, ' ', UPPER(t.turma)) AS nome_turma,
                       r.date_time,
                       r.nome_responsavel,
                       tr.tipo AS tipo_responsavel,
                       r.nome_conducente,
                       tc.tipo AS tipo_conducente,
                       m.motivo,
                       " . $this->expressaoStatusJustificativa() . "
                FROM registro_entrada r
                INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                INNER JOIN turma t ON t.id_turma = a.id_turma
                LEFT JOIN tipo_responsavel tr ON tr.id_tipo_responsavel = r.id_tipo_responsavel
                LEFT JOIN tipo_conducente tc ON tc.id_tipo_conducente = r.id_tipo_conducente
                LEFT JOIN motivo m ON m.id_motivo = r.id_motivo
                WHERE t.ano = :ano
                  AND r.date_time BETWEEN :inicio AND :fim
                  AND TIME(r.date_time) >= '07:40:00'
                  AND TIME(r.date_time) < '11:40:00'
                ORDER BY r.date_time ASC, a.nome ASC";
        $consulta = $this->connect->prepare($sql);
        $consulta->execute(['ano' => $this->ano, 'inicio' => $inicio, 'fim' => $fim]);
        $dados = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new PDFRelatorioAtrasos('L', 'mm', 'A4');
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
            $pdf->Cell(0, 12, $this->textoPdf('Nenhum atraso encontrado para o ano e período selecionados.'), 0, 1, 'C');
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

        $pdf->Output('I', 'relatorio_atrasos_ano_geral.pdf');
    }
}

if (isset($_GET['ano'], $_GET['tipo_relatorio'])) {
    new SaidaPDFAnoGeral();
} else {
    header('Location: ../relatorioSaida.php?error=missing_params');
    exit();
}