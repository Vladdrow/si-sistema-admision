<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $table = 'persona';

    protected $primaryKey = 'id_persona';

    public $timestamps = false;

    protected $fillable = [
        'ci',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'sexo',
        'direccion',
        'telefono',
        'correo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function credencial(): HasOne
    {
        return $this->hasOne(Credencial::class, 'id_persona', 'id_persona');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'id_docente', 'id_persona');
    }

    public function postulante(): HasOne
    {
        return $this->hasOne(Postulante::class, 'id_postulante', 'id_persona');
    }

    public function personalAdministrativo(): HasOne
    {
        return $this->hasOne(PersonalAdministrativo::class, 'id_personal', 'id_persona');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->nombres,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])));
    }
}
