<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @php
        $opLabels = [
            'fecha' => 'Fecha de emisión', 'moneda' => 'Moneda', 'formaPago' => 'Forma de pago',
            'vendedor' => 'Vendedor', 'documentoAfectado' => 'Doc. que modifica', 'motivo' => 'Motivo',
            'comprobante' => 'Comprobante', 'estadoPago' => 'Estado de pago',
        ];
        $totalCobrar = $total + ($percepcion ?? 0);
    @endphp
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10.5px; color: #1e293b; margin: 0; }
        .wrap { padding: 22px 26px; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .empresa .rs { font-size: 15px; font-weight: bold; color: #0f172a; }
        .empresa .l { color: #475569; margin-top: 2px; }
        .doc-box { border: 1.5px solid #334155; border-radius: 6px; text-align: center; padding: 10px 8px; }
        .doc-box .ruc { font-weight: bold; font-size: 12px; }
        .doc-box .tipo { font-weight: bold; font-size: 12px; margin-top: 4px; text-transform: uppercase; }
        .doc-box .num { font-size: 13px; margin-top: 4px; letter-spacing: 1px; }
        .cols { margin-top: 14px; }
        .card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; }
        .card .h { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-bottom: 4px; font-weight: bold; }
        .card .row { margin: 1px 0; }
        .card .k { color: #64748b; }
        .card .v { color: #0f172a; font-weight: bold; }
        table.items { margin-top: 12px; }
        table.items th { background: #f1f5f9; color: #334155; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; }
        table.items td { padding: 5px 8px; border-bottom: 1px solid #eef2f7; }
        table.items .r { text-align: right; }
        table.items .c { text-align: center; }
        .totals td { padding: 3px 8px; }
        .totals .k { text-align: right; color: #475569; }
        .totals .v { text-align: right; width: 120px; font-weight: bold; }
        .totals .grand { font-size: 13px; color: #0f172a; border-top: 1.5px solid #334155; }
        .totals .perc { color: #b45309; }
        .letras { margin-top: 8px; padding: 6px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-style: italic; }
        .footer { margin-top: 14px; }
        .footer td { vertical-align: top; }
        .firma { font-size: 9px; color: #475569; }
        .firma .lbl { color: #64748b; }
        .hash { word-break: break-all; font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; }
        .anulado { color: #dc2626; border: 2px solid #dc2626; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
        .leyenda { margin-top: 10px; font-size: 8.5px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <table class="header">
        <tr>
            <td class="empresa" style="width: 60%;">
                @if(!empty($emisor['logo']))
                    <img src="{{ $emisor['logo'] }}" alt="Logo" style="max-height: 56px; max-width: 200px; margin-bottom: 6px;">
                @endif
                <div class="rs">{{ $emisor['razon_social'] }}</div>
                @if(!empty($emisor['nombre_comercial']))<div class="l">{{ $emisor['nombre_comercial'] }}</div>@endif
                <div class="l">{{ $emisor['direccion'] ?: 'Lima - Perú' }}</div>
                @if(!empty($emisor['ubicacion']))<div class="l">{{ $emisor['ubicacion'] }}</div>@endif
                @if(!empty($emisor['telefono']) || !empty($emisor['email']))
                    <div class="l">{{ $emisor['telefono'] }}{{ !empty($emisor['telefono']) && !empty($emisor['email']) ? ' · ' : '' }}{{ $emisor['email'] }}</div>
                @endif
            </td>
            <td style="width: 40%; padding-left: 12px;">
                <div class="doc-box">
                    <div class="ruc">R.U.C. {{ $emisor['ruc'] }}</div>
                    <div class="tipo">{{ $titulo }}</div>
                    <div class="num">{{ $numero }}</div>
                </div>
                @if($anulado)<div style="text-align:center; margin-top:6px;"><span class="anulado">ANULADO</span></div>@endif
            </td>
        </tr>
    </table>

    <table class="cols">
        <tr>
            <td style="width: 50%; padding-right: 6px;">
                <div class="card">
                    <div class="h">{{ $receptorLabel }}</div>
                    <div class="row"><span class="v">{{ $receptor['nombre'] }}</span></div>
                    <div class="row"><span class="k">{{ $receptor['tipoLabel'] }}:</span> <span class="v">{{ $receptor['doc'] ?: '—' }}</span></div>
                    <div class="row"><span class="k">Dirección:</span> {{ $receptor['direccion'] ?: '—' }}</div>
                </div>
            </td>
            <td style="width: 50%; padding-left: 6px;">
                <div class="card">
                    <div class="h">Datos de la operación</div>
                    @foreach($operacion as $k => $v)
                        <div class="row"><span class="k">{{ $opLabels[$k] ?? $k }}:</span> <span class="v">{{ $v }}</span></div>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 70px;">Código</th>
                <th class="c" style="width: 40px;">Cant.</th>
                <th class="c" style="width: 44px;">Unidad</th>
                <th>Descripción</th>
                <th class="r" style="width: 85px;">P. Unit.</th>
                <th class="r" style="width: 85px;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $it)
            <tr>
                <td>{{ $it['codigo'] }}</td>
                <td class="c">{{ rtrim(rtrim(number_format($it['cantidad'], 2), '0'), '.') }}</td>
                <td class="c">{{ $it['unidad'] }}</td>
                <td>{{ $it['descripcion'] }}</td>
                <td class="r">{{ number_format($it['precio'], 2) }}</td>
                <td class="r">{{ number_format($it['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $m = $montos ?? ['gravadas' => $subtotal, 'exoneradas' => 0, 'inafectas' => 0];
        $igvPctLabel = rtrim(rtrim(number_format($igvPct ?? 18, 2), '0'), '.');
    @endphp
    <table class="totals">
        @if($m['gravadas'] > 0)<tr><td></td><td class="k">Op. Gravada</td><td class="v">{{ $moneda }} {{ number_format($m['gravadas'], 2) }}</td></tr>@endif
        @if($m['exoneradas'] > 0)<tr><td></td><td class="k">Op. Exonerada</td><td class="v">{{ $moneda }} {{ number_format($m['exoneradas'], 2) }}</td></tr>@endif
        @if($m['inafectas'] > 0)<tr><td></td><td class="k">Op. Inafecta</td><td class="v">{{ $moneda }} {{ number_format($m['inafectas'], 2) }}</td></tr>@endif
        @if($descuento > 0)<tr><td></td><td class="k">Descuento</td><td class="v">- {{ $moneda }} {{ number_format($descuento, 2) }}</td></tr>@endif
        <tr><td></td><td class="k">IGV ({{ $igvPctLabel }}%)</td><td class="v">{{ $moneda }} {{ number_format($igv, 2) }}</td></tr>
        <tr><td></td><td class="k grand">IMPORTE TOTAL</td><td class="v grand">{{ $moneda }} {{ number_format($total, 2) }}</td></tr>
        @if(($percepcion ?? 0) > 0)
            <tr><td></td><td class="k perc">Percepción{{ !empty($percepcionTasa) ? ' ('.rtrim(rtrim(number_format($percepcionTasa, 2), '0'), '.').'%)' : '' }}</td><td class="v perc">{{ $moneda }} {{ number_format($percepcion, 2) }}</td></tr>
            <tr><td></td><td class="k grand">TOTAL A COBRAR</td><td class="v grand">{{ $moneda }} {{ number_format($totalCobrar, 2) }}</td></tr>
        @endif
    </table>

    <div class="letras">SON: {{ $enLetras }}@if(($percepcion ?? 0) > 0) + PERCEPCIÓN @endif</div>

    @if(($percepcion ?? 0) > 0)
        <div class="leyenda" style="text-align:left; color:#b45309;">
            Comprobante de Percepción — Venta interna. Régimen de Percepciones del IGV.
        </div>
    @endif

    <table class="footer">
        <tr>
            <td style="width: 160px;">
                @if(!empty($qr))
                    <img src="{{ $qr }}" alt="QR" style="width: 120px; height: 120px;">
                @endif
            </td>
            <td class="firma">
                @if(!empty($hash))
                    <div class="row"><span class="lbl">Firma digital (Hash):</span></div>
                    <div class="hash">{{ $hash }}</div>
                    <div class="row" style="margin-top:4px;"><span class="lbl">Estado SUNAT:</span> <strong>{{ $estado }}</strong></div>
                @endif
            </td>
        </tr>
    </table>

    <div class="leyenda">
        @if(!empty($hash))
            Representación impresa del comprobante electrónico. Consulte su validez en www.sunat.gob.pe
        @else
            Documento interno — Generado por ERP Comercial.
        @endif
    </div>
</div>
</body>
</html>
