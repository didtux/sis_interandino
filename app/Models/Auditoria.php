<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'sistema_auditoria';
    protected $primaryKey = 'audit_id';
    public $timestamps = false;

    protected $fillable = [
        'audit_usuario_id', 'audit_usuario_nombre', 'audit_accion',
        'audit_modulo', 'audit_descripcion', 'audit_registro_id',
        'audit_datos_anteriores', 'audit_datos_nuevos', 'audit_ip'
    ];

    protected $casts = [
        'audit_fecha' => 'datetime',
        'audit_datos_anteriores' => 'array',
        'audit_datos_nuevos' => 'array',
    ];

    public static function registrar($accion, $modulo, $descripcion, $registroId = null, $datosAnteriores = null, $datosNuevos = null)
    {
        $user = auth()->user();
        if (!$user) return;

        return self::create([
            'audit_usuario_id' => $user->us_id,
            'audit_usuario_nombre' => $user->us_nombres . ' ' . ($user->us_apellidos ?? ''),
            'audit_accion' => $accion,
            'audit_modulo' => $modulo,
            'audit_descripcion' => $descripcion,
            'audit_registro_id' => $registroId,
            'audit_datos_anteriores' => $datosAnteriores,
            'audit_datos_nuevos' => $datosNuevos,
            'audit_ip' => request()->ip(),
        ]);
    }
}
