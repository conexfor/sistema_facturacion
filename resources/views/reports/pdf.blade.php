<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1e293b; margin: 0; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 8px; margin-bottom: 12px; }
        .empresa { font-size: 14px; font-weight: bold; color: #312e81; }
        .ruc { font-size: 10px; color: #64748b; }
        h1 { font-size: 15px; margin: 6px 0 2px; }
        .periodo { font-size: 10px; color: #64748b; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #4f46e5; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary { margin-top: 14px; width: 100%; }
        .summary td { border: none; padding: 3px 8px; }
        .summary .lbl { color: #64748b; }
        .summary .val { text-align: right; font-weight: bold; }
        .foot { margin-top: 18px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="empresa">{{ $empresa->razon_social ?? 'ERP Comercial' }}</div>
        @if ($empresa)
            <div class="ruc">RUC {{ $empresa->ruc }}</div>
        @endif
        <h1>{{ $report['title'] }}</h1>
        @if (!empty($report['periodo']))
            <p class="periodo">
                Del {{ \Illuminate\Support\Carbon::parse($report['periodo']['desde'])->format('d/m/Y') }}
                al {{ \Illuminate\Support\Carbon::parse($report['periodo']['hasta'])->format('d/m/Y') }}
            </p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($report['columns'] as $col)
                    <th class="{{ $col['align'] === 'right' ? 'right' : ($col['align'] === 'center' ? 'center' : '') }}">
                        {{ $col['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsFmt as $row)
                <tr>
                    @foreach ($row as $i => $cell)
                        <td class="{{ ($report['columns'][$i]['align'] ?? 'left') === 'right' ? 'right' : (($report['columns'][$i]['align'] ?? '') === 'center' ? 'center' : '') }}">
                            {{ $cell }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['columns']) }}" class="center">Sin datos para el período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        @foreach ($summaryFmt as $s)
            <tr>
                <td class="lbl">{{ $s['label'] }}</td>
                <td class="val">{{ $s['value'] }}</td>
            </tr>
        @endforeach
    </table>

    <div class="foot">
        Generado el {{ now()->format('d/m/Y H:i') }} · ERP Comercial
    </div>
</body>
</html>
