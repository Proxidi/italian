<?php

namespace App\Services\Legacy;

class ImportReport
{
    public array $counts = ['insertados' => 0, 'identicos' => 0, 'rechazados' => 0, 'conflictos' => 0, 'pendientes' => 0];

    public array $samples = [];

    public array $sequence = [];

    public int $sourceCount = 0;

    public int $targetCount = 0;

    public function record(string $status, int $id, string $reason = ''): void
    {
        $this->counts[$status]++;
        if ($reason !== '' && count($this->samples) < 25) {
            // Only numeric IDs and fixed reason codes; never row values or SQL.
            $this->samples[] = ['id' => $id, 'motivo' => $reason];
        }
    }

    public function hasProblems(): bool
    {
        return $this->counts['rechazados'] + $this->counts['conflictos'] > 0;
    }
}
