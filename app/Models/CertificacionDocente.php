<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificacionDocente extends Model
{
    protected $table = 'certificacion_docente';

    protected $primaryKey = 'id_certificacion';

    public $timestamps = false;

    protected $fillable = [
        'institucion',
        'nivel',
        'id_docente',
    ];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }
}
