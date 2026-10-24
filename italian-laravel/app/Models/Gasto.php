<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    protected $table = 'gastos';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:24', 'importe' => 'decimal:24', 'nueva_cantidad' => 'decimal:24', 'antigua_cantidad' => 'decimal:24', 'fecha' => 'immutable_date'];
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(ConceptoGasto::class, 'concepto_gasto_id');
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
