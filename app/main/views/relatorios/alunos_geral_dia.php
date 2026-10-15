<?php
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/_layout_pdf.php');
require_once(__DIR__ . '/../../model/select_model.php');

class PDFRelatorioAtrasosDia extends FPDF
{
    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, 'Pagina ' . $this->PageNo() . '/{nb} | Gerado em ' . date('d/m/Y H:i'), 0, 0, 'R');
    }
}

class RelatorioAtrasosDia
{
    private $select;
    private $data;
    private $todosRegistros;

    public function __construct()
    {
        $this->select = new select_model();
        $data = $_POST['data'] ?? '';
        $this->todosRegistros = $data === '';
        if (!$this->todosRegistros) {
            $dataValida = DateTime::createFromFormat('!Y-m-d', $data);
            $this->data = $dataValida && $dataValida->format('Y-m-d') === $data
                ? $data
                : date('Y-m-d');
        }
        $this->gerarPdf();
    }

    private function textoPdf($texto)
    {
        return mb_convert_encoding((string) $texto, 'ISO-8859-1', 'UTF-8');
    }

    private function imprimirCabecalho($pdf)
    {
        $titulo = $this->todosRegistros
            ? 'Relatório de atrasos registrados'
            : 'Relatório de atrasos por dia';
        $periodo = $this->todosRegistros
            ? 'Período: todos os registros'
            : 'Data: ' . date('d/m/Y', strtotime($this->data));
        aplicarCabecalhoRelatorio($pdf, $titulo, $periodo);
    }

    private function imprimirCabecalhoTabela($pdf, $larguras)
    {
        $pdf->SetFillColor(240, 249, 244);
        $pdf->SetDrawColor(210, 218, 213);
        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('Arial', 'B', 9);
        $indice = 0;
        if ($this->todosRegistros) {
            $pdf->Cell($larguras[$indice], 8, 'Data', 1, 0, 'C', true);
            $indice++;
        }
        $pdf->Cell($larguras[$indice], 8, 'Aluno', 1, 0, 'L', true);
        $pdf->Cell($larguras[$indice + 1], 8, 'Horario', 1, 0, 'C', true);
        $pdf->Cell($larguras[$indice + 2], 8, 'Status da justificativa', 1, 1, 'C', true);
    }

    private function imprimirLinha($pdf, $dado, $status, $larguras, $indice)
    {
        $altura = 7;
        if ($pdf->GetY() + $altura > $pdf->GetPageHeight() - 15) {
            $pdf->AddPage();
            $this->imprimirCabecalho($pdf);
            $this->imprimirCabecalhoTabela($pdf, $larguras);
        }

        $tom = $indice % 2 === 0 ? 255 : 245;
        $pdf->SetFillColor($tom, $tom, $tom);
        $pdf->SetDrawColor(225, 231, 227);
        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('Arial', '', 9);
        $indiceColuna = 0;
        if ($this->todosRegistros) {
            $pdf->Cell(
                $larguras[$indiceColuna],
                $altura,
                date('d/m/Y', strtotime($dado['date_time'])),
                1,
                0,
                'C',
                true
            );
            $indiceColuna++;
        }
        $pdf->Cell($larguras[$indiceColuna], $altura, $this->textoPdf(strtoupper($dado['nome'])), 1, 0, 'L', true);
        $pdf->Cell(
            $larguras[$indiceColuna + 1],
            $altura,
            date('H:i', strtotime($dado['date_time'])),
            1,
            0,
            'C',
            true
        );
        $pdf->Cell($larguras[$indiceColuna + 2], $altura, $this->textoPdf($status), 1, 1, 'C', true);
    }

    public function gerarPdf()
    {
        $dados = $this->todosRegistros
            ? $this->select->todosAtrasos()
            : $this->select->atrasosPorData($this->data);
        $turmas = [
            9 => ['titulo' => '3º A - ENFERMAGEM', 'cor' => [220, 53, 69]],
            10 => ['titulo' => '3º B - INFORMÁTICA', 'cor' => [65, 105, 225]],
            11 => ['titulo' => '3º C - ADMINISTRAÇÃO', 'cor' => [13, 202, 240]],
            12 => ['titulo' => '3º D - EDIFICAÇÃO', 'cor' => [108, 117, 125]]
        ];
        $dadosPorTurma = [];
        foreach ($dados as $dado) {
            $idTurma = (int) $dado['id_turma'];
            if (isset($turmas[$idTurma])) {
                $dadosPorTurma[$idTurma][] = $dado;
            }
        }

        $pdf = new PDFRelatorioAtrasosDia('L', 'mm', 'A4');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $this->imprimirCabecalho($pdf);
        $larguras = $this->todosRegistros ? [28, 140, 30, 62] : [165, 45, 63];
        $statusNomes = ['pendente' => 'Pendente', 'aprovada' => 'Aprovada', 'recusada' => 'Recusada'];

        if (empty($dadosPorTurma)) {
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(145, 145, 145);
            $mensagemVazia = $this->todosRegistros
                ? 'Nenhum atraso registrado.'
                : 'Nenhum atraso registrado nesta data.';
            $pdf->Cell(0, 12, $this->textoPdf($mensagemVazia), 0, 1, 'C');
        } else {
            foreach ($turmas as $idTurma => $turma) {
                if (empty($dadosPorTurma[$idTurma])) {
                    continue;
                }

                if ($pdf->GetY() + 18 > $pdf->GetPageHeight() - 15) {
                    $pdf->AddPage();
                    $this->imprimirCabecalho($pdf);
                }

                $pdf->SetFillColor($turma['cor'][0], $turma['cor'][1], $turma['cor'][2]);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(0, 8, $this->textoPdf($turma['titulo']), 0, 1, 'L', true);
                $this->imprimirCabecalhoTabela($pdf, $larguras);

                foreach ($dadosPorTurma[$idTurma] as $indice => $dado) {
                    $status = $dado['status_justificativa'] ?? 'pendente';
                    $this->imprimirLinha(
                        $pdf,
                        $dado,
                        $statusNomes[$status] ?? 'Pendente',
                        $larguras,
                        $indice
                    );
                }
                $pdf->Ln(3);
            }
        }

        $pdf->Output('I', 'Atrasos registrados.pdf');
    }
}

new RelatorioAtrasosDia();