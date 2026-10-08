<?php
define('FPDF_FONTPATH', __DIR__ . '/../../assets/lib/fpdf/font/');
require_once(__DIR__ . '/../../config/Database.php');
require_once(__DIR__ . '/../../assets/lib/fpdf/fpdf.php');
require_once(__DIR__ . '/../../assets/lib/phpqrcode/qrlib.php');

class qrCode1 extends connect
{
    public function __construct()
    {
        parent::__construct();
        $this->pdf();
    }

    public function pdf()
    {
        // Verificar se a extensão GD está ativada
        if (!function_exists('imagecreate')) {
            die("Erro: A extensão GD do PHP não está ativada. Habilite-a no php.ini.");
        }

        // Validar entrada
        if (!isset($_POST['turma']) || empty($_POST['turma'])) {
            die("Erro: Turma não especificada.");
        }

        $pdf = new FPDF("L", "cm", "A4");
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $logo_frente = __DIR__ . '/../../assets/img/logo_frente_cracha.png';
        $logo_verso  = __DIR__ . '/../../assets/img/logo_verso_cracha.png';
        

        $curso = $_POST["turma"];
        
        
        
        

        // Consultar alunos da turma
        $queryStr = "
        SELECT 
            id_aluno,
            nome,
            matricula
        FROM aluno
        WHERE id_turma = :turma
        AND ano_corrente = YEAR(CURDATE())
        ";



        $query = $this->connect->prepare($queryStr);
        $query->bindValue(":turma", $curso, PDO::PARAM_STR);
        $query->execute();
        $id_aluno = $query->fetchAll(PDO::FETCH_ASSOC);

        // Verificar se há alunos
        if (empty($id_aluno)) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 1, utf8_decode('Nenhum aluno encontrado para a turma especificada.'), 0, 1, 'C');
            $pdf->Output('I', 'crachas_turma_' . $curso . '.pdf');
            return;
        }

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

        // Definir diretório dinâmico com base na turma
        $turma_dir = '';
        switch ($curso) {
            case '9':
                $turma_dir = 'img3A';
                break;
            case '10':
                $turma_dir = 'img3B';
                break;
            case '11':
                $turma_dir = 'img3C';
                break;
            case '12':
                $turma_dir = 'img3D';
                break;
            case '13':
                $turma_dir = 'areaDev';
                break;
            case '14':
                $turma_dir = 'sti';
                break;
            case '15':
                $turma_dir = 'reimpressao';
                break;
        }

        // Criar diretório se não existir
        $qr_dir = __DIR__ . '/../../assets/img/imgAlunos/' . $turma_dir . '/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0777, true);
        }

foreach ($id_aluno as $id) {

    // Verificar quebra de página
    if ($current_y + $cracha_height + 1 > $pdf->GetPageHeight() - 1) {
        $pdf->AddPage();
        $current_x = $start_x;
        $current_y = $start_y;
    }

    // =========================
    // CARD 1 - QR CODE
    // =========================

    $arquivo_qrcode = $qr_dir . str_replace(' ', '_', $id['nome']) . '.png';

    $url = "https://salaberga.com/salaberga/portalsalaberga/app/subsystems/entradasaida/index.php?id_aluno=" . urlencode($id['nome']);

    QRcode::png($url, $arquivo_qrcode, QR_ECLEVEL_M, 4);

    

    
     // IMAGEM DE PLANO DE FUNDO DO VERSO DOS CRACHÁS POR TURMA (LADO DO QR CODE) 
        switch ($curso) {
            case '9':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '10':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '11':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '12':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '13':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo_areadev.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '14':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo_sti.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '15':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
        }
        
        
        

    
    
    

    // QR Code centralizado
// =========================
// LOGO VERSO ACIMA DO QR
// =========================

if (file_exists($logo_verso)) {

    $logo_w = 3.2;
    $logo_h = 0;

    $logo_x = $current_x + ($cracha_width - $logo_w) / 2;
    $logo_y = $current_y + 0.45;

    $pdf->Image(
        $logo_verso,
        $logo_x,
        $logo_y,
        $logo_w,
        $logo_h
    );
}

// =========================
// QR CODE
// =========================

$qr_x = $current_x + ($cracha_width - $qr_size) / 2;
$qr_y = $current_y + 2.5;

$pdf->Image(
    $arquivo_qrcode,
    $qr_x,
    $qr_y,
    $qr_size,
    $qr_size
);




    
    $pdf->Image($arquivo_qrcode, $qr_x, $qr_y, $qr_size, $qr_size);
    
    
    
    // Matrícula abaixo do QRCode
    $pdf->SetFont('Arial','B',6);
    $pdf->SetXY(
        $current_x + 0.1,
        $qr_y + $qr_size + (0.20)
    );
    
    $pdf->Cell($cracha_width - 0.1,0.25,utf8_decode('Matrícula.: '.$id['matricula']),0,0,'C');



    // Nome
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY($current_x, $current_y + $cracha_height + 0.2);
    $pdf->Cell($cracha_width, 0.5, utf8_decode($id['nome']), 0, 1, 'C');

    // Remove QR temporário
    if (file_exists($arquivo_qrcode)) {
        unlink($arquivo_qrcode);
    }

    // Próxima posição (lado do QR)
    $current_x += $cracha_width + $space_between;
    
    
    

















    

// =========================
// CARD 2 - FOTO
// =========================

// =========================
// LOCALIZAR FOTO DO ALUNO
// =========================

$nome_arquivo = strtoupper(trim($id['nome']));
$matricula = trim($id['matricula']);

$diretorio_fotos = __DIR__
    . '/../../assets/img/imgAlunos/'
    . $turma_dir . '/';

$foto = '';

// Aceitar vários padrões do hífen
$padroes = [
    $matricula . ' - ' . $nome_arquivo,
    $matricula . '- '  . $nome_arquivo,
    $matricula . ' -'  . $nome_arquivo,
    $matricula . '-'   . $nome_arquivo
];

// Aceitar png, jpg e jpeg
$extensoes = [
    'png','PNG',
    'jpg','JPG',
    'jpeg','JPEG'
];

// Procurar arquivo
foreach ($padroes as $base) {

    foreach ($extensoes as $ext) {

        $arquivo = $diretorio_fotos
                  . $base
                  . '.'
                  . $ext;

        if (file_exists($arquivo)) {
            $foto = $arquivo;
            break 2;
        }
    }
}



        

     // IMAGEM DE PLANO DE FUNDO DA FRENTE DOS CRACHÁS POR TURMA (LADO DA FOTO DO(A) ALUNO(A))
        switch ($curso) {
            case '9':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '10':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '11':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '12':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '13':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo_areadev.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '14':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo_sti.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
            case '15':
                // Fundo
                $pdf->Image(
                    __DIR__ . '/../../assets/img/plano_de_fundo.jpg',
                    $current_x,
                    $current_y,
                    $cracha_width,
                    $cracha_height
                );
                break;
        }



// =========================
// LOGO FRENTE ACIMA DA FOTO
// =========================

if (file_exists($logo_frente)) {

    $logo_w = 4.0;
    $logo_h = 0;

    $logo_x = $current_x + ($cracha_width - $logo_w) / 2;
    $logo_y = $current_y + 0.40;

    $pdf->Image(
        $logo_frente,
        $logo_x,
        $logo_y,
        $logo_w,
        $logo_h
    );
}

// =========================
// FOTO
// =========================

$foto_w = 2.30;
$foto_h = 3.06;

// manter centralizada
$foto_x = $current_x + ($cracha_width - $foto_w) / 2;
$foto_y = $current_y + 2.0;



// Imprimir foto
if (!empty($foto) && file_exists($foto)) {

    $pdf->Image(
        $foto,
        $foto_x,
        $foto_y,
        $foto_w,
        $foto_h
    );

} else {

    $pdf->SetFont('Arial','B',8);
    $pdf->SetXY($current_x, $foto_y + 1.8);
    $pdf->Cell($cracha_width,0.5,'SEM FOTO',0,1,'C');
}

// =========================
// DADOS ABAIXO DA FOTO
// SEQUÊNCIA:
// FOTO -> NOME -> TARJA DO CURSO
// =========================

// =========================
// DEFINIR CURSO + COR DA TARJA
// =========================

switch ($curso) {

    case '9': // Enfermagem
        $curso_nome = 'Enfermagem';
        $pdf->SetFillColor(213,46,63);
        break;

    case '10': // Informática
        $curso_nome = 'Informática';
        $pdf->SetFillColor(54,82,209);
        break;

    case '11': // Meio Ambiente
        $curso_nome = 'Meio Ambiente';
        $pdf->SetFillColor(124,194,160);
        break;

    case '12': // Edificações
        $curso_nome = 'Edificações';
        $pdf->SetFillColor(107,116,124);
        break;

    case '13': // areaDev
        $curso_nome = 'DESENVOLVEDOR  |  AREA DEV';
        $pdf->SetFillColor(00,00,00);
        break;

    case '14': // sti
        $curso_nome = 'Suporte de TI';
        $pdf->SetFillColor(54,82,209);
        break;

    case '15': // reimpressao
        $curso_nome = 'Enfermagem';
        $pdf->SetFillColor(213,46,63);
        break;

    default:
        $curso_nome = 'Curso';
        $pdf->SetFillColor(180,180,180);
}

// =========================
// NOME (ABAIXO DA FOTO)
// =========================

$nome_y = $foto_y + $foto_h + 0.35;

$pdf->SetFont('Arial','B',8);
$pdf->SetTextColor(0,0,0);

// definir área do nome
$pdf->SetXY(
    $current_x + 0.08,
    $nome_y
);

// nome quebra automaticamente
$pdf->MultiCell(
    $cracha_width - 0.16,
    0.28,
    utf8_decode(mb_convert_case(mb_strtolower($id['nome']), MB_CASE_TITLE, 'UTF-8')),
    0,
    'C'
);

// =========================
// TARJA COLORIDA DO CURSO
// (sempre abaixo do nome)
// =========================

// pega posição REAL após o MultiCell
$tarja_y = $pdf->GetY() + 0.05;
$tarja_h = 0.45;

// desenhar tarja
$pdf->Rect(
    $current_x,
    $tarja_y,
    $cracha_width,
    $tarja_h,
    'F'
);

// =========================
// CURSO DENTRO DA TARJA
// =========================

$pdf->SetFont('Arial','B',7);
$pdf->SetTextColor(255,255,255);

$pdf->SetXY(
    $current_x,
    $tarja_y + 0.08
);

$pdf->Cell(
    $cracha_width,
    0.28,
    utf8_decode($curso_nome),
    0,
    0,
    'C'
);

// =========================
// ESTAGIÁRIO(A)
// ABAIXO DA TARJA
// =========================

$pdf->SetFont('Arial','B',8.5);
$pdf->SetTextColor(0,0,0);

$pdf->SetXY(
    $current_x,
    $tarja_y + $tarja_h + 0.18
);

$pdf->Cell(
    $cracha_width,
    0.30,
    utf8_decode('Estagiário(a)'),
    0,
    0,
    'C'
);

// voltar cor padrão
$pdf->SetTextColor(0,0,0);



















// =========================
// ESTAGIÁRIO(A)
// =========================



// Voltar cor padrão
$pdf->SetTextColor(0,0,0);
    
    // Próximo card
    $current_x += $cracha_width + $space_between;
    
    // Quebra de linha
    if ($current_x + $cracha_width > $pdf->GetPageWidth() - 2) {
        $current_x = $start_x;
        $current_y += $cracha_height + 1;
        
        
    }
    
}

        // Finalizar o PDF
        $pdf->Output('I', 'crachas_turma_' . $curso . '.pdf');
    }
}

$qrcode = new qrCode1();
