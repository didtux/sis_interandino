<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 10px; }
        .recibo-container { border: 1px solid #000; padding: 10px; margin-bottom: 20px; page-break-inside: avoid; height: 48%; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .header-left { display: table-cell; width: 65%; vertical-align: top; font-size: 9px; line-height: 1.4; }
        .header-right { display: table-cell; width: 35%; vertical-align: top; text-align: right; }
        .recibo-box { border: 2px solid #000; display: inline-block; padding: 8px 20px; font-size: 16px; font-weight: bold; }
        .info-line { margin: 5px 0; font-size: 10px; }
        .dotted-line { border-bottom: 1px dotted #000; display: inline-block; min-width: 150px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 5px; text-align: left; font-size: 9px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .total-row { font-weight: bold; text-align: right; }
        .son-line { margin: 10px 0; font-size: 9px; }
        .footer-line { border-top: 1px dotted #000; margin-top: 30px; padding-top: 5px; text-align: center; font-size: 9px; }
    </style>
</head>
<body>
    @foreach(['', ''] as $tipo)
    <div class="recibo-container">
        <div class="header">
            <div class="header-left">
                <strong>U.E. PRIVADA INTERANDINO BOLIVIANO</strong><br>
                C/ VICTOR GUTIERREZ N° 3339<br>
                TELEFONO: 2840320 - 67304340
            </div>
            <div class="header-right">
                <div class="recibo-box">RECIBO</div>
            </div>
        </div>

        <div class="info-line">
            <strong>Cancelado por:</strong> <span class="dotted-line">{{ $pago->padreFamilia->pfam_nombres ?? 'N/A' }}</span>
        </div>
        <div class="info-line">
            <strong>La suma de:</strong> <span class="dotted-line">Quinientos 00/100</span>
        </div>
        <div class="info-line">
            <strong>Por concepto de:</strong> <span class="dotted-line">Pago de Servicio</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="20%">Cód.</th>
                    <th width="80%">Descripción</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $pago->pserv_codigo }}</td>
                    <td>{{ $pago->servicio->serv_nombre ?? 'N/A' }} - Estudiante: {{ $pago->estudiante->est_nombres ?? 'N/A' }} {{ $pago->estudiante->est_apellidos ?? '' }} - Curso: {{ $pago->estudiante->curso->cur_nombre ?? 'N/A' }}@if($pago->pserv_observacion) - Obs: {{ $pago->pserv_observacion }}@endif</td>
                </tr>
                <tr>
                    <td colspan="2" style="height: 40px;"></td>
                </tr>
                <tr>
                    <td colspan="2" style="height: 40px;"></td>
                </tr>
            </tbody>
        </table>

        <div class="son-line">
            <strong>SON:</strong> <span class="dotted-line" style="min-width: 400px;">Quinientos 00/100 Bolivianos</span>
        </div>

        <div class="total-row" style="margin: 10px 0; font-size: 11px;">
            <strong>TOTAL Bs. {{ number_format($pago->pserv_total, 2) }}</strong>
        </div>

        <div class="footer-line">
            <strong>Firma:</strong> _________________ <strong>C.I.:</strong> _________________
        </div>
        <div style="text-align: center; margin-top: 5px; font-size: 9px;">
            <strong>Nom. Y Ap.:</strong> _________________________________
        </div>
    </div>
    @endforeach
</body>
</html>
