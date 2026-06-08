<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postulante extends Model
{
    protected $table = 'postulante';

    protected $primaryKey = 'id_postulante';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_postulante',
        'colegio_procedencia',
        'ciudad',
        'estado_admision',
        'codigo_libreta',
        'codigo_titulo',
        'id_carrera_primera_opc',
        'id_carrera_segunda_opc',
        'id_carrera_admitido',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_postulante', 'id_persona');
    }

    public function carreraPrimera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera_primera_opc', 'id_carrera');
    }

    public function carreraSegunda(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera_segunda_opc', 'id_carrera');
    }

    public function carreraAdmitido(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'id_carrera_admitido', 'id_carrera');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_postulante', 'id_postulante');
    }

    public function postulanteGrupo(): HasMany
    {
        return $this->hasMany(PostulanteGrupo::class, 'id_postulante', 'id_postulante');
    }
}
