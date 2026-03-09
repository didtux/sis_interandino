<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PadreFamilia extends Model
{
    protected $table = 'cole_padresfamilia';
    protected $primaryKey = 'pfam_id';
    public $timestamps = false;

    protected $fillable = [
        'pfam_codigo', 'pfam_ci', 'pfam_nombres', 'pfam_domicilio',
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
}
