<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrupoHorario extends Model
{
    protected $table = 'grupo_horario';

    protected $primaryKey = 'id_grupo_horario';

    public $timestamps = false;

    protected $fillable = [
        'fecha_asignacion',
        'id_grupo',
        'id_detalle',
        'id_docente',
        'id_aula',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
        ];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(DetallePlantillaHorario::class, 'id_detalle', 'id_detalle');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'id_aula', 'id_aula');
    }
}
