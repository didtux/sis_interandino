<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'rol_modulos';
    protected $primaryKey = 'mod_id';
    public $timestamps = false;

    protected $fillable = ['mod_nombre', 'mod_slug', 'mod_icono', 'mod_padre_id', 'mod_orden', 'mod_visible'];

    public function padre()
    {
        return $this->belongsTo(Modulo::class, 'mod_padre_id', 'mod_id');
    }

    public function hijos()
    {
        return $this->hasMany(Modulo::class, 'mod_padre_id', 'mod_id')->orderBy('mod_orden');
    }

    public function scopePrincipales($query)
    {
        return $query->whereNull('mod_padre_id')->where('mod_visible', 1)->orderBy('mod_orden');
    }
}
