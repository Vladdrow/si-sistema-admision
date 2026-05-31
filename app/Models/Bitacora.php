<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    protected $primaryKey = 'id_bitacora';

    public $timestamps = false;

    protected $fillable = [
        'fecha_hora',
        'accion',
        'modulo',
        'descripcion',
        'ip_origen',
        'id_persona',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }
}
