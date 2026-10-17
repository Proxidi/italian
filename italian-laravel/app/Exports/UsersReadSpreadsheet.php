<?php

namespace App\Exports;

use Generator;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class UsersReadSpreadsheet extends DefaultValueBinder implements FromGenerator, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithProperties, WithTitle
{
    public function __construct(private Builder $query, private array $columns, private string $heading) {}

    public function generator(): Generator
    {
        yield [$this->heading];
        yield [''];
        yield array_column($this->columns, 'label');

        // Bounded DB batches, preserving all filters and the requested ordering.
        // The XLSX workbook itself still requires memory proportional to its cells.
        foreach ((clone $this->query)->lazy(500) as $record) {
            yield array_map(fn (array $column): string => (string) ($record->{$column['field']} ?? ''), $this->columns);
        }
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        // Text remains text, including formula-looking values and leading zeroes.
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }

    public function title(): string
    {
        return 'Listado';
    }

    public function properties(): array
    {
        return ['creator' => 'The Italian Coffee Company', 'title' => $this->heading];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $lastColumn = count($this->columns);
            $sheet->mergeCells([1, 1, $lastColumn, 1]);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle([1, 3, $lastColumn, 3])->getFont()->setBold(true);
            $sheet->freezePane('A4');
            if ($sheet->getHighestRow() > 3) {
                $sheet->setAutoFilter([1, 3, $lastColumn, $sheet->getHighestRow()]);
            }
        }];
    }
}
