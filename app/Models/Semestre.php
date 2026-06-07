<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semestre extends Model
{
    protected $table = 'semestre';

    protected $primaryKey = 'id_semestre';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    public function cuposCarrera(): HasMany
    {
        return $this->hasMany(CarreraSemestre::class, 'id_semestre', 'id_semestre');
    }

    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class, 'id_semestre', 'id_semestre')
            ->orderBy('numero_examen');
    }
}
