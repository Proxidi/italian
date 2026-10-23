<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Initial configuration only. Real data belongs to legacy:importar-*.
        $this->call(CorePermissionsSeeder::class);
    }
}
