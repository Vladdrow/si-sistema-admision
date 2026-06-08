<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aula extends Model
{
    protected $table = 'aula';

    protected $primaryKey = 'id_aula';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'capacidad',
        'ubicacion',
    ];

    public function grupoHorarios(): HasMany
    {
        return $this->hasMany(GrupoHorario::class, 'id_aula', 'id_aula');
    }
}
