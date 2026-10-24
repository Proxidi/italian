<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait RemittanceFixtures
{
    protected function remittanceRows(): array
    {
        return [
            'familias_gastos' => [['id' => 1, 'clave' => 'GDF', 'nombre' => 'Fabricación']],
            'grupos_gastos' => [['id' => 2, 'familia_id' => 1, 'nombre' => 'Abarrotes']],
            'grupos_captura_gastos' => [['id' => 5, 'nombre' => 'Managers']],
            'formas_pago' => [['id' => 1, 'nombre' => 'Efectivo']],
            'conceptos_gastos' => [['id' => 14, 'grupo_id' => 2, 'insumo_id' => 71, 'unidad_id' => 7, 'grupo_captura_id' => 5, 'nombre' => 'Concepto sintético', 'stock_minimo' => '1.25', 'stock_maximo' => '120', 'bandera_stocks' => 0]],
            'gastos' => [$this->expenseRow()],
        ];
    }

    protected function expenseRow(array $overrides = []): array
    {
        return array_replace([
            'id' => 100, 'usuario_id' => 1, 'plaza_id' => 1, 'concepto_gasto_id' => 14, 'forma_pago_id' => 1,
            'cantidad' => '2', 'importe' => '100', 'modificacion' => 1, 'nueva_cantidad' => '0', 'antigua_cantidad' => '0',
            'fecha' => '2026-10-19', 'estado' => 1, 'notas' => '', 'corte' => 0, 'archivos' => 'comprobante.jpg,', 'folio' => 'SYN-100',
        ], $overrides);
    }

    protected function seedRemittanceRows(): void
    {
        foreach ($this->remittanceRows() as $table => $rows) {
            DB::table($table)->insert($rows);
        }
    }
}
