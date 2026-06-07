<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Credencial extends Authenticatable
{
    protected $table = 'credencial';

    protected $primaryKey = 'id_credencial';

    public $timestamps = false;

    protected $fillable = [
        'registro',
        'contrasena',
        'rol',
        'estado',
        'fecha_ultimo_acceso',
        'intentos_fallidos',
        'fecha_bloqueo',
        'codigo_recuperacion',
        'fecha_expiracion_codigo',
        'id_persona',
    ];

    protected $hidden = [
        'contrasena',
        'codigo_recuperacion',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha_ultimo_acceso' => 'datetime',
            'fecha_bloqueo' => 'datetime',
            'fecha_expiracion_codigo' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'Administrador';
    }

    public function esPostulante(): bool
    {
        return $this->rol === 'Postulante';
    }

    public function esDocente(): bool
    {
        return $this->rol === 'Docente';
    }

    public function esPersonalAdministrativo(): bool
    {
        return $this->rol === 'PersonalAdministrativo';
    }

    public static function generateUniqueRegistro(): string
    {
        do {
            $registro = str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (self::where('registro', $registro)->exists());

        return $registro;
    }
}
