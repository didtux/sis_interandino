<!DOCTYPE html>
{{--
    Recibo con el mismo formato que el de mensualidades (dibujarRecibo() en
    pagos/index.blade.php, jsPDF): media carta apaisada, marco punteado,
    cuadros de fecha / Bs. / $us., renglones punteados y dos copias.
    Las medidas son las de jsPDF, en puntos; "y" es la línea base del texto.
--}}
<html><head><meta charset="utf-8"><title>Recibo {{ $r->codigo }}</title>
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #000; }
    .hoja { position: relative; width: 612pt; height: 396pt; overflow: hidden; }
    .salto { page-break-after: always; }
    .t { position: absolute; white-space: nowrap; line-height: 1; }
    .b { font-weight: bold; }
    .c { text-align: center; }
    .r { text-align: right; }
    .punteada { position: absolute; height: 0; border-top: 1pt dotted #000; }
    .caja { position: absolute; border: 1pt solid #000; border-radius: 2pt; }
    .anulado { position: absolute; top: 150pt; left: 0; width: 612pt; text-align: center; font-size: 70pt; font-weight: bold;
               color: rgba(200, 0, 0, 0.22); transform: rotate(-12deg); }
</style></head>
<body>
@php
    // Texto con la línea base en (x, y), como doc.text() de jsPDF
    $txt = function ($x, $y, $tam, $texto, $clase = '', $alinear = 'left') {
        $top = $y - $tam * 0.8;
        if ($alinear === 'center') { $left = $x - 300; $w = 600; }
        elseif ($alinear === 'right') { $left = $x - 600; $w = 600; }
        else { $left = $x; $w = null; }
        return '<div class="t ' . $clase . ($alinear !== 'left' ? ' ' . ($alinear === 'center' ? 'c' : 'r') : '') . '" style="left:' . $left . 'pt;top:' . $top . 'pt;font-size:' . $tam . 'pt;'
            . ($w ? 'width:' . $w . 'pt;' : '') . '">' . e($texto) . '</div>';
    };
    $linea = fn($x1, $y, $x2) => '<div class="punteada" style="left:' . $x1 . 'pt;top:' . $y . 'pt;width:' . ($x2 - $x1) . 'pt;"></div>';
    $caja = fn($x, $y, $w, $h) => '<div class="caja" style="left:' . $x . 'pt;top:' . $y . 'pt;width:' . ($w - 2) . 'pt;height:' . ($h - 2) . 'pt;"></div>';

    $fecha = $r->fecha ? \Carbon\Carbon::parse($r->fecha)->format('d/m/Y') : '';
    $entero = (int) floor(round($r->total, 2));
    $centavos = (int) round((round($r->total, 2) - $entero) * 100);
    $literal = ($entero === 0 ? 'Cero' : mb_convert_case(numero_en_letras($entero), MB_CASE_TITLE, 'UTF-8')) . ' ' . str_pad($centavos, 2, '0', STR_PAD_LEFT) . '/100';
    [$concepto] = $r->lineas[0] ?? [''];
    if (mb_strlen($concepto) > 70) $concepto = mb_substr($concepto, 0, 70) . '...';
    // Renglones libres bajo el estudiante: forma de pago y la nota del recibo
    $renglones = array_values(array_filter([
        $r->metodo ? 'Forma de pago: ' . $r->metodo . ($r->metodo === 'MIXTO' ? ' (efectivo Bs ' . number_format($r->efectivo, 2) . ' · QR Bs ' . number_format($r->qr, 2) . ')' : '') : null,
        $r->anulado ? 'RECIBO ANULADO' . ($r->anulado_motivo ? ': ' . $r->anulado_motivo : '') : null,
    ]));
    $nota = $r->nota ? wordwrap($r->nota, 120, "\n", true) : '';
    foreach (array_filter(explode("\n", $nota)) as $n) $renglones[] = $n;
    $renglones = array_slice($renglones, 0, 3);
@endphp

@foreach([1, 2] as $copia)
<div class="hoja {{ $copia === 1 ? 'salto' : '' }}">
    {{-- Marco punteado --}}
    <div style="position:absolute;left:10pt;top:10pt;width:590pt;height:374pt;border:1pt dotted #000;"></div>
    @if($r->anulado)<div class="anulado">ANULADO</div>@endif

    {{-- Encabezado --}}
    {!! $txt(15, 25, 8, 'U.E. PRIVADA INTERANDINO BOLIVIANO', 'b') !!}
    {!! $txt(15, 35, 6.5, 'C/ VICTOR GUTIERREZ Nº 3339') !!}
    {!! $txt(15, 43, 6.5, 'TELÉFONO: 2840320 - 67304340') !!}

    {{-- Cuadros de la derecha --}}
    {!! $caja(470, 10, 128, 21) !!}
    {!! $txt(534, 18, 8, 'Día/Mes/Año', 'b', 'center') !!}
    {!! $txt(534, 28, 12, $fecha, 'b', 'center') !!}
    {!! $caja(470, 34, 128, 16) !!}
    {!! $txt(478, 44, 9, 'Bs.', 'b') !!}
    {!! $txt(588, 44, 10, number_format($r->total, 2, '.', ''), 'b', 'right') !!}
    {!! $caja(470, 53, 128, 16) !!}
    {!! $txt(478, 63, 9, '$us.', 'b') !!}
    <div style="position:absolute;left:505pt;top:61pt;width:83pt;border-top:1pt solid #000;"></div>

    {{-- Título --}}
    {!! $txt(306, 85, 28, 'RECIBO-' . $r->codigo, 'b', 'center') !!}

    {{-- Cancelado por / La suma de / Por concepto de --}}
    {!! $txt(15, 110, 11, 'Cancelado por:', 'b') !!}
    {!! $linea(100, 112, 585) !!}
    {!! $txt(105, 110, 11, $r->padre ?: '') !!}

    {!! $txt(15, 130, 11, 'La suma de:', 'b') !!}
    {!! $linea(85, 132, 585) !!}
    {!! $txt(90, 130, 11, $literal) !!}

    {!! $txt(15, 150, 11, 'Por concepto de:', 'b') !!}
    {!! $linea(110, 152, 585) !!}
    {!! $txt(115, 150, 11, $concepto) !!}

    {!! $linea(15, 172, 585) !!}
    {!! $txt(15, 170, 11, 'Est: ' . ($r->estudiante ?: '') . '  -  Curso: ' . ($r->curso ?: '')) !!}

    @foreach([192, 214, 236] as $i => $y)
        {!! $linea(15, $y, 597) !!}
        @if(isset($renglones[$i])){!! $txt(18, $y - 3, 8.5, $renglones[$i]) !!}@endif
    @endforeach

    {{-- Total --}}
    {!! $linea(15, 295, 597) !!}
    {!! $txt(480, 310, 13, 'TOTAL', 'b') !!}
    {!! $txt(585, 310, 13, 'Bs. ' . number_format($r->total, 2, '.', ''), 'b', 'right') !!}

    {{-- Firmas --}}
    {!! $linea(80, 355, 220) !!}
    {!! $txt(150, 370, 9, 'RECIBÍ CONFORME', 'b', 'center') !!}
    {!! $txt(320, 340, 8, 'Firma:') !!}
    {!! $linea(350, 342, 450) !!}
    {!! $txt(460, 340, 8, 'C.I.:') !!}
    {!! $linea(480, 342, 580) !!}
    {!! $txt(320, 358, 8, 'Nom. Y Ap.:') !!}
    {!! $linea(370, 360, 580) !!}
    {!! $txt(450, 370, 9, 'ENTREGUÉ CONFORME', 'b', 'center') !!}
</div>
@endforeach
</body></html>
