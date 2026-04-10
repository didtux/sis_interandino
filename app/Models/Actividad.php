<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';
    protected $primaryKey = 'act_id';
    public $timestamps = false;
    protected $fillable = ['act_nombre', 'act_descripcion', 'act_fecha', 'act_estado', 'act_creado_por'];
    protected $casts = ['act_fecha' => 'date', 'act_fecha_registro' => 'datetime'];

    public function categorias() { return $this->hasMany(ActividadCategoria::class, 'act_id', 'act_id'); }
    public function categoriasActivas() { return $this->hasMany(ActividadCategoria::class, 'act_id', 'act_id')->where('actcat_estado', 1); }
    public function scopeActivo($q) { return $q->where('act_estado', 1); }
}
