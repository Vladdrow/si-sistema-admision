<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nota extends Model
{
    protected $table = 'nota';

    protected $primaryKey = 'id_nota';

    public $timestamps = false;

    protected $fillable = [
        'nota',
        'fecha_registro',
        'id_postulante',
        'id_docente',
        'id_materia',
        'id_examen',
    ];

    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
            'fecha_registro' => 'datetime',
        ];
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'id_postulante', 'id_postulante');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }
}
