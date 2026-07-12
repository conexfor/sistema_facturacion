<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Reports/Index', [
            'reportes' => collect(ReportService::REPORTES)->map(fn ($r, $key) => [
                'key' => $key,
                'title' => $r['title'],
                'description' => $r['description'],
                'icon' => $r['icon'],
            ])->values(),
        ]);
    }

    public function show(string $key, Request $request): Response
    {
        abort_unless($this->reports->exists($key), 404);

        $report = $this->reports->build($key, $request->only(['desde', 'hasta']), $request->user()->company_id);

        return Inertia::render('Reports/View', [
            'report' => $report,
            'filters' => $request->only(['desde', 'hasta']),
            'moneda' => $request->user()->company->moneda ?? 'PEN',
        ]);
    }

    public function export(string $key, Request $request): BinaryFileResponse|HttpResponse
    {
        abort_unless($this->reports->exists($key), 404);

        $formato = $request->input('formato', 'excel');
        $report = $this->reports->build($key, $request->only(['desde', 'hasta']), $request->user()->company_id);

        $headings = collect($report['columns'])->pluck('label')->all();
        $fecha = now()->format('Ymd_His');
        $filename = "reporte_{$key}_{$fecha}";

        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf', [
                'report' => $report,
                'empresa' => $request->user()->company,
                'rowsFmt' => array_map(
                    fn ($row) => $this->formatRow($row, $report['columns']),
                    $report['rows'],
                ),
                'summaryFmt' => array_map(
                    fn ($s) => ['label' => $s['label'], 'value' => $this->formatValue($s['value'], $s['format'])],
                    $report['summary'],
                ),
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(
            new ArrayExport($headings, $report['rows'], $report['title']),
            $filename . '.xlsx',
        );
    }

    private function formatRow(array $row, array $columns): array
    {
        $out = [];
        foreach ($row as $i => $value) {
            $out[] = $this->formatValue($value, $columns[$i]['format'] ?? 'text');
        }

        return $out;
    }

    private function formatValue(mixed $value, string $format): string
    {
        return match ($format) {
            'currency' => 'S/ ' . number_format((float) $value, 2),
            'number' => number_format((float) $value, 2),
            'int' => number_format((float) $value, 0),
            default => (string) $value,
        };
    }
}
