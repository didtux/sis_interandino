<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bloque repetible de la web pública: aviso, requisito de inscripción,
 * documento descargable, autoridad o hito de la línea del tiempo.
 */
class LandingBloque extends Model
{
    protected $table = 'landing_bloques';
    protected $primaryKey = 'lb_id';
    public $timestamps = false;

    protected $fillable = [
        'lb_seccion', 'lb_titulo', 'lb_subtitulo', 'lb_texto', 'lb_imagen', 'lb_archivo', 'lb_link', 'lb_link_texto',
        'lb_fecha', 'niv_nombre', 'ges_anio', 'vigente_desde', 'vigente_hasta', 'lb_orden', 'lb_activo',
        'lb_usuario', 'lb_fecha_registro',
    ];
    protected $casts = ['lb_fecha' => 'date', 'vigente_desde' => 'date', 'vigente_hasta' => 'date', 'lb_fecha_registro' => 'datetime'];

    public const NIVELES = ['INICIAL' => 'Inicial', 'PRIMARIA' => 'Primaria', 'SECUNDARIA' => 'Secundaria'];

    public function getUrlImagenAttribute(): ?string
    {
        return $this->lb_imagen ? asset('storage/' . $this->lb_imagen) : null;
    }

    public function getUrlArchivoAttribute(): ?string
    {
        return $this->lb_archivo ? asset('storage/' . $this->lb_archivo) : null;
    }
}
