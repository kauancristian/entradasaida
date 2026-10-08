<?php
require_once('../../assets/lib/fpdf/fpdf.php');

// Conexão direta com PDO (ajuste o nome do banco se necessário)
try {
    $db = new PDO('mysql:host=localhost;dbname=entradasaida;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}

$turmas = [
    '3A' => ['id' => 9, 'nome' => '3º Ano A'],
    '3B' => ['id' => 10, 'nome' => '3º Ano B'],
    '3C' => ['id' => 11, 'nome' => '3º Ano C'],
    '3D' => ['id' => 12, 'nome' => '3º Ano D'],
];

$pdf = new FPDF();
$pdf->SetAutoPageBreak(true, 20);

$primeira = true;
foreach ($turmas as $sigla => $turma) {
    if (!$primeira) {
        $pdf->AddPage();
    } else {
        $pdf->AddPage();
        $primeira = false;
    }

    // Cabeçalho estilizado
    $pdf->SetFillColor(0, 140, 69); // Verde
    $pdf->Rect(0, 0, 210, 20, 'F');
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 13, utf8_decode('Relatório de Alunos - ' . $turma['nome']), 0, 1, 'C');
    $pdf->Ln(8);

    // Tabela
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255); // Fundo branco
    $pdf->SetDrawColor(0, 0, 0); // Borda preta
    $pdf->Cell(120, 10, utf8_decode('Nome'), 1, 0, 'C', true); // Centralizado
    $pdf->Cell(50, 10, utf8_decode('Matrícula'), 1, 1, 'C', true);

    $stmt = $db->prepare("SELECT nome, matricula FROM aluno WHERE id_turma = :id_turma ORDER BY nome ASC");
    $stmt->bindValue(':id_turma', $turma['id'], PDO::PARAM_INT);
    $stmt->execute();
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdf->SetFont('Arial', '', 11);
    foreach ($alunos as $aluno) {
        $pdf->Cell(120, 9, utf8_decode($aluno['nome']), 0, 0, 'L'); // Nome à esquerda
        $pdf->Cell(50, 9, utf8_decode($aluno['matricula']), 0, 1, 'C'); // Matrícula centralizada
    }
    if (count($alunos) == 0) {
        $pdf->Cell(170, 9, utf8_decode('Nenhum aluno encontrado.'), 1, 1, 'C');
    }

    // Linha de separação visual no rodapé
    $pdf->SetY(-30);
    $pdf->SetDrawColor(255, 165, 0);
    $pdf->SetLineWidth(1.5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
}

$pdf->Output('I', 'relatorio_geral_alunos.pdf');
