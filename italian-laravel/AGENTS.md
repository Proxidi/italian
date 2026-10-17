# Guía local — Laravel en migración

Aplicar las reglas de [AGENTS.md raíz](../AGENTS.md) y consultar
[el contexto auditado](../docs/migration-context.md). Este subdirectorio es la
aplicación destino; `../italian-legacy/` es la referencia funcional y visual.

## Alcance antes de preparar herramientas

- Para lectura, auditoría estática o documentación no se necesita ejecutar PHP,
  Composer ni la aplicación. No instalar herramientas, iniciar servicios,
  modificar infraestructura, importar datos ni ejecutar migraciones o seeders.
- En tareas funcionales autorizadas, comprobar primero las herramientas y scripts
  existentes; preparar solo lo necesario para el flujo solicitado. No asumir que
  falta una herramienta solo porque no esté en el PATH de la shell.
- No ejecutar automáticamente `composer setup` ni `DatabaseSeeder`: contienen
  escrituras y restos scaffold; inspeccionar el contexto y el destino de BD antes
  de cualquier operación sobre datos.

## Dependencias e instrucciones generadas

La guía anterior prescribía preparación automática e instalación de Laravel Boost
antes de cambios de aplicación. Esa preparación no es un requisito de lectura: Boost
no está declarado en el manifiesto/lockfile auditado. No ejecutar
`composer require laravel/boost --dev` ni `php artisan boost:install` por iniciativa
propia, ni reemplazar estas reglas con instrucciones generadas. Una futura
adopción requiere una tarea que autorice ese cambio de dependencias y alcance.

La existencia de controladores, rutas o vistas Breeze no acredita una función
migrada. Revisar su conexión al arranque y al modelo legacy antes de usarlos.
Preservar temporalmente SHA-1 en el login activo; el esquema futuro y los permisos
se rigen por las reglas raíz. Distinguir inspección de código, prueba ejecutada y
validación con datos reales en cada entrega.
