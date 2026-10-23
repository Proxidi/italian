<?php

namespace App\Console\Commands;

class ImportLegacyAccesos extends LegacyCoreCommand
{
    protected $signature = 'legacy:importar-accesos {--dry-run : Validar sin escrituras} {--lote=500 : Filas por lote (1–2000)}';

    protected $description = 'Importa por lotes la bitácora con fecha, hora y plaza históricas';
}
