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
        'us_apellidos', 'us_user', 'us_pass', 'us_foto', 'us_visible'
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

    public function scopeActivo($query)
    {
        return $query->where('us_visible', 1);
    }
}
