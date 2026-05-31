<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carrera';

    protected $primaryKey = 'id_carrera';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
    ];
}
