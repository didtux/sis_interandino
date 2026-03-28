<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolPermiso extends Model
{
    protected $table = 'rol_permisos';
    protected $primaryKey = 'perm_id';
    public $timestamps = false;

    protected $fillable = ['rol_id', 'mod_id', 'perm_ver', 'perm_crear', 'perm_editar', 'perm_eliminar'];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'rol_id');
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'mod_id', 'mod_id');
    }
}
