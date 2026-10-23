<?php

namespace App\Console\Commands;

class BootstrapCorePermissions extends LegacyCoreCommand
{
    protected $signature = 'legacy:inicializar-permisos {--dry-run : Validar sin modificar catálogo, matriz ni cachés}';

    protected $description = 'Inicializa una sola vez los roles y permisos del núcleo en una instalación nueva';
}
