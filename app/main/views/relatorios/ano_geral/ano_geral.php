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

class qrCode1
{
    private $db;
    function __construct()
    {
        $this->db = new Database();
        $this->pdf();
    }

    public function pdf()
    {
        $connect = $this->db->connect();
        $pdf = new FPDF("P", "pt", "A4");
        $pdf->AddPage();

        // Título
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(0, 30, mb_convert_encoding('Relatório de Saída - Estágio STGM (3º Ano)', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // Subtítulo
        $pdf->SetFont('Arial', 'I', 14);
        $pdf->Cell(0, 20, mb_convert_encoding($_GET['tipoRelatorio'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(10);

        // Determinar o intervalo de datas com base no tipoRelatorio
        $tipo_relatorio = $_GET['tipoRelatorio'];
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

        $sql = "
            SELECT se.*, a.nome AS nome_aluno, t.turma AS nome_turma, t.id_turma
            FROM saida_estagio se
            JOIN aluno a ON se.id_aluno = a.id_aluno
            JOIN turma t ON a.id_turma = t.id_turma
            WHERE t.id_turma IN (1,2,3,4,5,6,7,8,9, 10, 11, 12)
        ";
        if ($startDate && $endDate) {
            $sql .= " AND se.dae BETWEEN :start AND :end";
        }
        $sql .= " ORDER BY t.id_turma, a.nome, se.dae"; // Ordena por id_turma, nome e data

        $stmt = $connect->prepare($sql);
        if ($startDate && $endDate) {
            $stmt->bindParam(':start', $startDate);
            $stmt->bindParam(':end', $endDate);
        }
        $stmt->execute();
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $registros_por_turma = [];
        foreach ($dados as $dado) {
           
            $id_turma = $dado['id_turma'];
            $turma = match ($id_turma) {
                9 => '3 ano a',  
                10 => '3 ano b', 
                11 => '3 ano c', 
                12 => '3 ano d', 
                default => 'Turma desconhecida'
            };
            if (!isset($registros_por_turma[$turma])) {
                $registros_por_turma[$turma] = [];
            }
            $registros_por_turma[$turma][] = $dado;
        }

       
        $cores = [
            '3 ano a' => ['r' => 255, 'g' => 0, 'b' => 0],      // Vermelho - Enfermagem
            '3 ano b' => ['r' => 0, 'g' => 0, 'b' => 139],     // Azul escuro - Informática
            '3 ano c' => ['r' => 135, 'g' => 206, 'b' => 250], // Azul claro - Administração
            '3 ano d' => ['r' => 128, 'g' => 0, 'b' => 128]    // Roxo - Edificações
        ];

       
        $current_turma = null;
        foreach ($registros_por_turma as $turma => $registros) {

          
            if ($current_turma !== $turma) {
                $current_turma = $turma;


                if ($pdf->GetY() + 50 > $pdf->GetPageHeight() - 40) {
                    $pdf->AddPage();
                }

                // Divisória colorida
                if (isset($cores[$turma])) {
                    $pdf->SetFillColor($cores[$turma]['r'], $cores[$turma]['g'], $cores[$turma]['b']);
                } else {
                    $pdf->SetFillColor(200, 200, 200); 
                }
                $pdf->Rect(40, $pdf->GetY(), 515, 10, 'F'); 
                $pdf->Ln(15);

                // Título da turma
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->SetTextColor(40, 40, 40);
                $pdf->Cell(0, 20, mb_convert_encoding("Turma: $turma", 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
                $pdf->Ln(5);

                // Cabeçalho da tabela
                $pdf->SetFillColor(220, 220, 220);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(40, 40, 40);
                $pdf->Cell(300, 20, mb_convert_encoding("Nome do Aluno", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(100, 20, mb_convert_encoding("Data", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                $pdf->Cell(100, 20, mb_convert_encoding("Hora", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
            }


            $pdf->SetFont('Arial', '', 12);
            foreach ($registros as $dado) {

                if ($pdf->GetY() + 20 > $pdf->GetPageHeight() - 40) {
                    $pdf->AddPage();

                    if (isset($cores[$turma])) {
                        $pdf->SetFillColor($cores[$turma]['r'], $cores[$turma]['g'], $cores[$turma]['b']);
                    } else {
                        $pdf->SetFillColor(200, 200, 200);
                    }
                    $pdf->Rect(40, $pdf->GetY(), 515, 10, 'F');
                    $pdf->Ln(15);
                    $pdf->SetFont('Arial', 'B', 16);
                    $pdf->Cell(0, 20, mb_convert_encoding("Turma: $turma", 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
                    $pdf->Ln(5);
                    $pdf->SetFillColor(220, 220, 220);
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(300, 20, mb_convert_encoding("Nome do Aluno", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                    $pdf->Cell(100, 20, mb_convert_encoding("Data", 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
                    $pdf->Cell(100, 20, mb_convert_encoding("Hora", 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
                }


                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(300, 20, mb_convert_encoding($dado['nome_aluno'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
                $data_hora = date('d/m/Y H:i', strtotime($dado['dae']));
                $data = substr($data_hora, 0, 10); // Extrai a data (d/m/Y)
                $hora = substr($data_hora, 11, 5); // Extrai a hora (H:i)
                $pdf->Cell(100, 20, $data, 1, 0, 'C');
                $pdf->Cell(100, 20, $hora, 1, 1, 'C');
            }
        }

        // Caso não haja registros
        if (empty($dados)) {
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 20, mb_convert_encoding('Nenhum registro encontrado para o período especificado.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        }

        // Rodapé com data de geração
        $pdf->Ln(20);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 10, mb_convert_encoding('Relatório gerado em: ' . date('d/m/Y H:i:s'), 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');

        $pdf->Output('I', 'relatorio_saida_estagio_3_ano.pdf');
    }
}

if (isset($_GET['tipoRelatorio']) && !empty($_GET['tipoRelatorio'])) {
    new qrCode1;
} else {
    header('location:../relatorioSaida_Estagio.php');
    exit();
}