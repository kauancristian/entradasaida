<?php
date_default_timezone_set('America/Sao_Paulo');

// Configurar logs e desativar exibição de erros na tela
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/../../config/Database.php');
require_once('../../model/model_indexClass.php'); 

class qrCode1
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
        $pdf = new FPDF("P", "pt", "A4");
        $pdf->AddPage();

        // TÍTULO
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(0, 30, mb_convert_encoding('Relatório de Saída - Estágio STGM', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // SUBTÍTULO
        $pdf->SetFont('Arial', 'I', 14);
        $pdf->Cell(0, 20, mb_convert_encoding($_GET['tipo_relatorio'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(10);

     
        
        $id_aluno = filter_var($_GET['id_aluno'], FILTER_VALIDATE_INT);
        if (!$id_aluno) {
            error_log("Erro: id_aluno inválido ({$_GET['id_aluno']})");
            header('Location: ../relatorioSaida_Estagio.php?error=invalid_aluno');
            exit();
        }

        $query_turma = "SELECT t.turma, t.id_turma FROM aluno a JOIN turma t ON a.id_turma = t.id_turma WHERE a.id_aluno = :id_aluno";
        $stmt_turma = $this->connect->prepare($query_turma);
        $stmt_turma->bindParam(':id_aluno', $id_aluno, PDO::PARAM_INT);
        $stmt_turma->execute();
        $turma = $stmt_turma->fetch(PDO::FETCH_ASSOC);

        // Mapear turmas para corresponder ao array $cores
        $turma_map = [
            '3 ano a' => '3 ano a',
            '3 ano b' => '3 ano b',
            '3 ano c' => '3 ano c',
            '3 ano d' => '3 ano d'
        ];

        $turma_nome = $turma && isset($turma['turma']) ? strtolower($turma['turma']) : 'desconhecida';
        $turma_nome = isset($turma_map[$turma_nome]) ? $turma_map[$turma_nome] : 'desconhecida';
        $id_turma = $turma ? $turma['id_turma'] : 0;

        error_log("Turma do aluno id $id_aluno: turma_nome=$turma_nome, id_turma=$id_turma");

        
        $cores = [
            '3 ano a' => ['r' => 255, 'g' => 0, 'b' => 0],      // Vermelho - Enfermagem
            '3 ano b' => ['r' => 0, 'g' => 0, 'b' => 139],     // Azul escuro - Informática
            '3 ano c' => ['r' => 135, 'g' => 206, 'b' => 250], // Azul claro - Administração
            '3 ano d' => ['r' => 128, 'g' => 0, 'b' => 128],   // Roxo - Edificações
            'desconhecida' => ['r' => 100, 'g' => 100, 'b' => 100] // Cinza padrão
        ];

        // Divisória colorida
        if (!isset($cores[$turma_nome])) {
            error_log("Erro: turma_nome inválido ($turma_nome) não encontrado em \$cores");
            $turma_nome = 'desconhecida';
        }
        $pdf->SetFillColor($cores[$turma_nome]['r'], $cores[$turma_nome]['g'], $cores[$turma_nome]['b']);
        $pdf->Rect(40, $pdf->GetY(), 515, 10, 'F');
        $pdf->Ln(15);

        // CABEÇALHO DA TABELA
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(300, 20, mb_convert_encoding("Nome do Aluno", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(100, 20, mb_convert_encoding("Data", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(100, 20, mb_convert_encoding("Hora", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);


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

       
        $saidaEstagioModel = new SaidaEstagioModel();
        $dados = $saidaEstagioModel->getSaidasByDateRange($id_aluno, $startDate, $endDate);

        // DADOS DA TABELA
        $pdf->SetFont('Arial', '', 12);
        if (empty($dados)) {
            $pdf->Cell(0, 20, mb_convert_encoding('Nenhum registro encontrado para o aluno especificado.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        } else {
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
                    $pdf->SetFont('Arial', '', 12);
                }

                $pdf->Cell(300, 20, mb_convert_encoding($dado['nome_aluno'] ?? 'Aluno não encontrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
                $data_hora = date('d/m/Y H:i', strtotime($dado['dae']));
                $data = substr($data_hora, 0, 10);
                $hora = substr($data_hora, 11, 5);
                $pdf->Cell(100, 20, $data, 1, 0, 'C');
                $pdf->Cell(100, 20, $hora, 1, 1, 'C');
            }
        }

        $pdf->Ln(20);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 10, mb_convert_encoding('Relatório gerado em: ' . date('d/m/Y H:i:s'), 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');

        $pdf->Output('I', 'relatorio_saida_estagio.pdf');
        $this->db->closeConnection();
    }
}

if (isset($_GET['id_aluno']) && isset($_GET['tipo_relatorio']) && !empty($_GET['id_aluno']) && !empty($_GET['tipo_relatorio'])) {
    new qrCode1;
} else {
    error_log("Redirecionando: id_aluno ou tipo_relatorio ausentes ou vazios");
    header('location:../relatorioSaida_Estagio.php?error=missing_params');
    exit();
}
?>