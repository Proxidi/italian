# Reglas de trabajo — Migración CodeIgniter a Laravel

## Alcance

Este repositorio contiene dos sistemas relacionados:

- `italian-legacy/`: sistema original en CodeIgniter 3. Es la referencia funcional y visual; no modificar su código funcional salvo solicitud explícita.
- `italian-laravel/`: nueva implementación en Laravel 13. Es el destino de los cambios funcionales autorizados.

Si la estructura real del repositorio difiere de esos nombres, inspeccionarla antes de editar.

Estas reglas definen el alcance de la migración. Las guías locales no autorizan por sí solas cambios en el legacy, instalación de herramientas ni ampliación de una tarea documental a una tarea funcional. Las instrucciones explícitas del usuario prevalecen.

## Principio central de la migración

La aplicación Laravel debe conservar, con la mayor fidelidad posible:

- lo que el usuario ve;
- la información que se muestra;
- las acciones que puede realizar;
- las reglas de negocio;
- los permisos y alcances efectivos;
- la apariencia y organización visual relevantes del legacy.

Sin embargo, **no debe copiar la implementación técnica de CodeIgniter** cuando Laravel ofrezca una forma más nativa, simple, segura, rápida o mantenible de resolver la misma necesidad.

El objetivo no es producir “CodeIgniter escrito en Laravel”, sino el mismo producto visible y funcional sobre una arquitectura nueva.

## Fidelidad visual

La UI de Laravel debe mantenerse lo más fiel posible al legacy.

Todo dato, sección, botón, control o información visible relevante del legacy debe conservarse en Laravel, salvo decisión explícita del equipo.

No convertir la migración en un rediseño sin autorización.

Se puede cambiar por completo la implementación interna de una tabla, formulario, búsqueda, paginación, exportación o interacción si el resultado visible y funcional se mantiene.

Cuando una acción todavía no esté migrada, puede mostrarse deshabilitada e inerte para preservar la fidelidad visual.

## Libertad de implementación

No copiar por costumbre:

- controladores, modelos o consultas legacy;
- acoplamientos entre módulos;
- coincidencias de URL usadas como permisos;
- sesiones con datos redundantes;
- jQuery, Bootstrap, AdminLTE o DataTables solo porque existan en CodeIgniter;
- dependencias globales antiguas;
- nombres o estructura de la base de datos legacy;
- peculiaridades accidentales del sistema viejo.

Antes de reproducir un mecanismo legacy, preguntar qué necesidad de negocio resuelve y elegir la solución Laravel más apropiada.

## Stack destino

Esta lista expresa el objetivo del proyecto. Distinguir versiones objetivo, requisitos de manifiestos, versiones bloqueadas y versiones realmente ejecutadas; consultar `docs/migration-context.md` para el estado comprobado.

- Laravel 13.
- PHP 8.5.
- MySQL 8.4.
- Laravel Sail / Docker.
- Blade + Vite.
- Autenticación web con guard `web` y sesiones nativas.
- Autorización con `spatie/laravel-permission`.
- Validación mediante Form Requests.
- PHPUnit para pruebas automatizadas.

Usar CSS propio y JavaScript nativo cuando sean suficientes. No introducir Passport, Sanctum, Bootstrap, AdminLTE, jQuery o DataTables como solución general sin una necesidad técnica real.

## Base de datos: estado temporal y arquitectura objetivo

La base legacy actual es una **restricción temporal**, no el diseño objetivo de Laravel.

Javi indicó que la base de datos será migrada y mejorada, incluyendo cambios de nombres de tablas y posiblemente de columnas. Es muy probable que desaparezca el prefijo `tbl_`; por ejemplo, `tbl_usuarios` probablemente pasará a `usuarios`. El esquema definitivo aún debe confirmarse.

Mientras se trabaja contra la BD legacy:

- no renombrar anticipadamente tablas o columnas sin el diseño aprobado;
- evitar dispersar nombres físicos legacy por toda la aplicación;
- preferir modelos Eloquent y consultas encapsuladas cuando representen bien el dominio;
- no crear abstracciones innecesarias solo para adivinar cambios futuros;
- considerar nombres de tablas/columnas legacy como detalles de persistencia temporales.

Cuando llegue el nuevo esquema, adaptar la capa de persistencia sin alterar innecesariamente controladores, vistas y reglas de negocio.

## Convivencia temporal con CodeIgniter

El código Laravel actual está adaptado al esquema legacy para la convivencia con CodeIgniter. Comprobar las conexiones efectivas antes de afirmar que un despliegue concreto comparte la misma instancia de base de datos.

`tbl_usuarios.Password` usa SHA-1 porque CodeIgniter todavía depende de ese formato.

Mientras exista esta convivencia:

- Laravel debe validar temporalmente SHA-1;
- El login NO debe rehashear ni reemplazar `tbl_usuarios.Password`;
- Las altas y los cambios explícitos de contraseña autorizados deben escribir temporalmente SHA-1. En edición, dejar el campo vacío conserva la contraseña; nunca enviar el hash existente al formulario;
- no usar cast `hashed`, `Hash::make()`, `Hash::needsRehash()` ni migración automática de contraseña durante login.

Esta compatibilidad no forma parte de la arquitectura final. La migración a un hash moderno debe ocurrir cuando el nuevo esquema y el retiro del login legacy lo permitan.

## Roles y permisos

Mapeo temporal confirmado:

- `1` Dueño → `superadmin`
- `2` Manager → `admin`
- `3` Gerente → `root`
- `4` SubManager → `submanager`
- `5` Socio → `socio`

Reglas:

- `admin` y `submanager` son roles distintos aunque compartan parte de la UI.
- No crear un bypass global para `superadmin`.
- Mientras exista convivencia, `tbl_usuarios.rol_id` sigue siendo la fuente legacy de rol y debe mantenerse sincronizado con Spatie cuando corresponda.
- No usar `rol_id` como nuevo mecanismo de autorización Laravel.
- Laravel debe expresar capacidades mediante permisos semánticos Spatie.

## Navegación y autorización

En CodeIgniter, navegación y autorización están separadas:

- sidebar/dashboard: principalmente hardcodeados por `rol_id`;
- autorización: `Backend_lib::control()`, `tbl_menus`, `tbl_permisos` y excepciones específicas.

No importar mecánicamente `tbl_menus` o `tbl_permisos` a Spatie.

En Laravel:

- ocultar un enlace nunca sustituye autorización server-side;
- rutas y acciones deben comprobar permisos;
- la navegación debe reflejar esos mismos permisos cuando corresponda;
- los roles pueden definir una estructura base de panel, pero las capacidades concretas deben depender de permisos.

## Rutas y módulos

Preservar el resultado funcional, no necesariamente URLs ni acoplamientos internos históricos.

Mantener una ruta legacy solo si forma parte relevante de la compatibilidad visible o externa.

Migrar por responsabilidades funcionales y necesidades de negocio, no por archivos o controladores CodeIgniter.

## Decisiones transversales

Antes de introducir una dependencia o arquitectura reutilizable en varios módulos —exportaciones, importaciones, PDF, archivos, autorización, almacenamiento, tablas, etc.—:

1. inspeccionar los usos relevantes en todo el legacy;
2. inspeccionar el estado actual de Laravel;
3. decidir la solución a nivel de proyecto;
4. evitar generalizar a partir de un único módulo.

Para cambios locales, inspeccionar solo el contexto necesario.

## Exportaciones

Las integraciones instaladas y adoptadas para las exportaciones de Usuarios son:

- Excel/XLSX → `maatwebsite/excel` (4.0.4 en el lockfile).
- PDF tabular → `barryvdh/laravel-dompdf` (3.1.2 en el lockfile).

PhpSpreadsheet y Dompdf siguen siendo sus motores transitivos. La integración no
implica por sí misma una mejora de rendimiento. Leer consultas por lotes evita
materializar todos los registros de BD, pero el workbook XLSX y el render PDF
siguen consumiendo memoria proporcional al documento. Conservar alcance, orden,
formatos y protección de fórmulas. No actualizar dependencias para coincidir con
una documentación antigua; comprobar siempre manifiesto, lockfile y código real.

Las exportaciones deben:

- conservar los controles y resultados visibles requeridos por el legacy;
- respetar permisos y filtros;
- excluir columnas de acciones cuando corresponda;
- proteger contra inyección de fórmulas;
- escapar correctamente datos en PDF;
- usar endpoints autorizados del lado servidor cuando sea apropiado.

## Pruebas

Añadir pruebas proporcionales al riesgo y comportamiento del cambio.

Antes de considerar terminado un módulo:

- ejecutar sus pruebas específicas;
- compilar Vite si cambian assets;
- revisar visualmente la fidelidad con el legacy;
- comprobar manualmente descargas o flujos externos cuando corresponda.

La suite conserva pruebas scaffold que pueden no representar el sistema actual. No implementar funcionalidades ajenas al alcance solo para hacer pasar tests obsoletos; primero decidir si esos tests deben adaptarse, reemplazarse o eliminarse.

## Seguridad y datos

- No modificar datos legacy innecesariamente.
- Mantener autorización server-side.
- Validar inputs mediante Form Requests y listas cerradas cuando corresponda.
- Evitar SQL dinámico basado directamente en input.
- Escapar salida HTML.
- Mantener CSRF en operaciones mutables.
- No introducir borrado físico de usuarios sin decisión explícita.
- No alterar esquema o índices sin justificarlo y considerar su impacto sobre el legacy mientras siga activo.

## Documentación viva

Consultar `docs/migration-context.md` antes de modificar módulos relacionados.

Toda afirmación marcada como pendiente debe verificarse contra el código o la BD real antes de usarla como base.

En auditorías estáticas o tareas documentales, leer el código sin arrancar servicios, importar/poblar bases ni ejecutar migraciones o seeders. Una BD vacía no bloquea esa revisión. Lo que requiera datos, ejecución o inspección visual permanece pendiente; no presentar resultados de pruebas anteriores como validación actual. No cambiar infraestructura ni dependencias para una tarea que solo requiere leer el repositorio.

Actualizar `docs/migration-context.md` después de:

- completar una migración funcional;
- cambiar una decisión arquitectónica;
- introducir una dependencia transversal;
- recibir una definición nueva de Javi;
- descubrir una regla de negocio importante;
- confirmar cambios del nuevo esquema de base de datos.

No inventar estado del proyecto. Si algo no está confirmado, documentarlo como pendiente o con su nivel de certeza.
