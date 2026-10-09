<?php

date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/../../config/Database.php');

class PDFRelatorioSaidaEstagio extends FPDF
{
    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, 'Pagina ' . $this->PageNo() . '/{nb} | Gerado em ' . date('d/m/Y H:i'), 0, 0, 'R');
    }
}

class GeradorRelatorioSaidaEstagio
{
    private $connect;
    private $escopoTitulo;
    private $periodoTitulo;
    private $corTurma;

    public function __construct()
    {
        $database = new connect();
        $this->connect = $database->getConnection();
        $this->gerar();
    }

    private function textoPdf($texto)
    {
        return mb_convert_encoding((string) $texto, 'ISO-8859-1', 'UTF-8');
    }

    private function intervaloDatas($periodo)
    {
        $agora = new DateTimeImmutable();
        if ($periodo === 'dia_atual') {
            return [
                $agora->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
                $agora->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            ];
        }
        if ($periodo === 'ultimos_30_dias') {
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

    private function cabecalho($pdf)
    {
        $pdf->SetFillColor(238, 238, 238);
        $pdf->Rect(0, 0, $pdf->GetPageWidth(), 28, 'F');
        $pdf->Image(__DIR__ . '/../../assets/img/logo.png', 12, 8.5, 8, 11);

        $pdf->SetXY(24, 5);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(255, 165, 0);
        $pdf->Cell(0, 8, $this->textoPdf('Relatório de saídas de estágio'), 0, 1, 'L');

        $pdf->SetXY(24, 14);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(110, 110, 110);
        $pdf->Cell(0, 6, $this->textoPdf($this->periodoTitulo), 0, 1, 'L');

        $pdf->SetFillColor($this->corTurma[0], $this->corTurma[1], $this->corTurma[2]);
        $pdf->Rect(12, 32, $pdf->GetPageWidth() - 24, 9, 'F');
        $pdf->SetXY(16, 33);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, $this->textoPdf($this->escopoTitulo), 0, 1, 'L');
    }

    private function cabecalhoTabela($pdf, $colunas)
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

    private function linhaTabela($pdf, $valores, $colunas, $indice)
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
            $this->cabecalho($pdf);
            $pdf->SetY(46);
            $this->cabecalhoTabela($pdf, $colunas);
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

    private function gerar()
    {
        $escopos = ['aluno', 'ano', 'turma'];
        $periodos = [
            'dia_atual' => 'Dia atual',
            'ultimos_30_dias' => 'Últimos 30 dias',
            'ultimos_12_meses' => 'Últimos 12 meses'
        ];
        $escopo = $_GET['escopo'] ?? '';
        $periodo = $_GET['tipo_relatorio'] ?? '';
        if (!in_array($escopo, $escopos, true) || !isset($periodos[$periodo])) {
            header('Location: relatorioSaida_Estagio.php?error=invalid_parameters');
            exit();
        }
        $this->periodoTitulo = $periodos[$periodo];
        list($inicio, $fim) = $this->intervaloDatas($periodo);

        $condicao = '';
        $parametros = ['inicio' => $inicio, 'fim' => $fim];
        if ($escopo === 'aluno') {
            $idAluno = filter_var($_GET['id_aluno'] ?? null, FILTER_VALIDATE_INT);
            if (!$idAluno) {
                header('Location: relatorioSaida_Estagio.php?error=invalid_aluno');
                exit();
            }
            $condicao = ' AND a.id_aluno = :id_aluno AND a.id_turma IN (9, 10, 11, 12)';
            $parametros['id_aluno'] = $idAluno;
            $consultaNome = $this->connect->prepare('SELECT nome FROM aluno WHERE id_aluno = :id_aluno AND id_turma IN (9, 10, 11, 12)');
            $consultaNome->execute(['id_aluno' => $idAluno]);
            $nomeAluno = $consultaNome->fetchColumn();
            if (!$nomeAluno) {
                header('Location: relatorioSaida_Estagio.php?error=invalid_aluno');
                exit();
            }
            $this->escopoTitulo = 'ALUNO: ' . strtoupper($nomeAluno);
            $this->corTurma = [0, 122, 51];
        } elseif ($escopo === 'ano') {
            $ano = filter_var($_GET['ano'] ?? null, FILTER_VALIDATE_INT);
            if ($ano !== 3) {
                header('Location: relatorioSaida_Estagio.php?error=invalid_year');
                exit();
            }
            $condicao = ' AND t.id_turma IN (9, 10, 11, 12)';
            $this->escopoTitulo = '3º ANO - TODAS AS TURMAS';
            $this->corTurma = [0, 122, 51];
        } else {
            $idTurma = filter_var($_GET['id_turma'] ?? null, FILTER_VALIDATE_INT);
            if (!$idTurma || !in_array($idTurma, [9, 10, 11, 12], true)) {
                header('Location: relatorioSaida_Estagio.php?error=invalid_turma');
                exit();
            }
            $condicao = ' AND t.id_turma = :id_turma';
            $parametros['id_turma'] = $idTurma;
            $consultaTurma = $this->connect->prepare('SELECT ano, turma FROM turma WHERE id_turma = :id_turma');
            $consultaTurma->execute(['id_turma' => $idTurma]);
            $turma = $consultaTurma->fetch(PDO::FETCH_ASSOC);
            $this->escopoTitulo = strtoupper($turma['ano'] . ' ' . $turma['turma']);
            $cores = [9 => [220, 53, 69], 10 => [65, 105, 225], 11 => [13, 202, 240], 12 => [108, 117, 125]];
            $this->corTurma = $cores[$idTurma];
        }

        $sql = "SELECT a.nome AS nome_aluno, t.ano, t.turma, se.dae
                FROM saida_estagio se
                INNER JOIN aluno a ON a.id_aluno = se.id_aluno
                INNER JOIN turma t ON t.id_turma = a.id_turma
                WHERE se.dae BETWEEN :inicio AND :fim {$condicao}
                ORDER BY t.id_turma, a.nome, se.dae";
        $consulta = $this->connect->prepare($sql);
        $consulta->execute($parametros);
        $dados = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new PDFRelatorioSaidaEstagio('L', 'mm', 'A4');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $this->cabecalho($pdf);
        $pdf->SetY(46);

        $colunas = [['Aluno', 135], ['Turma', 55], ['Data', 42], ['Hora', 41]];
        $this->cabecalhoTabela($pdf, $colunas);
        if (empty($dados)) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(145, 145, 145);
            $pdf->Cell(0, 12, $this->textoPdf('Nenhuma saída de estágio encontrada para o filtro selecionado.'), 0, 1, 'C');
        } else {
            foreach ($dados as $indice => $dado) {
                $valores = [
                    $dado['nome_aluno'],
                    $dado['ano'] . ' ' . strtoupper($dado['turma']),
                    date('d/m/Y', strtotime($dado['dae'])),
                    date('H:i:s', strtotime($dado['dae']))
                ];
                $this->linhaTabela($pdf, $valores, $colunas, $indice);
            }
        }
        $pdf->Output('I', 'relatorio_saida_estagio_' . $escopo . '.pdf');
    }
}

new GeradorRelatorioSaidaEstagio();
