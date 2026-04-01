<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'rol_usuarios';
    protected $primaryKey = 'us_id';
    public $timestamps = false;

    protected $fillable = [
        'us_codigo', 'rol_id', 'us_ci', 'us_nombres',
        'us_apellidos', 'us_user', 'us_pass', 'us_foto', 'us_visible',
        'us_entidad_tipo', 'us_entidad_id'
    ];

    protected $hidden = ['us_pass', 'remember_token'];

    protected $casts = [
        'us_fecha' => 'datetime',
        'us_visible' => 'integer',
        'rol_id' => 'integer'
    ];

    public function getAuthPassword()
    {
        return $this->us_pass;
    }

    public function getAuthIdentifierName()
    {
        return 'us_user';
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'rol_id');
    }

    public function tienePermiso($modSlug, $accion = 'perm_ver')
    {
        if ($this->rol_id == 1) return true; // Admin total

        return RolPermiso::where('rol_id', $this->rol_id)
            ->whereHas('modulo', fn($q) => $q->where('mod_slug', $modSlug))
            ->where($accion, 1)
            ->exists();
    }

    public function tieneAccesoModulo($modSlug)
    {
        return $this->tienePermiso($modSlug, 'perm_ver');
    }

    public function scopeActivo($query)
    {
        return $query->where('us_visible', 1);
    }
}
