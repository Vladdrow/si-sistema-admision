<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePlantillaHorario extends Model
{
    protected $table = 'detalle_plantilla_horario';

    protected $primaryKey = 'id_detalle';

    public $timestamps = false;

    protected $fillable = [
        'dia',
        'hora_inicio',
        'hora_fin',
        'modalidad',
        'id_plantilla',
    ];

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillaHorario::class, 'id_plantilla', 'id_plantilla');
    }
}
