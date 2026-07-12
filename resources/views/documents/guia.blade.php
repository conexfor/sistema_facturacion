<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
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
        .doc-box .tipo { font-weight: bold; font-size: 11px; margin-top: 4px; text-transform: uppercase; }
        .doc-box .sub { font-size: 9px; color: #475569; }
        .doc-box .num { font-size: 13px; margin-top: 4px; letter-spacing: 1px; }
        .cols { margin-top: 14px; }
        .card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; }
        .card .h { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-bottom: 4px; font-weight: bold; }
        .card .row { margin: 1px 0; }
        .card .k { color: #64748b; }
        .card .v { color: #0f172a; font-weight: bold; }
        table.items { margin-top: 4px; }
        table.items th { background: #f1f5f9; color: #334155; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; }
        table.items td { padding: 5px 8px; border-bottom: 1px solid #eef2f7; }
        table.items .c { text-align: center; }
        .footer { margin-top: 14px; }
        .footer td { vertical-align: top; }
        .firma { font-size: 9px; color: #475569; }
        .firma .lbl { color: #64748b; }
        .hash { word-break: break-all; font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; }
        .leyenda { margin-top: 10px; font-size: 8.5px; color: #94a3b8; text-align: center; }
        .obs { margin-top: 8px; padding: 6px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 9.5px; }
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
                    @if(!empty($subtitulo))<div class="sub">{{ $subtitulo }}</div>@endif
                    <div class="num">{{ $numero }}</div>
                </div>
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
                    <div class="h">Datos del traslado</div>
                    @foreach($traslado as $k => $v)
                        <div class="row"><span class="k">{{ $k }}:</span> <span class="v">{{ $v }}</span></div>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    <table class="cols">
        <tr>
            <td style="width: 50%; padding-right: 6px;">
                <div class="card">
                    <div class="h">Punto de partida</div>
                    <div class="row">{{ $partida ?: '—' }}</div>
                </div>
            </td>
            <td style="width: 50%; padding-left: 6px;">
                <div class="card">
                    <div class="h">Punto de llegada</div>
                    <div class="row">{{ $llegada ?: '—' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="card">
        <div class="h">Datos del transporte</div>
        @foreach($transporte as $k => $v)
            <div class="row"><span class="k">{{ $k }}:</span> <span class="v">{{ $v }}</span></div>
        @endforeach
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 80px;">Código</th>
                <th class="c" style="width: 60px;">Cant.</th>
                <th class="c" style="width: 60px;">Unidad</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $it)
            <tr>
                <td>{{ $it['codigo'] }}</td>
                <td class="c">{{ rtrim(rtrim(number_format($it['cantidad'], 2), '0'), '.') }}</td>
                <td class="c">{{ $it['unidad'] }}</td>
                <td>{{ $it['descripcion'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($observacion))
        <div class="obs"><strong>Observación:</strong> {{ $observacion }}</div>
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
            Representación impresa de la Guía de Remisión Electrónica. Consulte su validez en www.sunat.gob.pe
        @else
            Guía de remisión — pendiente de envío a SUNAT.
        @endif
    </div>
</div>
</body>
</html>
