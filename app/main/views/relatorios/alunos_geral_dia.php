<?php
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../assets/lib/fpdf/fpdf.php');
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

    public function __construct()
    {
        $this->select = new select_model();
        $data = $_POST['data'] ?? '';
        $dataValida = DateTime::createFromFormat('!Y-m-d', $data);
        $this->data = $dataValida && $dataValida->format('Y-m-d') === $data
            ? $data
            : date('Y-m-d');
        $this->gerarPdf();
    }

    private function textoPdf($texto)
    {
        return mb_convert_encoding((string) $texto, 'ISO-8859-1', 'UTF-8');
    }

    private function imprimirCabecalho($pdf)
    {
        $pdf->SetFillColor(238, 238, 238);
        $pdf->Rect(0, 0, $pdf->GetPageWidth(), 28, 'F');
        $pdf->Image(__DIR__ . '/../../assets/img/logo.png', 12, 8.5, 8, 11);

        $pdf->SetXY(24, 5);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(255, 165, 0);
        $pdf->Cell(0, 8, $this->textoPdf('Relatório de atrasos por dia'), 0, 1, 'L');

        $pdf->SetXY(24, 14);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(110, 110, 110);
        $pdf->Cell(0, 6, $this->textoPdf('Data: ' . date('d/m/Y', strtotime($this->data))), 0, 1, 'L');
    }

    private function imprimirCabecalhoTabela($pdf, $larguras)
    {
        $pdf->SetFillColor(240, 249, 244);
        $pdf->SetDrawColor(210, 218, 213);
        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell($larguras[0], 8, 'Aluno', 1, 0, 'L', true);
        $pdf->Cell($larguras[1], 8, 'Horario', 1, 0, 'C', true);
        $pdf->Cell($larguras[2], 8, 'Justificativa', 1, 1, 'C', true);
    }

    private function imprimirLinha($pdf, $nome, $horario, $status, $larguras, $indice)
    {
        $altura = 7;
        if ($pdf->GetY() + $altura > $pdf->GetPageHeight() - 15) {
            $pdf->AddPage();
            $this->imprimirCabecalho($pdf);
            $pdf->SetY(32);
            $this->imprimirCabecalhoTabela($pdf, $larguras);
        }

        $tom = $indice % 2 === 0 ? 255 : 245;
        $pdf->SetFillColor($tom, $tom, $tom);
        $pdf->SetDrawColor(225, 231, 227);
        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell($larguras[0], $altura, $this->textoPdf(strtoupper($nome)), 1, 0, 'L', true);
        $pdf->Cell($larguras[1], $altura, $horario, 1, 0, 'C', true);
        $pdf->Cell($larguras[2], $altura, $this->textoPdf($status), 1, 1, 'C', true);
    }

    public function gerarPdf()
    {
        $dados = $this->select->atrasosPorData($this->data);
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
        $larguras = [165, 45, 63];
        $statusNomes = ['pendente' => 'Pendente', 'aprovada' => 'Aprovada', 'recusada' => 'Recusada'];

        if (empty($dadosPorTurma)) {
            $pdf->SetY(38);
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->SetTextColor(145, 145, 145);
            $pdf->Cell(0, 12, $this->textoPdf('Nenhum atraso registrado nesta data.'), 0, 1, 'C');
        } else {
            foreach ($turmas as $idTurma => $turma) {
                if (empty($dadosPorTurma[$idTurma])) {
                    continue;
                }

                if ($pdf->GetY() + 18 > $pdf->GetPageHeight() - 15) {
                    $pdf->AddPage();
                    $this->imprimirCabecalho($pdf);
                    $pdf->SetY(32);
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
                        $dado['nome'],
                        date('H:i', strtotime($dado['date_time'])),
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