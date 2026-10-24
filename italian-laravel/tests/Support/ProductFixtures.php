<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait ProductFixtures
{
    protected function productRows(): array
    {
        return [
            'medidas' => [['id' => 7, 'nombre' => 'Gramos']],
            'categorias_insumos' => [['id' => 3, 'nombre' => 'Producción']],
            'familias_productos' => [['id' => 9, 'nombre' => 'Café']],
            'insumos' => [[
                'id' => 71, 'nombre' => 'Café sintético', 'clave_pedido' => 'CAF', 'categorias_insumos_id' => 3,
                'unidad_id' => 7, 'unidad_pedido' => 7, 'unidad_final' => 7, 'inv_produccion' => 1, 'inv_fisico' => 1,
                'pedido' => 1, 'pedido_pasteleria' => 0, 'categoria_pedido_id' => 1, 'categoria_quebranto_id' => 3,
                'analisis_cv' => 1, 'costo_venta' => '153.12', 'orden_captura_inventario' => 1,
            ]],
            'conversiones_insumos' => [[
                'id' => 82, 'nombre' => 'Café sintético', 'insumo_id' => 71,
                'bodega' => '*,25', 'piso' => '-,615,/,1000', 'total' => '*,1', 'dlc' => '/,1000', 'pedido' => '*,150', 'cvi' => '*,1', 'cvg' => '/,1000',
            ]],
            'productos' => [['id' => 91, 'nombre' => 'Americano sintético', 'clave' => 'SYN-1', 'familia_id' => 9, 'estado' => 1]],
            'productos_insumos' => [['id' => 112, 'producto_id' => 91, 'insumo_id' => 71, 'cantidad' => '7.7']],
        ];
    }

    protected function seedProductRows(): void
    {
        foreach ($this->productRows() as $table => $rows) {
            DB::table($table)->insert($rows);
        }
    }
}
