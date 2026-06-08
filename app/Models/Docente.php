<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    protected $table = 'docente';

    protected $primaryKey = 'id_docente';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'titulo_profesional',
        'tiene_maestria',
        'tiene_diplomado',
        'codigo_rda',
    ];

    protected function casts(): array
    {
        return [
            'tiene_maestria' => 'boolean',
            'tiene_diplomado' => 'boolean',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_docente', 'id_persona');
    }

    public function certificaciones(): HasMany
    {
        return $this->hasMany(CertificacionDocente::class, 'id_docente', 'id_docente');
    }

    public function materiasHabilitadas(): BelongsToMany
    {
        return $this->belongsToMany(
            Materia::class,
            'docente_materia_habilitada',
            'id_docente',
            'id_materia'
        );
    }
}
