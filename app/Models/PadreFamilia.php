<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PadreFamilia extends Model
{
    protected $table = 'cole_padresfamilia';
    protected $primaryKey = 'pfam_id';
    public $timestamps = false;

    protected $fillable = [
        'pfam_codigo', 'pfam_ci', 'pfam_nombres', 'pfam_parentesco', 'pfam_domicilio',
        'pfam_correo', 'pfam_numeroscelular', 'pfam_foto', 'pfam_estado'
    ];

    protected $casts = ['pfam_estado' => 'integer'];

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'rela_estudiantespadres', 'pfam_id', 'est_id', 'pfam_codigo', 'est_codigo')
            ->wherePivot('estpad_estado', 1);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'pfam_codigo', 'pfam_codigo');
    }

    public function scopeActivo($query)
    {
        return $query->where('pfam_estado', 1);
    }

    /** CI sólo con dígitos; '' si no sirve para identificar (vacío, 0000000…). */
    public static function ciNormalizado(?string $ci): string
    {
        $d = preg_replace('/\D/', '', (string) $ci);
        return (strlen($d) >= 5 && !preg_match('/^0+$/', $d)) ? $d : '';
    }

    /**
     * Devuelve el padre con ese CI si ya existe; si no, lo crea.
     * Antes cada inscripción creaba un padre nuevo con CI 0000000 y así quedaron
     * 138 familias con un registro por hijo (ver `php artisan padres:unificar`).
     */
    public static function obtenerOCrear(string $nombre, ?string $ci): self
    {
        $ciNorm = self::ciNormalizado($ci);
        if ($ciNorm !== '') {
            $existente = self::activo()
                ->whereRaw("REGEXP_REPLACE(pfam_ci, '[^0-9]', '') = ?", [$ciNorm])
                ->orderBy('pfam_id')->first();
            if ($existente) return $existente;
        }
        return self::create([
            'pfam_codigo'  => 'Pad' . str_pad(self::max('pfam_id') + 1, 5, '0', STR_PAD_LEFT),
            'pfam_nombres' => trim($nombre),
            'pfam_ci'      => $ciNorm !== '' ? trim((string) $ci) : '0000000',
            'pfam_estado'  => 1,
        ]);
    }

    /** Vincula padre e hijo si no lo estaban. */
    public function vincularHijo(string $estCodigo): void
    {
        $existe = \Illuminate\Support\Facades\DB::table('rela_estudiantespadres')
            ->where('est_id', $estCodigo)->where('pfam_id', $this->pfam_codigo)->exists();
        if (!$existe) {
            \Illuminate\Support\Facades\DB::table('rela_estudiantespadres')->insert([
                'est_id' => $estCodigo, 'pfam_id' => $this->pfam_codigo, 'estpad_estado' => 1,
            ]);
        }
    }

    /**
     * Hijos visibles del padre. Se suman los de todos los registros de padre
     * con el mismo CI: hasta que se unifiquen los duplicados (php artisan
     * padres:unificar), una madre con un registro por hijo veía sólo al hijo
     * atado a su usuario.
     */
    public function hijos()
    {
        $codigos = collect([$this->pfam_codigo]);
        $ci = self::ciNormalizado($this->pfam_ci);
        if ($ci !== '') {
            $codigos = $codigos->merge(self::activo()
                ->whereRaw("REGEXP_REPLACE(pfam_ci, '[^0-9]', '') = ?", [$ci])->pluck('pfam_codigo'));
        }
        $estCodigos = \Illuminate\Support\Facades\DB::table('rela_estudiantespadres')->whereIn('pfam_id', $codigos->unique())
            ->where('estpad_estado', 1)->pluck('est_id');
        return Estudiante::with('curso')->whereIn('est_codigo', $estCodigos)->where('est_visible', 1)
            ->orderBy('est_apellidos')->orderBy('est_nombres')->get();
    }
}
