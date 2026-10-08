<?php

date_default_timezone_set('America/Sao_Paulo');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/Projeto-Integrador-mainAtual/logs/php_error.log');

require_once('../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/../../config/Database.php');
require_once('../../model/model_indexClass.php');

class TurmaReport
{
    private $db;
    private $connect;

    public function __construct()
    {
        error_log("Instanciando TurmaReport");
        $this->db = new Database();
        $this->connect = $this->db->connect();
    }

    public function generatePdf()
    {
        $pdf = new FPDF("P", "pt", "A4");
        $pdf->AddPage();

        // Validar parâmetros
        if (!isset($_GET['id_turma']) || !isset($_GET['tipoRelatorio']) || empty($_GET['id_turma']) || empty($_GET['tipoRelatorio'])) {
            error_log("Parâmetros id_turma ou tipoRelatorio ausentes ou vazios");
            header('location:../relatorioSaida_Estagio.php?error=missing_params');
            exit();
        }

        $id_turma = filter_var($_GET['id_turma'], FILTER_VALIDATE_INT);
        $tipo_relatorio = htmlspecialchars($_GET['tipoRelatorio']);

        // Mapear id_turma para nome da turma
        $turma_nome = match ($id_turma) {
            9 => '3 ano a',
            10 => '3 ano b', 
            11 => '3 ano c', 
            12 => '3 ano d', 
            default => null
        };

        if ($turma_nome === null) {
            error_log("Erro: id_turma inválido ($id_turma)");
            header('location:../relatorioSaida_Estagio.php?error=invalid_turma');
            exit();
        }

        // Título
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(0, 30, mb_convert_encoding('Relatório de Saída - Estágio STGM', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // Subtítulo com a turma
        $pdf->SetFont('Arial', 'I', 14);
        $pdf->Cell(0, 20, mb_convert_encoding("Turma: $turma_nome", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(10);

        // Determinar o intervalo de datas
        $startDate = null;
        $endDate = null;
        $currentDate = new DateTime();
        $currentDateStr = $currentDate->format('Y-m-d H:i:s');

        switch ($tipo_relatorio) {
            case 'dia_atual':
                $startDate = $currentDate->format('Y-m-d 00:00:00');
                $endDate = $currentDate->format('Y-m-d 23:59:59');
                break;
            case 'ultimos_30_dias':
                $startDate = $currentDate->modify('-30 days')->format('Y-m-d 00:00:00');
                $endDate = $currentDateStr;
                break;
            case 'ultimos_12_meses':
                $startDate = $currentDate->modify('-12 months')->format('Y-m-d 00:00:00');
                $endDate = $currentDateStr;
                break;
            default:
                error_log("Tipo de relatório inválido: $tipo_relatorio");
                header('location:../relatorioSaida_Estagio.php?error=invalid_report_type');
                exit();
        }

        // Consulta SQL
        $sql = "
            SELECT se.*, a.nome AS nome_aluno, t.turma AS nome_turma, t.id_turma
            FROM saida_estagio se
            JOIN aluno a ON se.id_aluno = a.id_aluno
            JOIN turma t ON a.id_turma = t.id_turma
            WHERE t.id_turma = :id_turma
        ";
        if ($startDate && $endDate) {
            $sql .= " AND se.dae BETWEEN :start AND :end";
        }
        $sql .= " ORDER BY a.nome, se.dae";

        $stmt = $this->connect->prepare($sql);
        $stmt->bindParam(':id_turma', $id_turma, PDO::PARAM_INT);
        if ($startDate && $endDate) {
            $stmt->bindParam(':start', $startDate);
            $stmt->bindParam(':end', $endDate);
        }
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Consulta SQL executada para id_turma: $id_turma");

        // Definir cores para a turma
        $cores = [
            '3 ano a' => ['r' => 255, 'g' => 0, 'b' => 0],      // Vermelho - Enfermagem
            '3 ano b' => ['r' => 0, 'g' => 0, 'b' => 139],     // Azul escuro - Informática
            '3 ano c' => ['r' => 135, 'g' => 206, 'b' => 250], // Azul claro - Administração
            '3 ano d' => ['r' => 128, 'g' => 0, 'b' => 128]    // Roxo - Edificações
        ];

        // Divisória colorida
        $pdf->SetFillColor($cores[$turma_nome]['r'], $cores[$turma_nome]['g'], $cores[$turma_nome]['b']);
        $pdf->Rect(40, $pdf->GetY(), 515, 10, 'F');
        $pdf->Ln(15);

        // Cabeçalho da tabela
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(300, 20, mb_convert_encoding("Nome do Aluno", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(100, 20, mb_convert_encoding("Data", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(100, 20, mb_convert_encoding("Hora", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

        // Linhas da tabela
        $pdf->SetFont('Arial', '', 12);
        foreach ($dados as $dado) {
            if ($pdf->GetY() + 20 > $pdf->GetPageHeight() - 40) {
                $pdf->AddPage();
                $pdf->SetFillColor($cores[$turma_nome]['r'], $cores[$turma_nome]['g'], $cores[$turma_nome]['b']);
                $pdf->Rect(40, $pdf->GetY(), 515, 10, 'F');
                $pdf->Ln(15);
                $pdf->SetFillColor(220, 220, 220);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(300, 20, mb_convert_encoding("Nome do Aluno", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(100, 20, mb_convert_encoding("Data", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(100, 20, mb_convert_encoding("Hora", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
            }

            $pdf->Cell(300, 20, mb_convert_encoding($dado['nome_aluno'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
            $data_hora = date('d/m/Y H:i', strtotime($dado['dae']));
            $data = substr($data_hora, 0, 10);
            $hora = substr($data_hora, 11, 5);
            $pdf->Cell(100, 20, $data, 1, 0, 'C');
            $pdf->Cell(100, 20, $hora, 1, 1, 'C');
        }

        if (empty($dados)) {
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 20, mb_convert_encoding('Nenhum registro encontrado para o período especificado.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        }

        // Rodapé
        $pdf->Ln(20);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 10, mb_convert_encoding('Relatório gerado em: ' . date('d/m/Y H:i:s'), 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');

        $pdf->Output('I', 'relatorio_saida_estagio_' . str_replace(' ', '_', $turma_nome) . '.pdf');
        $this->db->closeConnection();
    }
}

if (isset($_GET['id_turma']) && isset($_GET['tipoRelatorio']) && !empty($_GET['id_turma']) && !empty($_GET['tipoRelatorio'])) {
    error_log("Recebido em por_turma.php: id_turma=" . $_GET['id_turma'] . ", tipoRelatorio=" . $_GET['tipoRelatorio']);
    $report = new TurmaReport();
    $report->generatePdf();
} else {
    error_log("Redirecionando: id_turma ou tipoRelatorio ausentes ou vazios");
    header('location:../relatorioSaida_Estagio.php?error=missing_params');
    exit();
}
?>