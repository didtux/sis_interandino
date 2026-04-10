<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActividadCategoria extends Model
{
    protected $table = 'actividades_categorias';
    protected $primaryKey = 'actcat_id';
    public $timestamps = false;
    protected $fillable = ['act_id', 'actcat_nombre', 'actcat_descripcion', 'actcat_estado'];

    public function actividad() { return $this->belongsTo(Actividad::class, 'act_id', 'act_id'); }
    public function registros() { return $this->hasMany(ActividadRegistro::class, 'actcat_id', 'actcat_id'); }
}
