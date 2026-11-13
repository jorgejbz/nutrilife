<?php

namespace App\Http\Controllers;

use App\Services\AdvancedReport;
use App\Support\PdfExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes avanzados (feature:advanced_reports → Pro, Consultorio y prueba).
 * Todo se limita al nutriólogo autenticado (ver App\Services\AdvancedReport).
 * La descarga en PDF además requiere feature:pdf_export (ver routes/web.php).
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $report = AdvancedReport::fromRequest(Auth::user(), $request);

        return view('reports.index', [
            'report' => $report->all(),
            'builder' => $report,
            'presets' => AdvancedReport::PRESETS,
        ]);
    }

    public function pdf(Request $request)
    {
        $report = AdvancedReport::fromRequest(Auth::user(), $request);

        return PdfExport::download('pdf.report', [
            'report' => $report->all(),
            'docTitle' => 'Reporte avanzado',
            'docSubtitle' => $report->periodLabel(),
            'nutritionist' => Auth::user(),
        ], 'reporte-nutrilife-'.$report->from()->format('Ymd').'-'.$report->to()->format('Ymd').'.pdf');
    }

    public function csv(Request $request): StreamedResponse
    {
        $report = AdvancedReport::fromRequest(Auth::user(), $request);
        $rows = $report->csvRows($report->all());
        $name = 'reporte-nutrilife-'.$report->from()->format('Ymd').'-'.$report->to()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel abre los acentos bien
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
