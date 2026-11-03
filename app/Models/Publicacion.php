<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Publicación del colegio para el portal de padres: revista, lista de útiles
 * por curso, reglamento, horarios, misión y visión, agenda institucional.
 */
class Publicacion extends Model
{
    protected $table = 'publicaciones_institucionales';
    protected $primaryKey = 'pub_id';
    public $timestamps = false;

    protected $fillable = [
        'pub_tipo', 'pub_titulo', 'pub_descripcion', 'ges_anio', 'niv_nombre', 'cur_codigo', 'pub_formato',
        'pub_archivo', 'pub_contenido_html', 'pub_url', 'visible_padres', 'publicado_desde', 'publicado_hasta',
        'pub_orden', 'pub_estado', 'pub_usuario', 'pub_fecha',
    ];
    protected $casts = ['publicado_desde' => 'date', 'publicado_hasta' => 'date', 'pub_fecha' => 'datetime'];

    public const TIPOS = [
        'REVISTA'       => ['Revistas', 'fas fa-book-open'],
        'LISTA_UTILES'  => ['Lista de útiles', 'fas fa-pencil-ruler'],
        'REGLAMENTO'    => ['Reglamento', 'fas fa-gavel'],
        'HORARIO'       => ['Horarios', 'fas fa-clock'],
        'MISION_VISION' => ['Misión y visión', 'fas fa-bullseye'],
        'AGENDA'        => ['Agenda', 'fas fa-calendar-alt'],
        'OTRO'          => ['Otros documentos', 'fas fa-file-alt'],
    ];

    public function curso() { return $this->belongsTo(Curso::class, 'cur_codigo', 'cur_codigo'); }

    /** Publicadas y vigentes hoy, para la gestión dada (o sin gestión fija). */
    public function scopeVisiblesPara($q, int $gestion)
    {
        $hoy = now()->toDateString();
        return $q->where('pub_estado', 1)->where('visible_padres', 1)
            ->where(fn($w) => $w->whereNull('ges_anio')->orWhere('ges_anio', $gestion))
            ->where(fn($w) => $w->whereNull('publicado_desde')->orWhere('publicado_desde', '<=', $hoy))
            ->where(fn($w) => $w->whereNull('publicado_hasta')->orWhere('publicado_hasta', '>=', $hoy));
    }

    /** ¿Le corresponde a un estudiante de este curso/nivel? */
    public function aplicaA(?string $curCodigo, ?string $nivel): bool
    {
        if ($this->cur_codigo) return $this->cur_codigo === $curCodigo;
        if ($this->niv_nombre) return strtoupper((string) $nivel) === strtoupper($this->niv_nombre);
        return true;
    }

    public function getUrlArchivoAttribute(): ?string
    {
        return $this->pub_archivo ? asset('storage/' . $this->pub_archivo) : null;
    }
}
