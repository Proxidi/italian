# Usuarios: primera etapa de consulta

Propuesta integrada con los archivos Laravel que compartiste en codigo.txt.
El repositorio CodeIgniter no se ha modificado. Este paquete se aplica a tu proyecto Laravel.

## Archivos

Nuevos:

- app/Http/Controllers/UsersReadController.php
- app/Http/Requests/Users/DirectoryRequest.php
- app/Http/Requests/Users/AccessLogRequest.php
- database/seeders/UsersReadPermissionsSeeder.php
- resources/views/usuarios/table.blade.php
- resources/views/usuarios/print.blade.php
- resources/css/users-read.css
- resources/js/users-read.js
- tests/Feature/UsersReadTest.php

Reemplazos completos, basados en las versiones adjuntas:

- routes/web.php
- app/Support/AuthenticatedNavigation.php
- vite.config.js

Una modificación puntual:

- config/authenticated-navigation.php, indicada abajo.

User.php, el controlador de autenticación, el layout, los componentes y los assets anteriores siguen siendo los compartidos.
No se crean modelos de Plaza o Bitácora: este corte solo necesita joins de lectura.

## 1. Configuración de navegación

En config/authenticated-navigation.php, inmediatamente DESPUÉS del foreach que convierte $legacyDestinations en $destinations, añade:

```php
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
```

Tiene que ir después del foreach: si va antes, el bucle sobrescribe esas entradas.
No cambies las definiciones de paneles o los demás destinos.

AuthenticatedNavigation ahora filtra los destinos cuyo permiso falta y elimina los grupos que quedan vacíos.
También aplica el filtro a la tarjeta Usuarios de Inicio. Los demás módulos pendientes conservan su presentación actual.

## 2. Autorización

Las rutas usan auth:web y el middleware nativo can. Tu configuración permission.php ya contiene register_permission_check_method=true:
Spatie registra esos permisos en el Gate de Laravel. No necesitas nuevos aliases en bootstrap/app.php ni un bypass para superadmin.
Los Form Requests vuelven a comprobar la misma capacidad antes de validar.

El seeder sincroniza exclusivamente las relaciones rol-permiso de estas dos capacidades:

| Permiso | Roles web |
|---|---|
| users.directory.view | root |
| access-log.view | root, superadmin |

Puede repetirse. Conserva los demás permisos, los roles asignados a usuarios y todas las tablas legacy.
No modifica asignaciones directas de permisos a usuarios; no se crean asignaciones directas en este paquete.

## 3. Consulta y presentación

El directorio muestra Nombre, Edad, Email, Usuario y Plaza. Usa INNER JOIN con roles y plazas como el legacy, sin seleccionar contraseñas, hashes o identificadores.
El orden inicial es el ID del usuario ascendente; el identificador se utiliza en SQL pero no se selecciona ni se muestra.
No hay alta, edición, eliminación ni controles deshabilitados para esas operaciones.

La bitácora conserva Fecha, Hora, plaza del acceso, Nombre actual del usuario y rol legacy actual.
Mantiene INNER JOIN: los accesos sin usuario/plaza/rol relacionado no aparecen, como en CodeIgniter.
El orden inicial es Fecha y Hora descendentes; el ID del acceso desempata para que la paginación sea estable.
La consulta es global y filtra Fecha por igualdad exacta. No aplica DATE() a la columna.

Buscar, ordenar, filtrar y paginar usan GET con recarga de página. Los parámetros se validan mediante Form Requests y los nombres de columnas SQL salen de listas cerradas.
El botón Restablecer elimina todos los filtros; en la bitácora vuelve al día actual.
La búsqueda de texto se aplica a las cinco columnas visibles; no añade selectores separados de plaza, usuario o rol.
La paginación es de 100 usuarios y 200 accesos.

Todas las rutas nuevas son GET; no necesitan token CSRF. El logout POST existente mantiene @csrf en sus componentes.

## 4. Exportación sin nuevas dependencias

CSV, impresión y portapapeles cubren este corte sin instalar una librería para tablas o exportación.

- Exportar CSV descarga TODOS los resultados que coinciden con la fecha/búsqueda y conserva el orden, no solo la página visible.
- El CSV usa UTF-8 con BOM y separador punto y coma. Se abre en hojas de cálculo; no es un archivo XLSX.
- Imprimir / Guardar PDF abre todos los resultados filtrados en una vista independiente. El usuario guarda el PDF desde el diálogo del navegador.
- Copiar esta página copia únicamente las filas visibles, con encabezados. Esa limitación está indicada en el botón.
- CSV y copiar neutralizan prefijos que una hoja de cálculo podría interpretar como fórmulas.

Estos mecanismos reemplazan las exportaciones de DataTables con formatos/alcances explícitos. Si Victor necesita XLSX real, PDF descargable con un diseño fijo o copiar todo el resultado, esa necesidad requiere una ampliación acotada.
El portapapeles requiere HTTPS o localhost y permiso del navegador; si falla, se muestra una alternativa.
Las exportaciones están protegidas con los mismos permisos que las pantallas.

El CSS/JS del módulo se carga únicamente en estas vistas mediante Vite y los stacks que tu layout ya contiene.
No se modifica package.json ni se añaden dependencias. La impresión utiliza el mismo CSS/JS propio.

## 5. Rendimiento y fecha actual

La paginación ocurre en SQL; no se envía el día completo al navegador para paginarlo allí.
Se seleccionan solamente las cinco columnas necesarias. CSV e impresión recorren la consulta mediante cursor; eso evita hidratar una colección Eloquent completa, aunque PDO puede seguir almacenando resultados en su buffer.
Una impresión con muchos registros también necesita memoria en el navegador.

El DDL aportado no declara un índice sobre tbl_bitacora_accesos.Fecha. Recomiendo medir con EXPLAIN y datos reales un índice sobre Fecha o un índice compuesto que ayude también al orden por Hora e ID.
No se añade ningún índice ni se cambia el esquema. Sin acceso a esa base no puedo medir el plan o asegurar el índice óptimo.

Tu config/app.php usa UTC. La fecha predeterminada usa now(), igual que el login que escribe la bitácora.
Por eso ambos son coherentes internamente. Para paridad con el servidor CodeIgniter, verifica su zona horaria real; el repositorio legacy solo declara time_reference=local.
Si el legacy opera en America/Mexico_City, alinea la zona horaria de Laravel antes de la prueba funcional. No he cambiado la configuración ni convertido fechas históricas.

## 6. Aplicación y comandos

1. Copia los archivos nuevos a Laravel y aplica los tres reemplazos completos.
2. Añade las dos entradas de navegación descritas arriba.
3. Ejecuta desde la raíz de Laravel:

```bash
sail artisan optimize:clear

sail artisan db:seed --class=UsersReadPermissionsSeeder

sail npm run build

sail artisan route:list --path=Usuarios -v

sail artisan test --filter=UsersReadTest
```

Para desarrollar con Vite, utiliza npm run dev en lugar de recompilar cada cambio.
Si todavía no instalaste las dependencias existentes, ejecuta npm ci con tu archivo lock o npm install si no tienes lock.
No necesitas composer require, publicar middleware ni ejecutar migraciones.

Ejecuta el seeder con la clase explícita. Tu DatabaseSeeder compartido aún usa la factory estándar con name/email: no forma parte de este módulo y no se modifica ni se invoca aquí.

## 7. Pruebas

UsersReadTest utiliza una conexión SQLite :memory: con nombre propio; configura sesión y cache en memoria y crea únicamente las tablas necesarias dentro de esa conexión.
No utiliza RefreshDatabase, migrate:fresh, migraciones del proyecto ni la conexión MySQL compartida.
Necesita la extensión pdo_sqlite y el tests/TestCase.php estándar de Laravel.

Si tu proyecto no tiene tests/TestCase.php, crea este archivo; si ya existe, conserva tu versión:

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
}
```

Los tests cubren invitados, matriz de roles, accesos directos y exportaciones, cinco columnas, alcance global, fecha exacta, restablecimiento, plaza registrada, rol actual, orden, paginación, validación, escape, búsqueda literal, navegación y ausencia de escrituras durante consultas.
También verifican que el seeder sea repetible y preserve otros permisos.

El esquema en memoria es un fixture de pruebas; no es una migración que debas ejecutar en MySQL.
Estos tests no prueban la collation, el modo SQL, la codificación o el rendimiento de MySQL 8.4. Comprueba búsqueda con acentos y ordenación sobre una copia MySQL para cerrar esa paridad.

Pruebas manuales adicionales:

- Abrir ambas pantallas como root y solo la bitácora como superadmin.
- Confirmar que no aparece el grupo Usuarios para los demás roles y que el acceso directo devuelve 403.
- Seleccionar una fecha, buscar, ordenar y navegar por páginas conservando la fecha.
- Restablecer y comparar con los accesos del día que también ve CodeIgniter.
- Comprobar CSV, impresión/PDF y copiar una página.
- Probar ancho móvil, foco de teclado y desplazamiento horizontal de la tabla.
- Comparar nombres con acentos, mayúsculas y caracteres especiales en MySQL.

## Verificación disponible

No se dispone aquí del proyecto Laravel ni de un ejecutable PHP, por lo que no se han ejecutado PHPUnit ni la compilación Vite de tu proyecto.
El paquete debe compilarse y probarse con los comandos anteriores en tu entorno Laravel.

