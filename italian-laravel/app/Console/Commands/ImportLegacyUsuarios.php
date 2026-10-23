<?php

namespace App\Console\Commands;

class ImportLegacyUsuarios extends LegacyCoreCommand
{
    protected $signature = 'legacy:importar-usuarios {--dry-run : Validar sin escrituras} {--lote=500 : Filas por lote (1–2000)}';

    protected $description = 'Importa usuarios y su único rol Spatie atómicamente, preservando SHA-1';
}
