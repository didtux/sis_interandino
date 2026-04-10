<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActividadRegistro extends Model
{
    protected $table = 'actividades_registros';
    protected $primaryKey = 'actreg_id';
    public $timestamps = false;
    protected $fillable = ['actcat_id', 'est_codigo', 'actreg_hora', 'actreg_observacion', 'actreg_registrado_por'];
    protected $casts = ['actreg_fecha_registro' => 'datetime'];

    public function categoria() { return $this->belongsTo(ActividadCategoria::class, 'actcat_id', 'actcat_id'); }
    public function estudiante() { return $this->belongsTo(Estudiante::class, 'est_codigo', 'est_codigo'); }
}
