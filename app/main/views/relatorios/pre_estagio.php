<?php
require_once('../../config/Database.php');
require_once('../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/_layout_pdf.php');
require_once('../../model/select_model.php');

class PDF extends FPDF
{
    private $select;
    private $colors = [
        'light_green' => [240, 249, 244],
        'dark' => [55, 65, 81],
        'gray_border' => [210, 218, 213],
        'light_gray_row' => [245, 245, 245],
        'turma_3a' => [220, 53, 69],
        'turma_3b' => [65, 105, 225],
        'turma_3c' => [13, 202, 240],
        'turma_3d' => [108, 117, 125],
        'ausentes' => [128, 128, 128]
    ];

    public function __construct()
    {
        parent::__construct('L', 'mm', 'A4');
        $this->select = new select_model();
        $this->SetMargins(12, 12, 12);
        $this->SetAutoPageBreak(true, 14);
    }

    public function Header()
    {
        aplicarCabecalhoRelatorio($this, 'Preparação para estágio', 'Turmas 2026.2 | Registro de frequência');
    }

    public function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, 'Pagina ' . $this->PageNo() . '/{nb} | Gerado em ' . date('d/m/Y H:i'), 0, 0, 'R');
    }

    private function cabecalhoSecao($titulo, $cor)
    {
        $y = $this->GetY();
        $this->SetFillColor($cor[0], $cor[1], $cor[2]);
        $this->Rect(12, $y, $this->GetPageWidth() - 24, 9, 'F');
        $this->SetXY(16, $y + 1);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 7, textoRelatorioPdf($titulo), 0, 1, 'L');
        $this->SetY($y + 14);
    }

    private function cabecalhoTabelaFrequencia()
    {
        $larguras = [163, 55, 55];
        $this->SetFillColor(
            $this->colors['light_green'][0],
            $this->colors['light_green'][1],
            $this->colors['light_green'][2]
        );
        $this->SetDrawColor(
            $this->colors['gray_border'][0],
            $this->colors['gray_border'][1],
            $this->colors['gray_border'][2]
        );
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($larguras[0], 8, textoRelatorioPdf('Estudantes'), 1, 0, 'L', true);
        $this->Cell($larguras[1], 8, textoRelatorioPdf('Horário AB'), 1, 0, 'C', true);
        $this->Cell($larguras[2], 8, textoRelatorioPdf('Horário CD'), 1, 1, 'C', true);
        return $larguras;
    }

    private function imprimirAlunos($dados, $titulo, $cor)
    {
        $this->cabecalhoSecao($titulo, $cor);
        $larguras = $this->cabecalhoTabelaFrequencia();
        if (empty($dados)) {
            $this->SetFont('Arial', 'I', 9);
            $this->SetTextColor(145, 145, 145);
            $this->Cell(0, 9, textoRelatorioPdf('Nenhum aluno registrado.'), 0, 1, 'C');
            return;
        }

        $alunos = [];
        foreach ($dados as $dado) {
            $nome = strtoupper($dado['nome']);
            $hora = date('H:i:s', strtotime($dado['dae']));

            if (!isset($alunos[$nome])) {
                $alunos[$nome] = [
                    'ab' => false,
                    'cd' => false,
                ];
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

        $indice = 0;
        foreach ($alunos as $nome => $info) {
            if ($this->GetY() + 8 > $this->GetPageHeight() - 14) {
                $this->AddPage();
                $this->cabecalhoSecao($titulo, $cor);
                $this->cabecalhoTabelaFrequencia();
            }

            $tom = $indice % 2 === 0 ? 255 : $this->colors['light_gray_row'][0];
            $this->SetFillColor($tom, $tom, $tom);
            $this->SetDrawColor(
                $this->colors['gray_border'][0],
                $this->colors['gray_border'][1],
                $this->colors['gray_border'][2]
            );
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
            $this->Cell($larguras[0], 8, textoRelatorioPdf($nome), 1, 0, 'L', true);

            if ($info['ab']) {
                $texto = 'PRESENTE | ' . $info['hora_ab'];
                $this->SetTextColor(0, 128, 51);
            } else {
                $texto = 'AUSENTE';
                $this->SetTextColor(200, 55, 55);
            }
            $this->Cell($larguras[1], 8, textoRelatorioPdf($texto), 1, 0, 'C', true);

            if ($info['cd']) {
                $texto = 'PRESENTE | ' . $info['hora_cd'];
                $this->SetTextColor(0, 128, 51);
            } else {
                $texto = 'AUSENTE';
                $this->SetTextColor(200, 55, 55);
            }
            $this->Cell($larguras[2], 8, textoRelatorioPdf($texto), 1, 1, 'C', true);
            $indice++;
        }
    }

    private function imprimirAlunosAusentes($dados)
    {
        $titulo = 'Alunos ausentes no dia ' . date('d/m/Y');
        $cor = $this->colors['ausentes'];
        $this->cabecalhoSecao($titulo, $cor);

        $larguras = [150, 123];
        $this->SetFillColor(
            $this->colors['light_green'][0],
            $this->colors['light_green'][1],
            $this->colors['light_green'][2]
        );
        $this->SetDrawColor(
            $this->colors['gray_border'][0],
            $this->colors['gray_border'][1],
            $this->colors['gray_border'][2]
        );
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($larguras[0], 8, textoRelatorioPdf('Estudante'), 1, 0, 'L', true);
        $this->Cell($larguras[1], 8, textoRelatorioPdf('Turma'), 1, 1, 'L', true);

        if (empty($dados)) {
            $this->SetFont('Arial', 'I', 9);
            $this->SetTextColor(145, 145, 145);
            $this->Cell(0, 9, textoRelatorioPdf('Nenhum aluno ausente hoje.'), 0, 1, 'C');
            return;
        }

        foreach ($dados as $indice => $dado) {
            if ($this->GetY() + 8 > $this->GetPageHeight() - 14) {
                $this->AddPage();
                $this->cabecalhoSecao($titulo, $cor);
                $this->SetFillColor(
                    $this->colors['light_green'][0],
                    $this->colors['light_green'][1],
                    $this->colors['light_green'][2]
                );
                $this->SetDrawColor(
                    $this->colors['gray_border'][0],
                    $this->colors['gray_border'][1],
                    $this->colors['gray_border'][2]
                );
                $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell($larguras[0], 8, textoRelatorioPdf('Estudante'), 1, 0, 'L', true);
                $this->Cell($larguras[1], 8, textoRelatorioPdf('Turma'), 1, 1, 'L', true);
            }

            $tom = $indice % 2 === 0 ? 255 : $this->colors['light_gray_row'][0];
            $this->SetFillColor($tom, $tom, $tom);
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
            $this->Cell($larguras[0], 8, textoRelatorioPdf(strtoupper($dado['nome'])), 1, 0, 'L', true);
            $this->Cell($larguras[1], 8, textoRelatorioPdf(strtoupper($dado['turma'])), 1, 1, 'L', true);
        }
    }

    public function generateReport()
    {
        $this->AliasNbPages();

        $turmas = [
            [
                'titulo' => '3º A - ENFERMAGEM',
                'cor' => $this->colors['turma_3a'],
                'dados' => $this->select->saida_estagio_3A_relatorio()
            ],
            [
                'titulo' => '3º B - INFORMÁTICA',
                'cor' => $this->colors['turma_3b'],
                'dados' => $this->select->saida_estagio_3B_relatorio()
            ],
            [
                'titulo' => '3º C - ADMINISTRAÇÃO',
                'cor' => $this->colors['turma_3c'],
                'dados' => $this->select->saida_estagio_3C_relatorio()
            ],
            [
                'titulo' => '3º D - EDIFICAÇÃO',
                'cor' => $this->colors['turma_3d'],
                'dados' => $this->select->saida_estagio_3D_relatorio()
            ]
        ];

        foreach ($turmas as $turma) {
            $this->AddPage();
            $this->imprimirAlunos($turma['dados'], $turma['titulo'], $turma['cor']);
        }

        $dadosAusentes = array_merge(
            $this->select->alunos_ausentes_3A_relatorio(),
            $this->select->alunos_ausentes_3B_relatorio(),
            $this->select->alunos_ausentes_3C_relatorio(),
            $this->select->alunos_ausentes_3D_relatorio()
        );
        $this->AddPage();
        $this->imprimirAlunosAusentes($dadosAusentes);
    }
}

$pdf = new PDF();
$pdf->generateReport();
$pdf->Output('I', textoRelatorioPdf('Frequência de Saída.pdf'));
?>
