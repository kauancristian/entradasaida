<?php

require_once('../../config/Database.php');
require_once('../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/_layout_pdf.php');
require_once('../../model/select_model.php');

date_default_timezone_set('America/Fortaleza');


class PDF extends FPDF {

    private $select;
    private $data;

    public function __construct()
    {
        // Largura 80mm (bobina térmica)
        parent::__construct('P', 'mm', array(80, 297));

        $this->select = new select_model();
        $this->data = isset($_POST['data'])
            ? $_POST['data']
            : date('Y-m-d');

        $this->SetMargins(3, 3, 3);
        $this->SetAutoPageBreak(true, 5);
    }

    public function Header()
    {
        aplicarCabecalhoRelatorio($this, 'Relatório de ausências', 'Data: ' . date('d/m/Y', strtotime($this->data)));
    }

    // Sem rodapé
    public function Footer()
    {
    }

   public function generateReport()
{
    $this->AliasNbPages();
    $this->AddPage();

    $this->Ln(3);

    // Turmas
    $turmas = [
        '3ºA - ENFERMAGEM' => $this->select->alunos_ausentes_3A_relatorio_dia($this->data),
        '3ºB - INFORMÁTICA' => $this->select->alunos_ausentes_3B_relatorio_dia($this->data),
        '3ºC - ADMINISTRAÇÃO' => $this->select->alunos_ausentes_3C_relatorio_dia($this->data),
        '3ºD - EDIFICAÇÃO' => $this->select->alunos_ausentes_3D_relatorio_dia($this->data)
    ];

    $totalGeral = 0;

    foreach ($turmas as $nomeTurma => $alunos) {

        // Linha separadora
        $this->SetFont('Courier', '', 9);
        $this->Cell(
            0,
            4,
            '======================================',
            0,
            1
        );

        // Cabeçalho da turma
        $this->SetFont('Courier', 'B', 10);
        $this->MultiCell(
            0,
            5,
            utf8_decode('TURMA: ' . $nomeTurma)
        );

        $this->Ln(1);

        if (empty($alunos)) {

            $this->SetFont('Courier', 'B', 9);
            $this->Cell(
                0,
                5,
                utf8_decode('NENHUM AUSENTE'),
                0,
                1
            );

        } else {

            foreach ($alunos as $aluno) {

                $this->SetFont('Courier', 'B', 8);

                $this->MultiCell(
                    0,
                    5,
                    utf8_decode(
                        strtoupper($aluno['nome'])
                    )
                );

                $totalGeral++;
            }
        }

        $this->Ln(2);
    }
                $this->SetFont('Courier', '', 9);

    $this->Cell(
        0,
        4,
            '======================================',
            0,
        1
    );

    $this->SetFont('Courier', 'B', 10);
    $this->Cell(
        0,
        6,
        utf8_decode('TOTAL GERAL: ' . $totalGeral),
        0,
        1,
        'C'
    );

    $this->Ln(3);

    $this->SetFont('Courier', 'B', 8);
    $this->Cell(
        0,
        4,
        date('d/m/Y H:i:s'),
        0,
        1,
        'C'
    );
}
}

// Geração do PDF
$pdf = new PDF();
$pdf->generateReport();
$pdf->Output('Ausentes_'.$pdf->PageNo().'.pdf', 'I');

?>