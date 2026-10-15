<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersReadSpreadsheet
{
    public function download(
        Builder $query,
        array $columns,
        string $title,
        string $filename,
    ): StreamedResponse {
        return response()->streamDownload(
            function () use ($query, $columns, $title): void {
                $spreadsheet = new Spreadsheet();

                try {
                    $spreadsheet->getProperties()
                        ->setCreator('The Italian Coffee Company')
                        ->setTitle($title);

                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle('Listado');

                    $sheet->setCellValueExplicit(
                        'A1',
                        $title,
                        DataType::TYPE_STRING,
                    );

                    $lastColumn = count($columns);

                    $sheet->mergeCells([1, 1, $lastColumn, 1]);
                    $sheet->getStyle('A1')->getFont()
                        ->setBold(true)
                        ->setSize(16);

                    $columnNumber = 1;

                    foreach ($columns as $column) {
                        $sheet->setCellValueExplicit(
                            [$columnNumber, 3],
                            $column['label'],
                            DataType::TYPE_STRING,
                        );

                        $sheet->getColumnDimensionByColumn($columnNumber)
                            ->setAutoSize(true);

                        $columnNumber++;
                    }

                    $sheet->getStyle([1, 3, $lastColumn, 3])
                        ->getFont()
                        ->setBold(true);

                    $rowNumber = 4;

                    foreach ($query->cursor() as $record) {
                        $columnNumber = 1;

                        foreach ($columns as $column) {
                            $sheet->setCellValueExplicit(
                                [$columnNumber, $rowNumber],
                                (string) ($record->{$column['field']} ?? ''),
                                DataType::TYPE_STRING,
                            );

                            $columnNumber++;
                        }

                        $rowNumber++;
                    }

                    $sheet->freezePane('A4');

                    if ($rowNumber > 4) {
                        $sheet->setAutoFilter([
                            1,
                            3,
                            $lastColumn,
                            $rowNumber - 1,
                        ]);
                    }

                    (new Xlsx($spreadsheet))->save('php://output');
                } finally {
                    $spreadsheet->disconnectWorksheets();
                }
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }
}
