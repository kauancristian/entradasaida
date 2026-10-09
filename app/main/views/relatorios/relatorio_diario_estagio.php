<?php
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/../../model/select_model.php');

class PDFRelatorioDiarioEstagio extends FPDF
{
    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, 'Pagina ' . $this->PageNo() . '/{nb} | Gerado em ' . date('d/m/Y H:i'), 0, 0, 'R');
    }
}

class RelatorioDiarioEstagio
{
    private $select;
    private $pdf;
    private $data;
    private $coresTurma = [
        '3a' => [220, 53, 69],
        '3b' => [65, 105, 225],
        '3c' => [13, 202, 240],
        '3d' => [108, 117, 125]
    ];

    public function __construct()
    {
        $this->select = new select_model();
        $this->data = date('d/m/Y');
        $this->pdf = new PDFRelatorioDiarioEstagio('L', 'mm', 'A4');
        $this->pdf->SetMargins(12, 12, 12);
        $this->pdf->SetAutoPageBreak(true, 14);
        $this->pdf->AliasNbPages();
        $this->gerarRelatorio();
    }

    private function textoPdf($texto)
    {
        return mb_convert_encoding((string) $texto, 'ISO-8859-1', 'UTF-8');
    }

    private function cabecalhoPagina($titulo, $cor)
    {
        $pdf = $this->pdf;
        $pdf->SetFillColor(238, 238, 238);
        $pdf->Rect(0, 0, $pdf->GetPageWidth(), 28, 'F');
        $pdf->Image(__DIR__ . '/../../assets/img/logo.png', 12, 8.5, 8, 11);

        $pdf->SetXY(24, 5);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(255, 165, 0);
        $pdf->Cell(0, 8, $this->textoPdf('Relatório de saída-estágio'), 0, 1, 'L');

        $pdf->SetXY(24, 14);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(110, 110, 110);
        $pdf->Cell(0, 6, $this->textoPdf('Frequência diária | ' . $this->data), 0, 1, 'L');

        $pdf->SetFillColor($cor[0], $cor[1], $cor[2]);
        $pdf->Rect(12, 32, $pdf->GetPageWidth() - 24, 9, 'F');
        $pdf->SetXY(16, 33);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, $this->textoPdf(strtoupper($titulo)), 0, 1, 'L');
        $pdf->SetY(46);
    }

    private function cabecalhoTabelaFrequencia()
    {
        $pdf = $this->pdf;
        $larguras = [163, 55, 55];
        $pdf->SetFillColor(240, 249, 244);
        $pdf->SetDrawColor(210, 218, 213);
        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell($larguras[0], 8, $this->textoPdf('Estudante'), 1, 0, 'L', true);
        $pdf->Cell($larguras[1], 8, $this->textoPdf('Horário AB'), 1, 0, 'C', true);
        $pdf->Cell($larguras[2], 8, $this->textoPdf('Horário CD'), 1, 1, 'C', true);
        return $larguras;
    }

    private function agruparPresencas($dados)
    {
        $alunos = [];
        foreach ($dados as $dado) {
            $nome = strtoupper($dado['nome']);
            $hora = date('H:i:s', strtotime($dado['dae']));
            if (!isset($alunos[$nome])) {
                $alunos[$nome] = ['ab' => false, 'cd' => false];
            }
            if ($hora <= '14:10:00') {
                $alunos[$nome]['ab'] = true;
                $alunos[$nome]['hora_ab'] = $hora;
            }
            if ($hora >= '15:20:00' && $hora <= '16:10:00') {
                $alunos[$nome]['cd'] = true;
                $alunos[$nome]['hora_cd'] = $hora;
            }
        }
        return $alunos;
    }

    private function imprimirFrequencia($dados, $turma)
    {
        $pdf = $this->pdf;
        $cor = $this->coresTurma[$turma['codigo']];
        $this->cabecalhoPagina($turma['nome'], $cor);
        $larguras = $this->cabecalhoTabelaFrequencia();
        $alunos = $this->agruparPresencas($dados);

        if (empty($alunos)) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(145, 145, 145);
            $pdf->Cell(0, 9, $this->textoPdf('Nenhum aluno registrado nesta turma hoje.'), 0, 1, 'C');
            return;
        }

        $indice = 0;
        foreach ($alunos as $nome => $info) {
            if ($pdf->GetY() + 8 > $pdf->GetPageHeight() - 14) {
                $pdf->AddPage();
                $this->cabecalhoPagina($turma['nome'], $cor);
                $this->cabecalhoTabelaFrequencia();
            }

            $tom = $indice % 2 === 0 ? 255 : 245;
            $pdf->SetFillColor($tom, $tom, $tom);
            $pdf->SetDrawColor(225, 231, 227);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(55, 65, 81);
            $pdf->Cell($larguras[0], 8, $this->textoPdf($nome), 1, 0, 'L', true);

            if ($info['ab']) {
                $textoAB = 'PRESENTE | ' . $info['hora_ab'];
                $pdf->SetTextColor(0, 128, 51);
            } else {
                $textoAB = 'AUSENTE';
                $pdf->SetTextColor(200, 55, 55);
            }
            $pdf->Cell($larguras[1], 8, $this->textoPdf($textoAB), 1, 0, 'C', true);

            if ($info['cd']) {
                $textoCD = 'PRESENTE | ' . $info['hora_cd'];
                $pdf->SetTextColor(0, 128, 51);
            } else {
                $textoCD = 'AUSENTE';
                $pdf->SetTextColor(200, 55, 55);
            }
            $pdf->Cell($larguras[2], 8, $this->textoPdf($textoCD), 1, 1, 'C', true);
            $indice++;
        }
    }

    private function imprimirAusentes($dados)
    {
        $cor = [128, 128, 128];
        $this->cabecalhoPagina('Alunos ausentes no dia ' . $this->data, $cor);
        $pdf = $this->pdf;
        $larguraNome = 150;
        $larguraTurma = 123;
        $pdf->SetFillColor(240, 249, 244);
        $pdf->SetDrawColor(210, 218, 213);
        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell($larguraNome, 8, $this->textoPdf('Estudante'), 1, 0, 'L', true);
        $pdf->Cell($larguraTurma, 8, $this->textoPdf('Turma'), 1, 1, 'L', true);

        if (empty($dados)) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(145, 145, 145);
            $pdf->Cell(0, 9, $this->textoPdf('Nenhum aluno ausente hoje.'), 0, 1, 'C');
            return;
        }

        foreach ($dados as $indice => $dado) {
            if ($pdf->GetY() + 8 > $pdf->GetPageHeight() - 14) {
                $pdf->AddPage();
                $this->cabecalhoPagina('Alunos ausentes no dia ' . $this->data, $cor);
                $pdf->SetFillColor(240, 249, 244);
                $pdf->SetDrawColor(210, 218, 213);
                $pdf->SetTextColor(55, 65, 81);
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell($larguraNome, 8, $this->textoPdf('Estudante'), 1, 0, 'L', true);
                $pdf->Cell($larguraTurma, 8, $this->textoPdf('Turma'), 1, 1, 'L', true);
            }
            $tom = $indice % 2 === 0 ? 255 : 245;
            $pdf->SetFillColor($tom, $tom, $tom);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(55, 65, 81);
            $pdf->Cell($larguraNome, 8, $this->textoPdf(strtoupper($dado['nome'])), 1, 0, 'L', true);
            $pdf->Cell($larguraTurma, 8, $this->textoPdf(strtoupper($dado['turma'])), 1, 1, 'L', true);
        }
    }

    public function gerarRelatorio()
    {
        $turmas = [
            ['codigo' => '3a', 'nome' => '3º A - Enfermagem', 'dados' => $this->select->saida_estagio_3A_relatorio()],
            ['codigo' => '3b', 'nome' => '3º B - Informática', 'dados' => $this->select->saida_estagio_3B_relatorio()],
            ['codigo' => '3c', 'nome' => '3º C - Administração', 'dados' => $this->select->saida_estagio_3C_relatorio()],
            ['codigo' => '3d', 'nome' => '3º D - Edificação', 'dados' => $this->select->saida_estagio_3D_relatorio()]
        ];

        foreach ($turmas as $turma) {
            $this->pdf->AddPage();
            $this->imprimirFrequencia($turma['dados'], $turma);
        }

        $ausentes = array_merge(
            $this->select->alunos_ausentes_3A_relatorio(),
            $this->select->alunos_ausentes_3B_relatorio(),
            $this->select->alunos_ausentes_3C_relatorio(),
            $this->select->alunos_ausentes_3D_relatorio()
        );
        $this->pdf->AddPage();
        $this->imprimirAusentes($ausentes);

        $this->pdf->Output('I', 'relatorio_saida_estagio_diario.pdf');
    }
}

(new RelatorioDiarioEstagio())->gerarRelatorio();