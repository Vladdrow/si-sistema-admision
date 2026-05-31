<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantillaHorario extends Model
{
    protected $table = 'plantilla_horario';

    protected $primaryKey = 'id_plantilla';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'turno',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePlantillaHorario::class, 'id_plantilla', 'id_plantilla')
            ->orderBy('dia')
            ->orderBy('hora_inicio');
    }
}
