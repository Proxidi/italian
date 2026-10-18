<?php

$destinations = [
    'inicio' => [
        'route' => 'inicio',
        'legacy' => 'Inicio',
    ],
];

$legacyDestinations = [
    'ventas.reporte' => 'Ventas/add2',
    'gastos.manager' => 'Remesas/indexManager',
    'inventario.captura' => 'Inventario/nuevaCaptura',
    'inventario.dlc' => 'Inventario/addDLC',
    'pedidos.manager' => 'Pedidos/manager',
    'nominas.manager' => 'Nominas/manager',

    'pedidos.recepcion' => 'Pedidos/recepcionPedido',
    'pedidos.pasteleria.manager' => 'Pedidos/listManagerPasteleria',
    'pedidos.historial.manager' => 'Pedidos/historialManager',
    'nominas.rol.manager' => 'Nominas/rolManager',
    'nominas.usuarios.manager' => 'Nominas/usuariosManager',
    'nominas.eliminados.manager' => 'Nominas/usuariosEliminadosManager',

    'ventas.panel' => 'Depositos/panel',
    'ventas.depositos' => 'Depositos',
    'ventas.analisis' => 'Depositos/analisisVentas',
    'ventas.tabla' => 'Depositos/tablaDepositos',
    'ventas.mes' => 'Depositos/comparar',
    'ventas.tickets' => 'Depositos/tickets',
    'ventas.liberar' => 'Depositos/liberarDepositos',
    'ventas.costo' => 'Ventas/tablaCostoVenta',

    'remesas.panel' => 'Remesas',
    'remesas.fgf' => 'Remesas/addRemesasAdministrador',
    'remesas.analisis' => 'Remesas/analisisRemesas',
    'remesas.generales' => 'Remesas/listRemesasGenerales',
    'remesas.stocks' => 'Remesas/stocks',

    'unidades.panel' => 'Unidades',

    'inventario.panel' => 'Inventario',
    'inventario.produccion' => 'Inventario/listAdmin',
    'inventario.fgf' => 'Inventario/listAdminF',
    'inventario.historial' => 'Inventario/historialIF',
    'inventario.configuracion' => 'Inventario/panelConfiguraciones',
    'inventario.seguimiento' => 'Inventario/seguimientoInventario',
    'inventario.maquinas' => null,

    'pedidos.panel' => 'Pedidos',
    'pedidos.add' => 'Pedidos/add',
    'pedidos.pasteleria' => 'Pedidos/addPasteleria',
    'pedidos.liberados' => 'Pedidos/historialAdmin',
    'pedidos.pasteleria.liberados' => 'Pedidos/historialPasteleria',
    'pedidos.faltantes' => 'Pedidos/faltantesPedido',

    'nominas.panel' => 'Nominas',
    'nominas.roles' => 'Nominas/roles',
    'nominas.tabla' => 'Nominas/tabla',
    'nominas.analisis' => 'Nominas/analisisNominas',
    'nominas.correccion' => 'Nominas/correccionRoles',
    'nominas.usuarios' => 'Nominas/usuariosAdministrador',
    'nominas.eliminados' => 'Nominas/usuariosEliminadosAdministrador',

    'usuarios.panel' => 'Usuarios',
    'usuarios.accesos' => 'Usuarios/listaAccesos',
    'permisos.panel' => 'Permisos',

    'productos.panel' => 'Productos',
    'productos.list' => 'Productos/list',
    'insumos.panel' => 'Insumos',
    'medidas.panel' => 'Medidas',
    'insumos.costo' => 'Insumos/getInsumosCostoVenta',

    'configuracion.panel' => 'Opciones',
    'configuracion.fechas' => 'Opciones/fechas',
    'configuracion.notificaciones' => 'Usuarios/notificaciones',

    'socio.ventas' => 'Depositos/ventasOaxaca',
    'socio.gastos' => 'Remesas/listRemesasOaxaca',
    'socio.unidades' => 'Unidades/listUnidadesOaxaca',
];

foreach ($legacyDestinations as $key => $legacy) {
    $destinations[$key] = [
        'route' => null,
        'legacy' => $legacy,
    ];
}

$destinations['usuarios.panel'] = [
    'route' => 'usuarios.index',
    'legacy' => 'Usuarios',
    'permission' => 'users.directory.view',
];

$destinations['usuarios.accesos'] = [
    'route' => 'usuarios.accesos',
    'legacy' => 'Usuarios/listaAccesos',
    'permission' => 'access-log.view',
];

foreach ([
    'productos.panel' => ['products.index', 'products.view'],
    'productos.list' => ['products.catalog.index', 'products.catalog.view'],
    'medidas.panel' => ['products.units.index', 'products.units.view'],
    'insumos.panel' => ['products.ingredients.index', 'products.ingredients.view'],
    'configuracion.panel' => ['configuration.index', 'configuration.view'],
    'configuracion.fechas' => ['configuration.dates', 'configuration.upload-window.view'],
    'configuracion.notificaciones' => ['configuration.notifications', 'configuration.notifications.view'],
] as $key => [$route, $permission]) {
    $destinations[$key] = [...$destinations[$key], 'route' => $route, 'permission' => $permission];
}

$item = static fn (string $label, string $destination, string $icon = 'circle'): array => [
    'label' => $label,
    'destination' => $destination,
    'icon' => $icon,
];

$group = static fn (string $label, string $icon, array $children): array => [
    'label' => $label,
    'icon' => $icon,
    'children' => $children,
];

$card = static function (
    string $label,
    string $destination,
    string $image,
    string $tone,
): array {
    $secondaryTexts = [
        'ventas.reporte' => 'Ventas',
        'gastos.manager' => 'Gastos',
        'inventario.captura' => 'Nueva Captura IF',
        'inventario.panel' => 'Inventarios',
        'socio.gastos' => 'Remesas',
    ];

    $footerTexts = [
        'ventas.reporte' => 'Enviar Reporte',
        'gastos.manager' => 'Gastos',
        'inventario.captura' => 'Capturar',
        'inventario.panel' => 'Inventarios',
        'socio.gastos' => 'Remesas',
    ];

    return [
        'label' => $label,
        'subtitle' => $secondaryTexts[$destination] ?? $label,
        'footer_label' => $footerTexts[$destination] ?? $label,
        'destination' => $destination,
        'image' => $image,
        'tone' => $tone,
    ];
};

$logout = [
    'label' => 'Cerrar sesión',
    'type' => 'logout',
    'icon' => 'logout',
];

$ventas = [
    $item('Depósitos', 'ventas.depositos'),
    $item('Análisis Ventas', 'ventas.analisis'),
    $item('Tabla Depósitos', 'ventas.tabla'),
    $item('Ventas x Mes', 'ventas.mes'),
    $item('Ticket Promedio - UT', 'ventas.tickets'),
];

$remesas = [
    $item('Remesas F.G.F', 'remesas.fgf'),
    $item('Análisis Remesas', 'remesas.analisis'),
    $item('Remesas Generales', 'remesas.generales'),
];

$inventario = [
    $item('Inventarios Producción', 'inventario.produccion'),
    $item('Inventarios F.G.F', 'inventario.fgf'),
];

$pedidos = [
    $item('Pedidos', 'pedidos.add'),
    $item('Pedidos Pastelería', 'pedidos.pasteleria'),
    $item('Liberados Franquicia', 'pedidos.liberados'),
    $item('Liberados Pastelería', 'pedidos.pasteleria.liberados'),
];

$administrativeCards = [
    $card('Ventas', 'ventas.panel', 'cash.png', 'blue'),
    $card('Remesas', 'remesas.panel', 'shopping.jpg', 'red'),
    $card('Unidades', 'unidades.panel', 'tea.png', 'green'),
    $card('Inventario', 'inventario.panel', 'inventory.png', 'orange'),
    $card('Pedidos', 'pedidos.panel', 'order.png', 'purple'),
];

return [
    // Conserva la precedencia del controlador Laravel actual.
    'role_panels' => [
        'superadmin' => 'superadmin',
        'root' => 'root',
        'admin' => 'manager',
        'submanager' => 'manager',
        'socio' => 'socio',
    ],

    'destinations' => $destinations,

    'common' => [
        [
            'label' => 'Mis Notificaciones',
            'icon' => 'bell',
            'unavailable_message' => 'Notificaciones pendientes de migración',
        ],
        $item('Inicio', 'inicio', 'home'),
    ],

    'panels' => [
        'manager' => [
            'sidebar' => [
                $item('Ventas', 'ventas.reporte', 'money'),
                $item('Gastos', 'gastos.manager', 'cart'),
                $item('Inventario Físico', 'inventario.captura', 'box'),
                $item('Agregar DLC', 'inventario.dlc', 'plus'),
                $group('Pedidos', 'cart', [
                    $item('Pedidos Franquicia', 'pedidos.recepcion'),
                    $item('Pedidos Pastelería', 'pedidos.pasteleria.manager'),
                    $item('H. Facturas', 'pedidos.historial.manager'),
                ]),
                $group('Nóminas', 'money', [
                    $item('Rol semanal', 'nominas.rol.manager'),
                    $item('Usuarios', 'nominas.usuarios.manager'),
                    $item('Usuarios Eliminados', 'nominas.eliminados.manager'),
                ]),
                $group('Configuración', 'settings', [$logout]),
            ],
            'cards' => [
                $card('Reporte Ventas', 'ventas.reporte', 'cash.png', 'blue'),
                $card('Capturar Gastos', 'gastos.manager', 'report2.png', 'red'),
                $card('Nueva Captura IF', 'inventario.captura', 'inventory.png', 'orange'),
                $card('Agregar DLC', 'inventario.dlc', 'report2.png', 'green'),
                $card('Pedidos', 'pedidos.manager', 'order.png', 'purple'),
                $card('Nóminas', 'nominas.manager', 'nomina.png', 'blue'),
            ],
            'title' => 'Panel de Inicio',
            'subtitle' => 'Panel de Control',
        ],

        'superadmin' => [
            'sidebar' => [
                $group('Ventas', 'money', $ventas),
                $group('Remesas', 'cart', $remesas),
                $item('Unidades', 'unidades.panel', 'cup'),
                $group('Inventario', 'box', [
                    ...$inventario,
                    $item('Historial I.F', 'inventario.historial'),
                ]),
                $group('Pedidos', 'cart', $pedidos),
                $group('Usuarios', 'users', [
                    $item('Bitácora de Accesos', 'usuarios.accesos'),
                ]),
                $logout,
            ],
            'cards' => $administrativeCards,
            'title' => 'Panel de Administración',
            'subtitle' => 'Panel de Control',
        ],

        'root' => [
            'sidebar' => [
                $group('Ventas', 'money', [
                    ...$ventas,
                    $item('Liberar Depósitos', 'ventas.liberar'),
                    $item('Costo de venta', 'ventas.costo'),
                ]),
                $group('Remesas', 'cart', [
                    ...$remesas,
                    $item('Stocks', 'remesas.stocks'),
                ]),
                $item('Unidades', 'unidades.panel', 'cup'),
                $group('Inventario', 'box', [
                    ...$inventario,
                    $item('Configuraciones', 'inventario.configuracion'),
                    $item('Seguimiento Inv.', 'inventario.seguimiento'),
                    $item('Inventario Máquinas', 'inventario.maquinas'),
                ]),
                $group('Pedidos', 'cart', [
                    ...$pedidos,
                    $item('Faltantes Pedido', 'pedidos.faltantes'),
                ]),
                $group('Nóminas', 'money', [
                    $item('Tabla Roles', 'nominas.roles'),
                    $item('Tabla Nóminas', 'nominas.tabla'),
                    $item('Análisis Nóminas', 'nominas.analisis'),
                    $item('Corrección de Roles', 'nominas.correccion'),
                    $item('Usuarios', 'nominas.usuarios'),
                    $item('Usuarios Eliminados', 'nominas.eliminados'),
                ]),
                $group('Usuarios', 'users', [
                    $item('Listado de Usuarios', 'usuarios.panel'),
                    $item('Bitácora de Accesos', 'usuarios.accesos'),
                ]),
                $group('Productos', 'cup', [
                    $item('Productos', 'productos.list'),
                    $item('Insumos', 'insumos.panel'),
                    $item('Unidades', 'medidas.panel'),
                    $item('Costo de Venta', 'insumos.costo'),
                ]),
                $item('Permisos', 'permisos.panel', 'lock'),
                $group('Configurar', 'settings', [
                    $item('Corte && DLC', 'configuracion.fechas'),
                    $item('Notificaciones', 'configuracion.notificaciones'),
                ]),
                $logout,
            ],
            'cards' => [
                ...$administrativeCards,
                $card('Nóminas', 'nominas.panel', 'nomina.png', 'blue'),
                $card('Usuarios', 'usuarios.panel', 'users.png', 'green'),
                $card('Permisos', 'permisos.panel', 'secure.png', 'red'),
                $card('Productos', 'productos.panel', 'coffe2.png', 'orange'),
                $card('Configurar', 'configuracion.panel', 'tool.png', 'purple'),
            ],
            'title' => 'Panel de Administración',
            'subtitle' => 'Panel de Control',
        ],

        'socio' => [
            'sidebar' => [
                $item('Ventas', 'socio.ventas', 'money'),
                $item('Gastos', 'socio.gastos', 'cart'),
                $item('Unidades', 'socio.unidades', 'cup'),
                $logout,
            ],
            'cards' => [
                $card('Ventas', 'socio.ventas', 'cash.png', 'blue'),
                $card('Gastos', 'socio.gastos', 'shopping.jpg', 'red'),
                $card('Unidades', 'socio.unidades', 'tea.png', 'green'),
            ],
            'title' => 'Panel de Inicio',
            'subtitle' => 'Panel de Control',
        ],
    ],
];
