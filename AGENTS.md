# Reglas de trabajo — Migración CodeIgniter a Laravel

## Alcance

Este repositorio contiene dos sistemas relacionados:

- `italian-legacy/`: sistema original en CodeIgniter 3. Es la referencia funcional y de reglas de negocio; su apariencia es un antecedente, no el diseño objetivo. No modificar su código funcional salvo solicitud explícita.
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
- la información y los alcances funcionales relevantes, aunque cambie su presentación.

Sin embargo, **no debe copiar la implementación técnica de CodeIgniter** cuando Laravel ofrezca una forma más nativa, simple, segura, rápida o mantenible de resolver la misma necesidad.

El objetivo es conservar las capacidades del producto con arquitectura Laravel y una experiencia visual renovada.

## Dirección visual y UX

Por solicitud del Ing. Víctor, la UI/UX puede rediseñarse: debe ser profesional, atractiva, moderna y amigable, con profundidad visual y microinteracciones cuando aporten valor. Priorizar accesibilidad, rendimiento y consistencia; no imitar necesariamente la apariencia de CodeIgniter. La propuesta está parcialmente implementada y **pendiente de validación final del Ing. Víctor**, no aprobada.

Conservar información, acciones, reglas de negocio y permisos requeridos del legacy, sin exigir la misma distribución visual.

Reutilizar los componentes visuales establecidos. Las tarjetas de navegación de Inicio, Productos y Configurar usan `x-dashboard.card`: encabezado de color distintivo, pie claro, esquinas redondeadas, profundidad y hover/press/entrada con `prefers-reduced-motion`. No aplicarlas por defecto a tablas, formularios o estadísticas. Los modales de pendientes actuales son una demostración visual, sin datos operativos reales.

Se puede renovar la implementación y presentación de tablas, formularios, búsquedas, paginación, exportaciones e interacciones preservando sus contratos funcionales y autorización.

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

Dirección confirmada por el Ing. Javi: Laravel tendrá tablas nuevas con nombres de negocio en español, minúsculas y `snake_case`, migradas progresivamente por módulo mediante migrations y modelos con relaciones Eloquent apropiadas. Conservar convenciones técnicas Laravel/Spatie cuando simplifiquen integraciones. La tabla autenticable definitiva es `usuarios`. Conservar los IDs legacy cuando sea viable e importar datos reales mediante procesos reproducibles; reservar seeders preferentemente para datos iniciales/configuración. El objetivo es abandonar las tablas `tbl_*` en la operación normal, con hashes modernos y Spatie. Los contratos y el plan de transición de cada módulo siguen pendientes; ejecutar migrations/importaciones requiere alcance y destino autorizados.

El núcleo funcional de la etapa 3 usa `Usuario` como proveedor Eloquent de `web`, con `usuarios`, `plazas`, `bitacora_accesos` y Spatie. El responsable reportó la importación local correcta; nuestras pruebas usan datos sintéticos, no verifican su BD real. Configurar opera con configuracion_corte_dlc y configuracion_notificaciones desde la etapa 4; Productos aún espera su adaptación de persistencia: no consultar sus tablas para autenticar o cargar Inicio. La etapa 2 incorpora `legacy:validar-nucleo`, bootstrap de Spatie y `legacy:importar-*` con `--dry-run`; conservar conflictos sin sobrescribir datos, hashes ni roles operativos. La matriz inicial se registra una sola vez en `inicializaciones_sistema`; los seeders habituales no deben revertir concesiones/revocaciones administrativas ni sustituir importadores de datos reales. La conexión principal debe usar `italian_laravel`; `legacy` apunta a `italian` solo para importadores, con una cuenta SELECT independiente. No importar el dump completo al destino ni ejecutar migrations/seeders sobre `legacy`. Verificar configuración efectiva, bases distintas y grants antes de cualquier escritura. No redirigir controladores funcionales a la fuente para compensar pantallas aún no adaptadas.

Mientras se trabaja contra la BD legacy:

- no renombrar anticipadamente tablas o columnas sin el diseño aprobado;
- evitar dispersar nombres físicos legacy por toda la aplicación;
- preferir modelos Eloquent y consultas encapsuladas cuando representen bien el dominio;
- no crear abstracciones innecesarias solo para adivinar cambios futuros;
- considerar nombres de tablas/columnas legacy como detalles de persistencia temporales.

Cuando llegue el nuevo esquema, adaptar la capa de persistencia sin alterar innecesariamente controladores, vistas y reglas de negocio.

## Convivencia temporal con CodeIgniter

CodeIgniter conserva sus datos y SHA-1 en `italian`; Laravel no escribe en ese origen. Comprobar conexiones efectivas antes de afirmar que un despliegue concreto comparte una instancia de BD.

En el núcleo nuevo:

- `PasswordCompatibility` permite temporalmente leer los SHA-1 importados en `usuarios.password`, sin casts automáticos ni transformación durante la importación.
- Solo después de verificar una contraseña correcta se reemplaza ese SHA-1 en la BD nueva por un hash moderno. El valor se vuelve a comprobar con bloqueo para proteger cambios concurrentes.
- El hasher predeterminado es Argon2id (64 MiB, dos iteraciones, un hilo), manteniendo verificaciones de algoritmos. BCrypt/Argon anteriores se verifican con su driver nativo antes de modernizarlos.
- Altas y cambios explícitos de contraseña generan exclusivamente hashes modernos. Edición con contraseña vacía conserva el hash más reciente; nunca enviar el hash al formulario.
- No volver a escribir SHA-1 ni restablecer contraseñas/roles operativos desde importadores o seeders.

Los avisos reales de Corte y pendientes siguen siendo requisitos. Por autorización de la etapa 3, Login ya no consulta `tbl_reporte_ventas`: el contrato de fecha de hace dos días/plaza y sus campos históricos `msj`/`hoy` quedan documentados para una integración posterior con Ventas migrado. El mock visual no los sustituye ni representa datos operativos.

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
- `tbl_usuarios.rol_id` solo corresponde al mapeo de importación. La fuente operativa es Spatie; no sincronizar roles durante Login. El formulario administrativo asigna un único rol web mediante `role_id` como input, sin columna duplicada en `usuarios`.
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
- revisar visualmente la dirección de diseño vigente y la conservación de capacidades del legacy;
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
