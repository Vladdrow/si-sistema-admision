<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    protected $table = 'grupo';

    protected $primaryKey = 'id_grupo';

    public $timestamps = false;

    protected $fillable = [
        'nombre_grupo',
        'cantidad_estudiantes',
        'id_semestre',
    ];

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class, 'id_semestre', 'id_semestre');
    }

    public function postulanteGrupos(): HasMany
    {
        return $this->hasMany(PostulanteGrupo::class, 'id_grupo', 'id_grupo');
    }

    public function grupoHorarios(): HasMany
    {
        return $this->hasMany(GrupoHorario::class, 'id_grupo', 'id_grupo');
    }
}
