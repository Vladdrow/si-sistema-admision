<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalAdministrativo extends Model
{
    protected $table = 'personal_administrativo';

    protected $primaryKey = 'id_personal';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_personal',
        'cargo',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_personal', 'id_persona');
    }
}
