<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acceso extends Model
{
    protected $table = 'bitacora_accesos';

    public $timestamps = false;

    protected $fillable = ['fecha', 'hora', 'usuario_id', 'plaza_id'];

    protected function casts(): array
    {
        // Keep historical DATE/TIME values literal, without timezone conversion.
        return ['usuario_id' => 'integer', 'plaza_id' => 'integer'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function plaza(): BelongsTo
    {
        return $this->belongsTo(Plaza::class, 'plaza_id');
    }
}
