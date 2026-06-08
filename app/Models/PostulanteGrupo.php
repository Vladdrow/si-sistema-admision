<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostulanteGrupo extends Model
{
    protected $table = 'postulante_grupo';

    protected $primaryKey = 'id_postulante_grupo';

    public $timestamps = false;

    protected $fillable = [
        'fecha_asignacion',
        'id_grupo',
        'id_postulante',
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

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'id_postulante', 'id_postulante');
    }
}
