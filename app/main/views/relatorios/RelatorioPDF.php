<?php
define('FPDF_FONTPATH', __DIR__ . '/../assets/fpdf/font/');
require_once('../assets/fpdf/fpdf.php');
require_once('../config/Database.php');

class GerarPdf extends Database
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
        $this->pdf();
    }

    public function pdf()
    {
        $pdf = new FPDF("L", "cm", "A4");
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $id_aluno = $_POST["id_aluno"];
        $pdo = $this->db->connect();

        $queryStr = "SELECT id_saida, id_aluno from saida_estagio WHERE id_aluno = :id_aluno";
        $query = $pdo->prepare($queryStr);
        $query->bindValue(":id_aluno", $id_aluno);
        $query->execute();

        $id_aluno = $query->fetchAll(PDO::FETCH_ASSOC);


        // Configurações de layout
        $cracha_width = 5; // Largura do crachá (cm)
        $cracha_height = 8; // Altura do crachá (cm)
        $qr_size = 3.5; // Tamanho do QR code (cm)
        $space_between = 1; // Espaço entre crachás (cm)
        $max_per_line = 3; // Máximo de crachás por linha
        $start_x = 2; // Posição X inicial (cm)
        $start_y = 0.5; // Posição Y inicial (cm)
        $current_x = $start_x;
        $current_y = $start_y;

        foreach ($id_aluno as $id) {
            // Verificar se o crachá cabe na página atual
            if ($current_y + $cracha_height + 1 > $pdf->GetPageHeight() - 1) {
                $pdf->AddPage();
                $current_x = $start_x;
                $current_y = $start_y;
            }


            // Colocar a imagem de fundo (crachá)
            $pdf->Image('../assets/img/crach.jpg', $current_x, $current_y, $cracha_width, $cracha_height);


            // Exibir o ID do aluno abaixo do crachá
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($current_x, $current_y + $cracha_height + 0.2); // 0.2 cm abaixo do crachá
            $pdf->Cell($cracha_width, 0.5, $id['nome'], 0, 1, 'C');


            // Atualizar a posição X para o próximo crachá
            $current_x += $cracha_width + $space_between;

            // Verificar se atingiu o limite da linha
            if ($current_x + $cracha_width > $pdf->GetPageWidth() - 2) {
                $current_x = $start_x;
                $current_y += $cracha_height + 1; // Espaço vertical para a próxima linha
            }
        }

        // Finalizar o PDF após processar todos os crachás
        $pdf->Output('I', 'relatorio_acervo.pdf');
    }
}

$qrcode = new qrCode1();