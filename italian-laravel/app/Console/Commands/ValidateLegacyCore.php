<?php

namespace App\Console\Commands;

class ValidateLegacyCore extends LegacyCoreCommand
{
    protected $signature = 'legacy:validar-nucleo {--lote=500 : Filas por lote (1–2000)}';

    protected $description = 'Prevalida conexiones, bootstrap y datos del núcleo en orden, sin escrituras';
}
