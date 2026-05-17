@php $sc = $sc ?? \App\Models\SistemaConfiguracion::actual(); @endphp
<table class="header-table">
    <tr>
        <td class="h-logo-cell">
            @if($sc && $sc->config_logo && file_exists(public_path('storage/'.$sc->config_logo)))
                <img src="{{ public_path('storage/'.$sc->config_logo) }}" alt="Logo">
            @elseif(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" alt="Logo">
            @endif
        </td>
        <td class="h-info-cell">
            <div class="ue-nombre">UNIDAD EDUCATIVA PRIVADA INTERANDINO BOLIVIANO</div>
            <div class="ue-dir">
                Calle V. Gutiérrez N° 3339 e/c Álvarez Plata y Catacora — Zona 16 de Julio · El Alto, La Paz, Bolivia<br>
                Telf.: 2840320 · Fax: 2846479 · Resolución Administrativa RA 311/2006
            </div>
            <div class="titulo-banda">{{ $tituloBanda ?? 'REPORTE' }}</div>
        </td>
        <td class="h-fecha-cell">
            <div class="fecha-label">FECHA</div>
            <div class="fecha-val">{{ now()->format('d/m/Y') }}</div>
            <div class="fecha-label" style="margin-top:6px;">HORA</div>
            <div class="fecha-val">{{ now()->format('H:i') }}</div>
        </td>
    </tr>
</table>
