<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Genera y descarga los PDF de NutriLife (plan alimenticio, progreso y
 * reporte) con las mismas opciones:
 *  - Tamaño carta.
 *  - Subconjunto de la fuente: solo se incrusta lo que se usa (sin esto
 *    cada PDF pesaba ~900 KB).
 *  - "Página X de Y" en el pie: dompdf no calcula el total de páginas con
 *    CSS, así que se escribe sobre el lienzo ya renderizado.
 */
class PdfExport
{
    public static function download(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('letter')
            ->setOption(['enable_font_subsetting' => true, 'default_font' => 'DejaVu Sans']);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text(
            $canvas->get_width() - 112,
            $canvas->get_height() - 36,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $font,
            6.5,
            [0.56, 0.58, 0.55],
        );

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.Str::finish($filename, '.pdf').'"',
        ]);
    }
}
