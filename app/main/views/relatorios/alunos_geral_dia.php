<?php
require_once('../../config/Database.php');
require_once('../../assets/lib/fpdf/fpdf.php');
require_once('../../model/select_model.php');

class PDF extends FPDF {
    private $select;
    private $colors = [
        'primary' => [0, 122, 51],    // Cor principal verde (para header/footer)
        'secondary' => [255, 165, 0], // Cor secundária laranja (para linhas decorativas)
        'light_green' => [240, 249, 244], // Verde claro para fundo de cabeçalho de tabela
        'dark' => [55, 65, 81],       // Cinza escuro para texto geral
        'gray_border' => [229, 231, 235], // Cinza claro para bordas da tabela
        'light_gray_row' => [245, 245, 245], // Cinza claro para linhas ímpares da tabela
        'turma_3a' => [220, 53, 69],  // Vermelho (danger)
        'turma_3b' => [65, 105, 225], // Azul (info)
        'turma_3c' => [13, 202, 240], // Ciano (admin)
        'turma_3d' => [108, 117, 125], // Cinza (grey)
        'ausentes' => [128, 128, 128] // Cinza médio para ausentes
    ];
    private $data;

    public function __construct()
    {
        parent::__construct('P', 'pt', 'A4');
        $this->select = new select_model();
        $this->data = isset($_POST['data']) ? $_POST['data'] : date('Y-m-d');
        $this->SetMargins(20, 20, 20);
    }

    public function Header()
    {
        date_default_timezone_set('America/Sao_Paulo');
        $this->SetFillColor($this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2]);
        $this->Rect(0, 0, $this->GetPageWidth(), 60, 'F');
        $this->Image('../../assets/img/logo.png', 18, 8, 40, 40);
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor($this->colors['secondary'][0], $this->colors['secondary'][1], $this->colors['secondary'][2]);
        $this->Cell(0, 0, utf8_decode('Frequência de Saída'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 30, utf8_decode('Estágio 2025'), 0, 1, 'C');
        $this->SetDrawColor($this->colors['secondary'][0], $this->colors['secondary'][1], $this->colors['secondary'][2]);
        $this->Ln(15);
    }

    public function Footer()
    {
        $this->SetY(-20);
        $this->SetDrawColor($this->colors['secondary'][0], $this->colors['secondary'][1], $this->colors['secondary'][2]);
        $this->SetLineWidth(0.5);
        $this->Line(40, $this->GetY(), $this->GetPageWidth() - 40, $this->GetY());
        $this->Ln(5);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor($this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2]);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 10, utf8_decode('Gerado em: ' . date('d/m/Y H:i:s')), 0, 0, 'R');
    }

    public function generateReport()
    {
        $this->AliasNbPages();

        // 3º Ano A
        $this->AddPage();
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->colors['turma_3a'][0], $this->colors['turma_3a'][1], $this->colors['turma_3a'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 20, utf8_decode('3ºA - ENFERMAGEM'), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_3a = $this->select->saida_estagio_3A_relatorio_dia($this->data);
        $this->imprimirAlunos($dados_3a);

        // 3º Ano B
        $this->AddPage();
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->colors['turma_3b'][0], $this->colors['turma_3b'][1], $this->colors['turma_3b'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 20, utf8_decode('3ºB - INFORMÁTICA'), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_3b = $this->select->saida_estagio_3B_relatorio_dia($this->data);
        $this->imprimirAlunos($dados_3b);

        // 3º Ano C
        $this->AddPage();
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->colors['turma_3c'][0], $this->colors['turma_3c'][1], $this->colors['turma_3c'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 20, utf8_decode('3ºC - ADMINISTRAÇÃO'), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_3c = $this->select->saida_estagio_3C_relatorio_dia($this->data);
        $this->imprimirAlunos($dados_3c);

        // 3º Ano D
        $this->AddPage();
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->colors['turma_3d'][0], $this->colors['turma_3d'][1], $this->colors['turma_3d'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 20, utf8_decode('3ºD - EDIFICAÇÃO'), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_3d = $this->select->saida_estagio_3D_relatorio_dia($this->data);
        $this->imprimirAlunos($dados_3d);

        // Alunos Ausentes
        $this->AddPage();
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->colors['ausentes'][0], $this->colors['ausentes'][1], $this->colors['ausentes'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 20, utf8_decode('Alunos Ausentes no Dia ' . date('d/m/Y', strtotime($this->data))), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_ausentes = array_merge(
            $this->select->alunos_ausentes_3A_relatorio_dia($this->data),
            $this->select->alunos_ausentes_3B_relatorio_dia($this->data),
            $this->select->alunos_ausentes_3C_relatorio_dia($this->data),
            $this->select->alunos_ausentes_3D_relatorio_dia($this->data)
        );
        $this->imprimirAlunosAusentes($dados_ausentes);
    }

    public function imprimirAlunos($dados) {
        if (empty($dados)) {
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
            $this->Cell(0, 10, strtoupper(utf8_decode('Nenhum aluno registrado hoje')), 0, 1, 'L');
            return;
        }

        $this->SetFillColor($this->colors['light_green'][0], $this->colors['light_green'][1], $this->colors['light_green'][2]);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $this->SetDrawColor($this->colors['gray_border'][0], $this->colors['gray_border'][1], $this->colors['gray_border'][2]);
        $this->SetLineWidth(0.2);

        $pageWidth = $this->GetPageWidth() - 40;
        $colWidthNome = $pageWidth * 0.8;
        $colWidthHorario = $pageWidth * 0.2;

        $this->SetFont('Arial', '', 8);
        $rowCounter = 0;
        foreach ($dados as $dado) {
            $this->SetFillColor($rowCounter % 2 == 0 ? 255 : $this->colors['light_gray_row'][0],
                              $rowCounter % 2 == 0 ? 255 : $this->colors['light_gray_row'][1],
                              $rowCounter % 2 == 0 ? 255 : $this->colors['light_gray_row'][2]);
            $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
            $this->Cell($colWidthNome, 10, utf8_decode(strtoupper($dado['nome'])), 1, 0, 'L', true);
            $this->Cell($colWidthHorario, 10, isset($dado['dae']) ? date('d/m/Y H:i:s', strtotime($dado['dae'])) : '--:--', 1, 1, 'R', true);
            $rowCounter++;
        }
    }

    public function imprimirAlunosAusentes($dados) {
        if (empty($dados)) {
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
            $this->Cell(0, 10, strtoupper(utf8_decode('Nenhum aluno ausente hoje')), 0, 1, 'L');
            return;
        }

        $this->SetFillColor($this->colors['light_green'][0], $this->colors['light_green'][1], $this->colors['light_green'][2]);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $this->SetDrawColor($this->colors['gray_border'][0], $this->colors['gray_border'][1], $this->colors['gray_border'][2]);
        $this->SetLineWidth(0.2);

        $pageWidth = $this->GetPageWidth() - 40;
        $colWidthNome = $pageWidth * 0.5;
        $colWidthTurma = $pageWidth * 0.5;

        $this->Cell($colWidthNome, 12, utf8_decode('Nome'), 1, 0, 'L', true);
        $this->Cell($colWidthTurma, 12, utf8_decode('Turma'), 1, 1, 'L', true);

        $this->SetFont('Arial', '', 8);
        $rowCounter = 0;
        foreach ($dados as $dado) {
            $this->SetFillColor($rowCounter % 2 == 0 ? 255 : $this->colors['light_gray_row'][0],
                              $rowCounter % 2 == 0 ? 255 : $this->colors['light_gray_row'][1],
                              $rowCounter % 2 == 0 ? 255 : $this->colors['light_gray_row'][2]);
            $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
            $this->Cell($colWidthNome, 10, utf8_decode(strtoupper($dado['nome'])), 1, 0, 'L', true);
            $this->Cell($colWidthTurma, 10, utf8_decode(strtoupper($dado['turma'])), 1, 1, 'L', true);
            $rowCounter++;
        }
    }
}

$pdf = new PDF();
$pdf->generateReport();
$pdf->Output('Frequência de Saída.pdf', 'I');
?>