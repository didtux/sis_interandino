<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mensaje enviado desde el formulario de contacto de la página pública. */
class ContactoMensaje extends Model
{
    protected $table = 'contacto_mensajes';
    protected $primaryKey = 'cm_id';

    protected $fillable = [
        'cm_nombre', 'cm_telefono', 'cm_correo', 'cm_motivo', 'cm_curso_interes',
        'cm_mensaje', 'cm_estado', 'cm_nota_interna', 'cm_atendido_por',
        'cm_atendido_at', 'cm_ip', 'cm_user_agent',
    ];

    protected $casts = ['cm_atendido_at' => 'datetime'];

    public const MOTIVOS = [
        'INSCRIPCION'      => 'Información sobre inscripciones',
        'NIVEL_INICIAL'    => 'Consulta sobre nivel Inicial',
        'NIVEL_PRIMARIA'   => 'Consulta sobre nivel Primaria',
        'NIVEL_SECUNDARIA' => 'Consulta sobre nivel Secundaria',
        'PENSIONES'        => 'Información sobre pensiones y pagos',
        'DOCUMENTOS'       => 'Solicitud de documentos',
        'INFORMACION'      => 'Información general',
        'OTRO'             => 'Otro',
    ];

    public const ESTADOS = [
        'NUEVO'      => 'Nuevo',
        'EN_PROCESO' => 'En proceso',
        'ATENDIDO'   => 'Atendido',
        'DESCARTADO' => 'Descartado',
    ];

    public function atendidoPor()
    {
        return $this->belongsTo(User::class, 'cm_atendido_por', 'us_id');
    }

    /** Número para wa.me: sólo dígitos, con 591 si viene un celular local. */
    public function getWhatsappAttribute(): ?string
    {
        $num = preg_replace('/\D/', '', (string) $this->cm_telefono);
        if ($num === '') return null;
        return strlen($num) === 8 ? '591' . $num : $num;
    }
}
