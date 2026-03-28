<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'rol_roles';
    protected $primaryKey = 'rol_id';
    public $timestamps = false;

    protected $fillable = ['rol_nombre', 'rol_descripcion', 'rol_visible'];

    public function permisos()
    {
        return $this->hasMany(RolPermiso::class, 'rol_id', 'rol_id');
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'rol_id', 'rol_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('rol_visible', 1);
    }
}
