<?php
date_default_timezone_set('America/Sao_Paulo');

// Configurar logs e desativar exibição de erros na tela
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/../../config/Database.php');
require_once('../../model/model_indexClass.php'); 

class EntradaPDFAnoGeral
{
    private $db;
    private $connect;

    public function __construct()
    {
        $this->db = new Database();
        $this->connect = $this->db->connect();
        $this->pdf();
    }

    public function pdf()
    {
        $pdf = new FPDF("L", "pt", "A4");
        $pdf->AddPage();

        // TÍTULO
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(0, 30, mb_convert_encoding('Relatório de Entrada - Ano Geral', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // SUBTÍTULO
        $pdf->SetFont('Arial', 'I', 14);
        $pdf->Cell(0, 20, mb_convert_encoding($_GET['tipo_relatorio'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(10);

        $ano = filter_var($_GET['ano'], FILTER_VALIDATE_INT);
        if (!$ano || $ano < 1 || $ano > 3) {
            error_log("Erro: ano inválido ({$_GET['ano']})");
            header('Location: ../relatorioEntrada.php?error=invalid_ano');
            exit();
        }

        $ano_nome = [
            1 => '1º Ano',
            2 => '2º Ano',
            3 => '3º Ano'
        ];

        $cores = [
            1 => ['r' => 255, 'g' => 165, 'b' => 0],   // Laranja para 1º ano
            2 => ['r' => 255, 'g' => 105, 'b' => 180], // Rosa para 2º ano
            3 => ['r' => 255, 'g' => 0, 'b' => 0],     // Vermelho para 3º ano
        ];

        // Divisória colorida
        $pdf->SetFillColor($cores[$ano]['r'], $cores[$ano]['g'], $cores[$ano]['b']);
        $pdf->Rect(40, $pdf->GetY(), 515, 10, 'F');
        $pdf->Ln(15);

        // Título do ano
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 20, mb_convert_encoding($ano_nome[$ano], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(10);

        // CABEÇALHO DA TABELA
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(150, 20, mb_convert_encoding("Nome do Aluno", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(100, 20, mb_convert_encoding("Turma", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(100, 20, mb_convert_encoding("Data", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(100, 20, mb_convert_encoding("Hora", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(150, 20, mb_convert_encoding("Responsável", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

        $tipo_relatorio = htmlspecialchars($_GET['tipo_relatorio']);
        $startDate = null;
        $endDate = null;

        $currentDate = new DateTime();
        $currentDateStr = $currentDate->format('Y-m-d H:i:s');

        if ($tipo_relatorio === 'dia_atual') {
            $startDate = $currentDate->format('Y-m-d 00:00:00');
            $endDate = $currentDate->format('Y-m-d 23:59:59');
        } elseif ($tipo_relatorio === 'ultimos_30_dias') {
            $startDate = $currentDate->modify('-30 days')->format('Y-m-d 00:00:00');
            $endDate = $currentDateStr;
        } elseif ($tipo_relatorio === 'ultimos_12_meses') {
            $startDate = $currentDate->modify('-12 months')->format('Y-m-d 00:00:00');
            $endDate = $currentDateStr;
        }

        // Buscar todas as turmas do ano especificado
        $turma_ids = [];
        if ($ano == 1) {
            $turma_ids = [1, 2, 3, 4]; // 1º ano a, b, c, d
        } elseif ($ano == 2) {
            $turma_ids = [5, 6, 7, 8]; // 2º ano a, b, c, d
        } elseif ($ano == 3) {
            $turma_ids = [9, 10, 11, 12]; // 3º ano a, b, c, d
        }

        // Construir placeholders nomeados para cada id_turma
        $placeholders = [];
        $params = [':start' => $startDate, ':end' => $endDate];
        foreach ($turma_ids as $index => $turma_id) {
            $param_name = ':turma' . $index;
            $placeholders[] = $param_name;
            $params[$param_name] = $turma_id;
        }

        $sql = "SELECT r.*, a.nome AS nome_aluno, t.turma 
                FROM registro_entrada r
                JOIN aluno a ON r.id_aluno = a.id_aluno 
                JOIN turma t ON a.id_turma = t.id_turma 
                WHERE t.id_turma IN (" . implode(',', $placeholders) . ")";
        if ($startDate && $endDate) {
            $sql .= " AND r.date_time BETWEEN :start AND :end";
        }
        $sql .= " ORDER BY t.turma, a.nome";

        $stmt = $this->connect->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // DADOS DA TABELA
        $pdf->SetFont('Arial', '', 12);
        if (empty($dados)) {
            $pdf->Cell(0, 20, mb_convert_encoding('Nenhum registro encontrado para o ano especificado.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        } else {
            foreach ($dados as $dado) {
                if ($pdf->GetY() + 20 > $pdf->GetPageHeight() - 40) {
                    $pdf->AddPage();
                    $pdf->SetFillColor($cores[$ano]['r'], $cores[$ano]['g'], $cores[$ano]['b']);
                    $pdf->Rect(40, $pdf->GetY(), 515, 10, 'F');
                    $pdf->Ln(15);
                    $pdf->SetFont('Arial', 'B', 16);
                    $pdf->Cell(0, 20, mb_convert_encoding($ano_nome[$ano], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
                    $pdf->Ln(10);
                    $pdf->SetFillColor(220, 220, 220);
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(150, 20, mb_convert_encoding("Nome do Aluno", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                    $pdf->Cell(100, 20, mb_convert_encoding("Turma", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                    $pdf->Cell(100, 20, mb_convert_encoding("Data", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                    $pdf->Cell(100, 20, mb_convert_encoding("Hora", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                    $pdf->Cell(150, 20, mb_convert_encoding("Responsável", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
                    $pdf->SetFont('Arial', '', 12);
                }

                $pdf->Cell(150, 20, mb_convert_encoding($dado['nome_aluno'] ?? 'Aluno não encontrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
                $pdf->Cell(100, 20, mb_convert_encoding($dado['turma'] ?? 'Turma não encontrada', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
                $data_hora = date('d/m/Y H:i', strtotime($dado['date_time']));
                $data = substr($data_hora, 0, 10);
                $hora = substr($data_hora, 11, 5);
                $pdf->Cell(100, 20, $data, 1, 0, 'C');
                $pdf->Cell(100, 20, $hora, 1, 0, 'C');
                $pdf->Cell(150, 20, mb_convert_encoding($dado['nome_responsavel'] ?? 'Não informado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
            }
        }

        $pdf->Ln(20);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 10, mb_convert_encoding('Relatório gerado em: ' . date('d/m/Y H:i:s'), 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');

        $pdf->Output('I', 'relatorio_entrada_ano_geral.pdf');
        $this->db->closeConnection();
    }
}

if (isset($_GET['ano']) && isset($_GET['tipo_relatorio']) && !empty($_GET['ano']) && !empty($_GET['tipo_relatorio'])) {
    new EntradaPDFAnoGeral();
} else {
    error_log("Redirecionando: ano ou tipo_relatorio ausentes ou vazios");
    header('location:../relatorioEntrada.php?error=missing_params');
    exit();
}
?>