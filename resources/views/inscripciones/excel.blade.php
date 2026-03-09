<table>
    <thead>
        <tr>
            <th colspan="12" style="text-align: center; font-weight: bold; font-size: 14px;">
                U.E. PRIVADA INTERANDINO BOLIVIANO - INSCRIPCIONES
            </th>
        </tr>
        <tr>
            <th colspan="12" style="text-align: center;">
                Fecha: {{ date('d/m/Y H:i:s') }} - Usuario: {{ auth()->user()->us_nombres }}
            </th>
        </tr>
        <tr></tr>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <th>Código</th>
            <th>Estudiante</th>
            <th>Curso</th>
            <th>Gestión</th>
            <th>Fecha</th>
            <th>Monto Total</th>
            <th>Descuento</th>
            <th>Monto Final</th>
            <th>Pagado</th>
            <th>Saldo</th>
            <th>Tipo Factura</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($inscripciones as $i)
            <tr>
                <td>{{ $i->insc_codigo }}</td>
                <td>{{ $i->estudiante->est_nombres ?? 'N/A' }} {{ $i->estudiante->est_apellidos ?? '' }}</td>
                <td>{{ $i->curso->cur_nombre ?? 'N/A' }}</td>
                <td>{{ $i->insc_gestion }}</td>
                <td>{{ $i->insc_fecha ? $i->insc_fecha->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ number_format($i->insc_monto_total, 2) }}</td>
                <td>{{ number_format($i->insc_monto_descuento, 2) }}</td>
                <td>{{ number_format($i->insc_monto_final ?: $i->insc_monto_total, 2) }}</td>
                <td>{{ number_format($i->insc_monto_pagado, 2) }}</td>
                <td>{{ number_format($i->insc_saldo, 2) }}</td>
                <td>{{ $i->insc_sin_factura ? 'Sin Factura' : 'Con Factura' }}</td>
                <td>
                    @if($i->insc_estado == 0)
                        Anulada
                    @elseif($i->insc_monto_pagado >= 300)
                        Inscripción Pagada
                    @elseif($i->insc_monto_pagado < 300)
                        Pendiente
                    @else
                        Cancelada
                    @endif
                </td>
            </tr>
        @endforeach
        <tr></tr>
        <tr style="font-weight: bold;">
            <td colspan="8" style="text-align: right;">TOTAL PAGADO:</td>
            <td>{{ number_format($inscripciones->where('insc_estado', '!=', 0)->sum('insc_monto_pagado'), 2) }}</td>
            <td colspan="3"></td>
        </tr>
    </tbody>
</table>

<script>
    window.onload = function() {
        var tab_text = document.documentElement.outerHTML;
        var data_type = 'data:application/vnd.ms-excel';
        var blob = new Blob([tab_text], {type: data_type});
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'inscripciones_' + new Date().getTime() + '.xls';
        link.click();
        setTimeout(function(){ window.close(); }, 100);
    };
</script>
