<?php

namespace App\Console\Commands;

class ValidateLegacyProducts extends ImportLegacyProducts
{
    protected $signature = 'legacy:validar-productos';

    protected $description = 'Prevalida el conjunto de Productos, IDs, decimales, relaciones y conflictos sin escribir';
}
