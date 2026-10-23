<?php

namespace App\Console\Commands;

class ImportLegacyPlazas extends LegacyCoreCommand
{
    protected $signature = 'legacy:importar-plazas {--dry-run : Validar sin escrituras} {--lote=500 : Filas por lote (1–2000)}';

    protected $description = 'Importa plazas conservando IDs y sin sobrescribir cambios operativos';
}
