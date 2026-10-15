<?php

function textoRelatorioPdf(string $texto): string
{
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
}

function aplicarCabecalhoRelatorio(FPDF $pdf, string $titulo, string $subtitulo = ''): void
{
    $escala = min($pdf->GetPageWidth(), $pdf->GetPageHeight()) / 210;
    $x = static function (float $posicao) use ($escala): float {
        return $posicao * $escala;
    };
    $y = static function (float $posicao) use ($escala): float {
        return $posicao * $escala;
    };

    $pdf->SetFillColor(238, 243, 236);
    $pdf->Rect(0, 0, $pdf->GetPageWidth(), 38 * $escala, 'F');
    $pdf->Image(__DIR__ . '/../../assets/img/cabeçalho_relatorios.jpg', $x(10), $y(5), 135 * $escala, 0, 'JPG');
    $pdf->Image(
        __DIR__ . '/../../assets/img/logo_area_dev.png',
        $pdf->GetPageWidth() - 40 * $escala,
        $y(8),
        30 * $escala,
        0,
        'PNG'
    );

    $pdf->SetTextColor(0, 122, 51);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetXY($x(10), $y(44));
    $pdf->Cell($pdf->GetPageWidth() - 20 * $escala, 7 * $escala, textoRelatorioPdf($titulo), 0, 1, 'L');

    if ($subtitulo !== '') {
        $pdf->SetTextColor(90, 90, 90);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY($x(10), $y(51));
        $pdf->Cell($pdf->GetPageWidth() - 20 * $escala, 4 * $escala, textoRelatorioPdf($subtitulo), 0, 1, 'L');
    }

    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetXY($x(10), $y($subtitulo !== '' ? 56 : 51));
    $agora = new DateTimeImmutable('now', new DateTimeZone('America/Fortaleza'));
    $pdf->Cell($pdf->GetPageWidth() - 20 * $escala, 4 * $escala, textoRelatorioPdf('Gerado em: ' . $agora->format('d/m/Y H:i')), 0, 1, 'L');
    $pdf->SetY($y($subtitulo !== '' ? 65 : 60));
}
