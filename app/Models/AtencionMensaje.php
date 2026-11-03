<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtencionMensaje extends Model
{
    protected $table = 'atencion_mensajes';
    protected $primaryKey = 'am_id';
    public $timestamps = false;

    protected $fillable = ['at_id', 'am_autor', 'us_id', 'am_texto', 'am_adjunto', 'am_leido', 'am_fecha'];
    protected $casts = ['am_fecha' => 'datetime'];

    public function usuario() { return $this->belongsTo(User::class, 'us_id', 'us_id'); }

    public function getUrlAdjuntoAttribute(): ?string
    {
        return $this->am_adjunto ? asset('storage/' . $this->am_adjunto) : null;
    }
}
