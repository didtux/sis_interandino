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
        return \App\Support\Permisos::puede((int) $this->rol_id, $modSlug, $accion);
    }

    /** Capacidad especial asignada al rol (config/permisos.php). */
    public function tieneCapacidad(string $slug): bool
    {
        return \App\Support\Permisos::tieneCapacidad((int) $this->rol_id, $slug);
    }

    public function tieneAccesoModulo($modSlug)
    {
        return $this->tienePermiso($modSlug, 'perm_ver');
    }

    /** Ver en alguno de los módulos dados. */
    public function tieneAccesoAlguno(array $slugs)
    {
        foreach ($slugs as $s) {
            if ($this->tieneAccesoModulo($s)) return true;
        }
        return false;
    }

    /**
     * Ver en el módulo o en cualquiera de sus submódulos. Para los grupos del
     * menú: antes un rol con sólo "Licencias" marcado no veía el grupo
     * Asistencias porque se le pedía el módulo padre.
     */
    public function tieneAccesoGrupo($modSlug)
    {
        return \App\Support\Permisos::puedeGrupo((int) $this->rol_id, $modSlug);
    }

    public function scopeActivo($query)
    {
        return $query->where('us_visible', 1);
    }
}
