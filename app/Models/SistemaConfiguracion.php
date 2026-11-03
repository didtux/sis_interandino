<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SistemaConfiguracion extends Model
{
    protected $table = 'sistema_configuracion';
    protected $primaryKey = 'config_id';
    public $timestamps = false;

    protected $fillable = [
        'config_logo',
        'config_denominacion',
        'config_nombre_ue',
        'config_direccion',
        'config_telefono',
        'config_ciudad',
        'config_email',
        'config_facebook',
        'config_instagram',
        'config_tiktok',
        'config_youtube',
        'config_whatsapp',
        'config_horario',
        'config_mapa_url',
    ];

    protected $casts = ['config_fecha' => 'datetime'];

    public static function actual()
    {
        return self::orderBy('config_id')->first();
    }

    /** Redes cargadas, como [url, icono, nombre]; las vacías no se muestran. */
    public function redes(): array
    {
        $redes = [];
        foreach (['config_facebook' => ['fab fa-facebook-f', 'Facebook'], 'config_instagram' => ['fab fa-instagram', 'Instagram'],
                  'config_tiktok' => ['fab fa-tiktok', 'TikTok'], 'config_youtube' => ['fab fa-youtube', 'YouTube']] as $campo => [$icono, $nombre]) {
            if ($this->$campo) $redes[] = [$this->$campo, $icono, $nombre];
        }
        if ($wa = $this->whatsapp_url) $redes[] = [$wa, 'fab fa-whatsapp', 'WhatsApp'];
        return $redes;
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        $num = preg_replace('/\D/', '', (string) $this->config_whatsapp);
        return $num ? 'https://wa.me/' . $num : null;
    }
}
