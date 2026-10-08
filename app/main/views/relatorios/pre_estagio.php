<?php
require_once('../../config/Database.php');
require_once('../../assets/lib/fpdf/fpdf.php');
require_once('../../model/select_model.php');

class PDF extends FPDF {
    private $select;
    private $colors = [
        'primary' => [238, 238, 238],    // Cor principal verde (para header/footer)
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

    public function __construct()
    {
        parent::__construct('P', 'pt', 'A4');
        $this->select = new select_model();
        $this->SetMargins(20, 20, 20);
    }

    public function Header()
    {
        date_default_timezone_set('America/Sao_Paulo');
        $this->SetFillColor($this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2]);
        $this->Rect(0, 0, $this->GetPageWidth(), 60, 'F');
        $this->Image('../../assets/img/logo.png', 10, 8, 45, 45);
        
        

        
        
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor($this->colors['secondary'][0], $this->colors['secondary'][1], $this->colors['secondary'][2]);
        $this->Cell(0, 0, utf8_decode('           Preparação para estágio'), 0, 1, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(140, 140, 140);
        $this->Cell(0, 30, utf8_decode('               Turmas 2026.2'), 0, 1, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(140, 140, 140);
        $this->Cell(0, -5, utf8_decode('               Registro de frequencia'), 0, 1, 'L');
        $this->SetDrawColor($this->colors['secondary'][0], $this->colors['secondary'][1], $this->colors['secondary'][2]);
        
        
                ////////////////IMPRESSÃO DA DATA E HORA //////////////////////////////
        date_default_timezone_set('America/Fortaleza'); // ou America/Sao_Paulo
        $dias = [
            'Sunday'    => 'Domingo',
            'Monday'    => 'Segunda-feira',
            'Tuesday'   => 'Terça-feira',
            'Wednesday' => 'Quarta-feira',
            'Thursday'  => 'Quinta-feira',
            'Friday'    => 'Sexta-feira',
            'Saturday'  => 'Sábado'
        ];
        
        $dataAtual = $dias[date('l')] . ', ' . date('d/m/Y');
        $this->SetXY(-90, 12); // distância da direita e do topo
        $this->SetFont('Arial', 'BI', 9);
        $this->SetTextColor(140,140,140);
        $this->Cell(70, 75, utf8_decode($dataAtual), 0, 0, 'R');     
        
        //////////////////////////////////////////////////////////        
        
        
        $this->Ln(55);
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
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->colors['turma_3a'][0], $this->colors['turma_3a'][1], $this->colors['turma_3a'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 18, utf8_decode('3º A - ENFERMAGEM'), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_3a = $this->select->saida_estagio_3A_relatorio();
        $this->Ln(5);
        $this->imprimirAlunos($dados_3a);

        // 3º Ano B
        $this->AddPage();
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->colors['turma_3b'][0], $this->colors['turma_3b'][1], $this->colors['turma_3b'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 18, utf8_decode('3º B - INFORMÁTICA'), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_3b = $this->select->saida_estagio_3B_relatorio();
        $this->Ln(5);
        $this->imprimirAlunos($dados_3b);

        // 3º Ano C
        $this->AddPage();
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->colors['turma_3c'][0], $this->colors['turma_3c'][1], $this->colors['turma_3c'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 18, utf8_decode('3º C - ADMINISTRAÇÃO'), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_3c = $this->select->saida_estagio_3C_relatorio();
        $this->Ln(5);
        $this->imprimirAlunos($dados_3c);

        // 3º Ano D
        $this->AddPage();
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->colors['turma_3d'][0], $this->colors['turma_3d'][1], $this->colors['turma_3d'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 18, utf8_decode('3º D - EDIFICAÇÃO'), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_3d = $this->select->saida_estagio_3D_relatorio();
        $this->Ln(5);
        $this->imprimirAlunos($dados_3d);

        // Alunos Ausentes
        $this->AddPage();
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->colors['ausentes'][0], $this->colors['ausentes'][1], $this->colors['ausentes'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 18, utf8_decode('Alunos Ausentes no Dia ' . date('d/m/Y')), 0, 1, 'L', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($this->colors['dark'][0], $this->colors['dark'][1], $this->colors['dark'][2]);
        $dados_ausentes = array_merge(
            $this->select->alunos_ausentes_3A_relatorio(),
            $this->select->alunos_ausentes_3B_relatorio(),
            $this->select->alunos_ausentes_3C_relatorio(),
            $this->select->alunos_ausentes_3D_relatorio()
        );
        $this->Ln(5);
        $this->imprimirAlunosAusentes($dados_ausentes);
    }




public function imprimirAlunos($dados)
{
    if (empty($dados)) {
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Nenhum aluno registrado.',0,1);
        return;
    }

    // Agrupa os registros por aluno
    $alunos = [];

    foreach ($dados as $dado){

        $nome = strtoupper($dado['nome']);
        $hora = date('H:i:s', strtotime($dado['dae']));

        if(!isset($alunos[$nome])){
            $alunos[$nome] = [
                'ab' => false,
                'cd' => false,
            ];
        }

        // AB
        if($hora <= '14:10:00'){
            $alunos[$nome]['ab'] = true;
            $alunos[$nome]['hora_ab'] = $hora;
        }

        // CD
        if($hora >= '15:20:00' && $hora <= '16:10:00'){
            $alunos[$nome]['cd'] = true;
            $alunos[$nome]['hora_cd'] = $hora;
        }
    }

    $pageWidth = $this->GetPageWidth()-40;

    $wNome = $pageWidth*0.68;
    $wAB = $pageWidth*0.16;
    $wCD = $pageWidth*0.16;


    // Cabeçalho
    $this->SetFont('Arial','B',9);

    $this->Cell($wNome,13,'ESTUDANTES',1,0,'L',true);
    $this->Cell($wAB,13,utf8_decode('HORÁRIO AB'),1,0,'C',true);
    $this->Cell($wCD,13,utf8_decode('HORÁRIO CD'),1,1,'C',true);


    $this->SetFont('Arial','',8);

    $i=0;

    foreach($alunos as $nome=>$info){

        $this->SetFillColor(
            $i%2==0 ? 255 : $this->colors['light_gray_row'][0],
            $i%2==0 ? 255 : $this->colors['light_gray_row'][1],
            $i%2==0 ? 255 : $this->colors['light_gray_row'][2]
        );

        $this->SetTextColor(55,65,81);
        $this->Cell($wNome,12,utf8_decode($nome),1,0,'L',true);



        // AB
        if($info['ab']){
            $this->SetTextColor(0,150,0);
            $texto='PRESENTE | '.$info['hora_ab'];
        }else{
            $this->SetTextColor(220,53,69);
            $texto='AUSENTE';
        }

        $this->Cell($wAB,12,utf8_decode($texto),1,0,'C',true);

   
   
        // CD
        if($info['cd']){
            $this->SetTextColor(0,150,0);
            $texto='PRESENTE | '.$info['hora_cd'];
        }else{
            $this->SetTextColor(220,53,69);
            $texto='AUSENTE';
        }

        $this->Cell($wCD,12,utf8_decode($texto),1,1,'C',true);



        $this->SetTextColor(55,65,81);


        $i++;
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
$pdf->Output(utf8_decode('Frequência de Saída.pdf'), 'I');
?>