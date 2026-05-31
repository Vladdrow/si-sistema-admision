<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroAdmision extends Model
{
    protected $table = 'parametro_admision';

    protected $primaryKey = 'id_parametro';

    public $timestamps = false;

    protected $fillable = [
        'fecha_inicio_inscripcion',
        'fecha_cierre_inscripcion',
        'fecha_cierre_notas',
        'monto_pago',
        'max_estudiante_grupo',
        'nota_minima_aprobacion',
        'max_grupos_docente',
        'tiempo_expiracion_pago',
        'id_semestre',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio_inscripcion' => 'datetime',
            'fecha_cierre_inscripcion' => 'datetime',
            'fecha_cierre_notas' => 'datetime',
            'monto_pago' => 'decimal:2',
            'nota_minima_aprobacion' => 'decimal:2',
        ];
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class, 'id_semestre', 'id_semestre');
    }
}
