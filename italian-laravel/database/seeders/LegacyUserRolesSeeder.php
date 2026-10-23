<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class LegacyUserRolesSeeder extends Seeder
{
    public function run(): void
    {
        throw new RuntimeException('Seeder legacy retirado: use legacy:inicializar-permisos y legacy:importar-usuarios. No se sincronizan roles operativos desde tbl_usuarios.');
    }
}
