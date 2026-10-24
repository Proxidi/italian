<?php

namespace App\Console\Commands;

class ValidateLegacyRemittances extends ImportLegacyRemittances
{
    protected $signature = 'legacy:validar-remesas';

    protected $description = 'Valida historial, relaciones, decimales, IDs y conflictos de Remesas sin escribir';
}
