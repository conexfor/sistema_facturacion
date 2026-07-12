<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @php
        $isGuia = ($layout ?? 'comprobante') === 'guia';
        $money = fn ($n) => number_format((float) $n, 2);
        $qty = fn ($n) => rtrim(rtrim(number_format((float) $n, 3), '0'), '.');
        $m = $montos ?? ['gravadas' => $subtotal ?? 0, 'exoneradas' => 0, 'inafectas' => 0];
        $igvPctLabel = rtrim(rtrim(number_format($igvPct ?? 18, 2), '0'), '.');
        $totalCobrar = ($total ?? 0) + ($percepcion ?? 0);
    @endphp
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 8.5px; color: #000; margin: 0; padding: 0; line-height: 1.35; }
        .t { padding: 6px 8px; width: 100%; }
        .c { text-align: center; }
        .r { text-align: right; }
        .b { font-weight: bold; }
        .rs { font-size: 11px; font-weight: bold; }
        .muted { color: #333; }
        .sep { border: 0; border-top: 1px dashed #000; margin: 5px 0; }
        .doc-title { font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .kv { width: 100%; }
        .kv td { vertical-align: top; padding: 0; font-size: 8.5px; }
        .kv td.k { color: #333; width: 38%; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { text-align: left; font-size: 8px; border-bottom: 1px solid #000; padding: 2px 0; }
        table.items td { padding: 2px 0; font-size: 8.5px; vertical-align: top; }
        .tot td { padding: 1px 0; }
        .grand { font-size: 10px; font-weight: bold; border-top: 1px solid #000; }
        .letras { margin-top: 4px; font-size: 8px; font-style: italic; }
        .qr { text-align: center; margin-top: 6px; }
        .foot { text-align: center; font-size: 7.5px; color: #333; margin-top: 6px; }
        .anulado { border: 1.5px solid #000; padding: 1px 6px; font-weight: bold; }
    </style>
</head>
<body>
<div class="t">
    @if(!empty($emisor['logo']))
        <div class="c" style="margin-bottom:4px;"><img src="{{ $emisor['logo'] }}" alt="Logo" style="max-height: 46px; max-width: 150px;"></div>
    @endif
    <div class="c rs">{{ $emisor['razon_social'] }}</div>
    @if(!empty($emisor['nombre_comercial']))<div class="c muted">{{ $emisor['nombre_comercial'] }}</div>@endif
    <div class="c b">R.U.C. {{ $emisor['ruc'] }}</div>
    <div class="c muted">{{ $emisor['direccion'] ?: 'Lima - Perú' }}</div>
    @if(!empty($emisor['ubicacion']))<div class="c muted">{{ $emisor['ubicacion'] }}</div>@endif
    @if(!empty($emisor['telefono']))<div class="c muted">Tel. {{ $emisor['telefono'] }}</div>@endif

    <hr class="sep">
    <div class="c doc-title">{{ $titulo }}</div>
    @if(!empty($subtitulo))<div class="c muted">{{ $subtitulo }}</div>@endif
    <div class="c b" style="font-size:10px;">{{ $numero }}</div>
    @if(!empty($anulado))<div class="c" style="margin-top:3px;"><span class="anulado">ANULADO</span></div>@endif

    <hr class="sep">
    <table class="kv">
        <tr><td class="k">{{ $receptorLabel }}:</td><td class="b">{{ $receptor['nombre'] ?: '—' }}</td></tr>
        <tr><td class="k">{{ $receptor['tipoLabel'] }}:</td><td>{{ $receptor['doc'] ?: '—' }}</td></tr>
        @if(!empty($receptor['direccion']))
        <tr><td class="k">Dirección:</td><td>{{ $receptor['direccion'] }}</td></tr>
        @endif
        @if($isGuia)
            @foreach($traslado as $k => $v)
                <tr><td class="k">{{ $k }}:</td><td>{{ $v }}</td></tr>
            @endforeach
        @else
            @foreach($operacion as $k => $v)
                <tr>
                    <td class="k">{{ [
                        'fecha' => 'Fecha', 'moneda' => 'Moneda', 'formaPago' => 'Pago', 'vendedor' => 'Vendedor',
                        'documentoAfectado' => 'Doc. afectado', 'motivo' => 'Motivo', 'comprobante' => 'Comprobante',
                        'estadoPago' => 'Estado',
                    ][$k] ?? $k }}:</td>
                    <td>{{ $v }}</td>
                </tr>
            @endforeach
        @endif
    </table>

    @if($isGuia)
        <hr class="sep">
        <table class="kv">
            <tr><td class="k">Partida:</td><td>{{ $partida ?: '—' }}</td></tr>
            <tr><td class="k">Llegada:</td><td>{{ $llegada ?: '—' }}</td></tr>
            @foreach($transporte as $k => $v)
                <tr><td class="k">{{ $k }}:</td><td>{{ $v }}</td></tr>
            @endforeach
        </table>
    @endif

    <hr class="sep">
    <table class="items">
        <thead>
            <tr>
                <th style="width:34px;">Cant</th>
                <th>Descripción</th>
                @unless($isGuia)<th class="r" style="width:52px;">Importe</th>@endunless
            </tr>
        </thead>
        <tbody>
            @foreach($items as $it)
            <tr>
                <td>{{ $qty($it['cantidad']) }} {{ $it['unidad'] }}</td>
                <td>
                    {{ $it['descripcion'] }}
                    @unless($isGuia)
                        <br><span class="muted" style="font-size:7.5px;">P.U. {{ $moneda }} {{ $money($it['precio']) }}</span>
                    @endunless
                </td>
                @unless($isGuia)<td class="r">{{ $money($it['total']) }}</td>@endunless
            </tr>
            @endforeach
        </tbody>
    </table>

    @unless($isGuia)
        <hr class="sep">
        <table class="kv tot">
            @if($m['gravadas'] > 0)<tr><td class="k">Op. Gravada</td><td class="r">{{ $moneda }} {{ $money($m['gravadas']) }}</td></tr>@endif
            @if($m['exoneradas'] > 0)<tr><td class="k">Op. Exonerada</td><td class="r">{{ $moneda }} {{ $money($m['exoneradas']) }}</td></tr>@endif
            @if($m['inafectas'] > 0)<tr><td class="k">Op. Inafecta</td><td class="r">{{ $moneda }} {{ $money($m['inafectas']) }}</td></tr>@endif
            @if(($descuento ?? 0) > 0)<tr><td class="k">Descuento</td><td class="r">- {{ $moneda }} {{ $money($descuento) }}</td></tr>@endif
            <tr><td class="k">IGV ({{ $igvPctLabel }}%)</td><td class="r">{{ $moneda }} {{ $money($igv) }}</td></tr>
            <tr class="grand"><td>TOTAL</td><td class="r">{{ $moneda }} {{ $money($total) }}</td></tr>
            @if(($percepcion ?? 0) > 0)
                <tr><td class="k">Percepción</td><td class="r">{{ $moneda }} {{ $money($percepcion) }}</td></tr>
                <tr class="grand"><td>A COBRAR</td><td class="r">{{ $moneda }} {{ $money($totalCobrar) }}</td></tr>
            @endif
        </table>
        <div class="letras">SON: {{ $enLetras }}</div>
    @endunless

    @if(!empty($observacion))
        <hr class="sep">
        <div style="font-size:8px;"><span class="b">Obs.:</span> {{ $observacion }}</div>
    @endif

    @if(!empty($qr))
        <div class="qr"><img src="{{ $qr }}" alt="QR" style="width: 96px; height: 96px;"></div>
    @endif

    @if(!empty($hash))
        <div class="foot">Hash: {{ $hash }}</div>
        <div class="foot">Estado SUNAT: {{ $estado }}</div>
    @endif

    <hr class="sep">
    <div class="foot">
        @if(!empty($hash))
            Representación impresa del comprobante electrónico.<br>Consulte su validez en www.sunat.gob.pe
        @else
            Documento interno — Generado por ERP Comercial.
        @endif
    </div>
    <div class="foot">¡Gracias por su preferencia!</div>
</div>
</body>
</html>
