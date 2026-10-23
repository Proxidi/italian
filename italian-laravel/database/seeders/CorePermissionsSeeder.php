<?php

namespace Database\Seeders;

use App\Services\Legacy\CoreBootstrap;
use Illuminate\Database\Seeder;

class CorePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $status = app(CoreBootstrap::class)->run();
        $this->command?->info('Bootstrap del núcleo: '.$status);
    }
}
