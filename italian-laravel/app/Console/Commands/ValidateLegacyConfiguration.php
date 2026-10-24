<?php

namespace App\Console\Commands;

class ValidateLegacyConfiguration extends ImportLegacyConfiguration
{
    protected $signature = 'legacy:validar-configuracion';

    protected $description = 'Prevalida ambas configuraciones sin escribir ni modificar cachés o secuencias';
}
