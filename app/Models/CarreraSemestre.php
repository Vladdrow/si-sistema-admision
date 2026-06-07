<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarreraSemestre extends Model
{
    protected $table = 'carrera_semestre';

    protected $primaryKey = 'id_carrera_semestre';

    public $timestamps = false;

    protected $fillable = [
        'cantidad_cupos',
        'cantidad_estudiantes',
        'id_carrera',
        'id_semestre',
    ];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class, 'id_semestre', 'id_semestre');
    }
}
