<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteObservado extends Model
{
    protected $table = 'estudiantes_observados';
    protected $primaryKey = 'obs_id';
    public $timestamps = false;

    protected $fillable = [
        'est_codigo', 'obs_estudiante_nombre', 'obs_estudiante_ci', 'obs_curso_texto',
        'obs_gestion',
        'obs_motivo_tipo', 'obs_motivo',
        'obs_registrado_por', 'obs_registrado_por_nombre',
        'obs_fecha_registro',
        'obs_liberado_por', 'obs_liberado_por_nombre',
        'obs_fecha_liberacion', 'obs_motivo_liberacion',
        'obs_activo',
    ];

    protected $casts = [
        'obs_fecha_registro'   => 'datetime',
        'obs_fecha_liberacion' => 'datetime',
        'obs_activo'           => 'integer',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo');
    }

    /**
     * ¿El estudiante está bloqueado para inscribirse en la gestión indicada?
     */
    public static function estaBloqueado(string $estCodigo, int $gestion): bool
    {
        return self::where('est_codigo', $estCodigo)
            ->where('obs_gestion', $gestion)
            ->where('obs_activo', 1)->exists();
    }

    public static function vigentePara(string $estCodigo, int $gestion)
    {
        return self::where('est_codigo', $estCodigo)
            ->where('obs_gestion', $gestion)
            ->where('obs_activo', 1)->first();
    }

    /**
     * ¿Existe un observado activo que coincida por CI? (para estudiantes que aún
     * no estaban en el sistema cuando se los agregó a la lista).
     */
    public static function vigentePorCi(?string $ci, int $gestion)
    {
        $ci = trim((string) $ci);
        if ($ci === '') return null;
        return self::where('obs_estudiante_ci', $ci)
            ->where('obs_gestion', $gestion)
            ->where('obs_activo', 1)->first();
    }
}
