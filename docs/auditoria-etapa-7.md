# Auditoría de BD — Etapa 7

Fecha: 2026-10-24. Rama: `migracion-bd`. Base revisada: `9ff62d2f` y cambios locales
de esta auditoría. Sin commit/push, despliegue, cambios de permisos ni datos reales.

## A. Resumen ejecutivo

Las etapas 0–6 constituyen una base funcional nativa para continuar el desarrollo.
No se encontraron consultas operativas a `tbl_*` ni conexiones legacy desde los
controladores, modelos, consultas, middleware, vistas o exportaciones migrados.
Los tests funcionales nativos inutilizan explícitamente legacy y rechazan SQL
`tbl_*` mediante un listener. Los importadores conservan su conexión independiente.

Se revisaron rutas, proveedor, acciones, Form Requests, consultas, modelos,
policies, navegación, exports, seeders, nueve migrations y cuatro familias de
importadores. Se añadieron casos de cambio del origen durante escrituras,
actualización incremental con datos y agregación sin N+1; se corrigieron dos
expectativas de tests atrasadas y se optimizó la comparación de Remesas.

La importación local de 135441 filas y sus tres agregados es información reportada
por el responsable, no acceso nuestro a esa BD. No se usaron cuentas/contraseñas
reales ni se cargó el dump en MySQL. Las comparaciones con CI son estáticas más
casos sintéticos independientes, no una ejecución paralela de CI en producción.
No se midió el historial real ni se ensayó aquí un cutover/restauración completo.

## B. Matriz de módulos

| Módulo | Legacy operativo | Evidencia | Estado / observaciones |
| --- | --- | --- | --- |
| Login/logout | Ninguno | AuthenticationTest; PasswordCompatibility/AuthenticateUsuario; Chromium | Aprobado con observación: retiro gradual de SHA-1 y configuración HTTPS de despliegue |
| Inicio/shell | Ninguno | NativeCoreTest, cinco roles y Chromium 1366/390 | Aprobado; bienvenida/pendientes son demo |
| Usuarios | Ninguno | UsersReadTest/NativeCoreTest: alta, edición, roles, plazas, filtros, XLSX/PDF/Copy | Aprobado; borrado deshabilitado |
| Bitácora | Ninguno | fecha/plaza histórica, exportaciones, errores de login sin acceso exitoso | Aprobado; rol mostrado es actual, no histórico |
| Configurar | Ninguno | NativeConfigurationTest/ConfigurationTest/importador/Chromium | Aprobado con observación: cardinalidad global protegida en app, no singleton estructural |
| Medidas/Insumos | Ninguno | ProductsUnitsTest/IngredientsTest/NativeProductsTest/Chromium | Aprobado; alta Insumos pendiente, conversiones conservadas |
| Productos/Fórmulas | Ninguno | ProductCatalogTest/NativeProductsTest/importador/Chromium | Aprobado; duplicados históricos se preservan y no permiten edición ambigua |
| Costo Pedido | Ninguno | CostOfSaleTest/NativeProductsTest/Chromium | Aprobado; costo almacenado independiente |
| Costo Remesas | Ninguno | NativeRemittancesTest/RemittancesImportTest/Chromium | Aprobado con observación: solo historia, sin administración Gastos |
| Importación | Legacy legítimo | LegacyCoreImport, ConfigurationImport, ProductsImport, RemittancesImport | Aprobado con observaciones: fuente congelada, diferencias destino bloquean reimportación |

### Inventario clasificado de referencias

El alcance de búsqueda fue `app`, `routes`, `config`, `resources`, `database`,
tests, documentación y assets públicos, excluyendo motores vendor/node_modules
como código propio. No se hizo sustitución textual.

| Clase | Archivos / referencias | Resolución |
| --- | --- | --- |
| A | No se encontraron lecturas/escrituras legacy operativas en los módulos migrados | Verificación estática más tests con legacy inaccesible |
| B | Services/Legacy/{ImportConnections,CoreImporter,CoreBootstrap,ConfigurationImporter,ProductsImporter,RemittancesImporter}.php; Console/Commands/Legacy* y comandos ImportLegacy*/ValidateLegacy*; config/database.php, config/legacy_import.php | Conservar. Aquí pertenecen tbl_*, rol_id y conexión legacy |
| B | DatabaseSeeder/CorePermissionsSeeder invocan bootstrap explícito | No son parte del login. No ejecutar seeders para sincronizar producción; bootstrap requiere la fuente aun si ya estaba inicializado |
| C | SaveUserRequest prohíbe rol_id, Password, Hash, sha1_id; create.blade.php reconoce esos errores | Defensa/contrato HTTP, no columnas operativas |
| C | Comentario de migration inicial, fixtures, pruebas, docs y CompatibleConversion | Referencias históricas legítimas |
| C | PasswordCompatibility SHA-1; Laravel Facade Hash | Compatibilidad operativa autorizada sobre usuarios.password; Hash facade NO es la columna legacy Hash |
| D | config/authenticated-navigation.php y AuthenticatedNavigation conservan nombres de destinos legacy inertes; URLs /Inicio, /Productos, /Medidas, /Insumos, /Opciones y /Usuarios son rutas Laravel | No llaman a CI ni conceden autorización por coincidencia de URL |
| D | insumos.categoria_pedido_id/categoria_quebranto_id y contratos Corte/notificaciones/Gastos | Catálogos/consumidores futuros; IDs preservados sin inventar FKs a tablas pendientes |
| E | Scaffold de recuperación/confirmación/perfil sin registro en rutas/auth.php; LegacyUserRolesSeeder lanza excepción de retiro | No activar ni borrar por estética; el stub impide sincronizaciones antiguas accidentales |
| E | public/assets/Classes/PHPExcel y resto de librerías copiadas | 217 PHP bajo public/assets. No se encontraron consumidores operativos en app/routes/resources; no se cargan para exports Laravel. Candidato a limpieza separada y endurecimiento del servidor; no se afirma una explotación comprobada ni se borran árboles indiscriminadamente |

Los logos/fondos, iconos copiados y fuentes locales son archivos estáticos propios;
usarlos no implica conexión al servidor CI. Login referencia Montserrat local vía
Vite. No se rediseñaron tipografías ni assets. No existe App\\Models\\User como
proveedor paralelo activo; el proveedor Eloquent web es Usuario, con morph usuario.

## C. Hallazgos

| ID / severidad | Evidencia y reproducción | Impacto / resolución |
| --- | --- | --- |
| H1 Medio, corregido | ProductsImporter::run volvía a leer la fuente antes de insertar pero no al terminar. Test beforeExecuting cambia Costo_Venta al insertar productos_insumos | Podía confirmar un catálogo que ya difiriera de la fuente. Nueva huella antes del commit revierte todas las tablas |
| H2 Bajo, corregido | CoreSchemaTest esperaba solo tablas de etapa 2; ExampleTest esperaba 200 en / | Suite completa daba dos falsos negativos. Inventario actualizado y redirección al login comprobada |
| H3 Medio, optimizado | RemittancesImporter::scan normalizaba/validaba de nuevo cada fila destino, aunque la fuente ya estaba validada y MySQL retorna DECIMAL canónico | Trabajo PHP duplicado al validar destino poblado. Comparación exacta campo a campo, incluido NULL vs vacío; prueba adversarial de precisión/flags/textos/fecha |
| H4 Alto, pendiente de preparación operativa | Reservas productos_importacion_v1/remesas_importacion_v1 ligan huellas; importador núcleo rechaza password/roles distintos | Un snapshot nuevo no se puede aplicar ciegamente sobre una BD Laravel con cambios operativos. Requiere reconciliación revisada o destino nuevo autorizado; nunca borrar reservas ni restaurar SHA-1/roles para forzar |
| H5 Medio, pendiente | CoreImporter confirma por lotes y limita por maxId; no ofrece una instantánea global multitabla ni huella durable como Productos/Remesas | Congelar fuente y destino o importar copia consistente. Los lotes previos pueden persistir tras un fallo; la reanudación valida, no es rollback global. No prometer sincronización continua ni detección de todo cambio concurrente del núcleo |
| H6 Medio, pendiente | TabularSpreadsheet usa lazy(500), pero workbook y Dompdf crecen con el documento; prueba XLSX cruza 505 filas | Se evita materializar el query, no toda la memoria del documento. Falta ensayo representativo de exports de bitácora completa y límites de operación en producción |
| H7 Bajo, pendiente | Bibliotecas PHP antiguas en public/assets y scaffold sin rutas | Superficie/desorden de publicación; revisar eliminación y permitir PHP únicamente en front controller en configuración de producción. No hubo cambio de infraestructura |
| H8 Informativo | Configuración cardinal validada bajo bloqueo; números de días 1–127 en requests/servicio, unsignedTinyInteger admite más por SQL directo | Mantener importadores y writers autorizados; una FK o tipo SQL no sustituye esa regla. No se añadió una CHECK a migration aplicada |

No se reprodujo un hallazgo crítico de autorización, corrupción de datos o
dependencia legacy operativa. Esto no acredita ausencia universal de defectos.

## D. Correcciones y pruebas nuevas

1. H1: tres líneas al final de la transacción ProductsImporter; prueba
   `test_source_changed_during_destination_insert_rolls_back_every_catalog`.
   Al mutar origen durante INSERT se exige fallo y cero medidas/fórmulas destino.
2. H2: corregir expectativas existentes sin modificar rutas ni migrations.
3. H3: conservar toda la validación fuente y comparar cada columna destino en su
   representación MySQL canónica. No se omiten huellas, FK, grants ni transacciones.
   `test_canonical_comparison_checks_decimals_dates_flags_and_empty_text_without_writes`
   altera once campos y exige conflicto, incluida una diferencia de 1e-24.
4. `test_incremental_migrations_preserve_populated_core_and_products`: levantar
   esquema anterior, poblar usuario/rol/productos/fórmula y aplicar etapa 6;
   datos anteriores idénticos, nuevas tablas vacías. En SQLite y MySQL.
5. `test_many_ingredients_and_plazas_use_one_aggregate_query`: 21 insumos × 2
   plazas devuelven 42 filas usando un SQL, como protección frente a N+1.
6. Perfilado opcional sintético de las tres fases del importador, sin BD real.

No se tocaron migrations históricas ni se añadieron migrations. Se verificó orden
de creación/FK y rollback únicamente en las bases descartables. Las pivots Spatie
mantienen sus PKs/índices y FKs de roles/permisos; model_id polimórfico no tiene
FK a usuarios por convención Spatie, y requiere verificaciones de integridad.

### Seguridad y reglas contrastadas

AuthenticateUsuario verifica SHA-1 con hash_equals o hasher moderno por algoritmo,
rehash solo tras credencial correcta y vuelve a verificar hash bajo bloqueo.
Plaza/único rol web deben existir; auditoría y sesión son obligatorias; error
invalida guard y sesión. PasswordCompatibility es retirable y no desactiva la
verificación global del hasher. Hasher configurado Argon2id; no downgrade.
Usuario oculta password/remember_token; SaveUser usa transacción, rol web y plaza
bloqueados. QueryException con bindings de contraseña no se propaga desde login
ni escritura de Usuarios. Se conserva throttle 5/min y POST/CSRF para logout.
Concesiones/revocaciones sobreviven al bootstrap registrado; no hay Gate::before
para superadmin ni rol_id operativo. La matriz tiene 5 roles y 22 permisos.

CI es referencia de filtros/campos/semántica, no de UI ni de implementación.
Remesas usa SUM(Importe)/SUM(Cantidad) por plaza/insumo, no promedio simple, no
filtro Estado ni conversiones. No compras/ambos totales cero→0; importe con cantidad
cero→No calculable. Pedido usa costo almacenado. Semana ISO corregida y tres
decimales de presentación permanecen. Las fórmulas duplicadas no se fusionan.
Conversiones siguen siendo cadenas de operaciones, nunca eval de PHP.
Los exports conservan filtros/orden y celdas de texto protegen fórmulas; PDF escapa.
Los formatos AJAX usan las mismas consultas GET y mantienen fallback HTTP.

No se cambian las discrepancias conocidas: Ventas fuerza 30 días en un recorrido,
el consumidor de plazo de Inventario no está acreditado, alta Insumos rota queda
pendiente, borrados peligrosos deshabilitados y notificaciones reales pendientes.

## E. Dependencias pendientes

- Funcionalidad: Pedidos, Inventario, Ventas/Corte real, administración de Gastos,
  notificaciones reales. El mock no los acredita ni sustituye.
- Negocio: definición del alta Insumos, resolución de duplicados históricos,
  eliminaciones, normalización de unidades cuando corresponda y calendario real
  de integración con Corte. No se cambian por conveniencia de esta auditoría.
- Datos: conciliación de snapshots contra fuente actual, hashes/roles/costos y
  configuraciones editados; comprobantes físicos/folios y sus permisos/rutas.
- Producción: H4–H7, zona UTC efectiva frente a zona de negocio, HTTPS/cookies,
  secretos, permisos mínimos, capacidad de exportación y ensayo de restauración.
- Opcional: limpieza de assets/scaffold y búsquedas más grandes. Los LIKE '%texto%'
  no se vuelven indexados con un índice B-tree genérico; no añadirlos a ciegas.

## F. Rendimiento

Referencia del responsable con 135223 gastos: validar ~4 min, importar 7m44,
validar importado 8m21. No es nuestro benchmark ni evidencia de igualdad de hardware.

Medición local MySQL 8.4.13/PHP 8.5.11, 5000 gastos sintéticos, lote 500:

| Fase | Antes (s) | Después (s) |
| --- | ---: | ---: |
| Validar destino vacío | 4.059 | 3.781 |
| Importar | 15.870 | 9.115 |
| Validar destino poblado | 10.938 | 8.912 |

Pico 56.5 MiB; preparación de fixtures no incluida en cada fase. Una ejecución
por versión y contención del host: no prometer porcentajes universales ni extrapolar
linealmente a la BD real. Comparación fuente/destino, validación por campo y varias
lecturas de huella explican trabajo mayor al validar destino lleno. También influye
latencia de conexión/red y almacenamiento; no se midieron esos factores del equipo
del responsable. Se conserva cada control de seguridad. No se reescribe el importer.

CostOfSale ya agrega en un SQL con índices fecha/plaza/concepto; la nueva prueba
protege su cardinalidad. Fórmulas usa eager loading. Inicio carga plaza/roles y
capacidades, no historia de Gastos/bitácora; no se identificó escaneo histórico
en ese recorrido. No se midió SLA del dashboard ni EXPLAIN sobre datos reales.
Antes de producción: medir 135223+ filas representativas, distribución por fecha,
plaza y concepto, EXPLAIN ANALYZE, p50/p95 y exports; documentar hardware/red.

## G. Resultados de pruebas

Laravel del lock: 13.36.0; Spatie 8.3.0; PHP ejecutado 8.5.11; MySQL 8.4.13.
Runner PHP con memory_limit=512M; servicios sin red externa, MySQL en tmpfs.
SQLite :memory:, MySQL italian_stage01_tests / italian_stage02_source_tests /
italian_stage02_target_tests / italian_stage03_tests, todas descartables.

Resultados finales:

| Ejecución | Seleccionados | Ejecutados | Aserciones | Fallos/errores | Omitidos |
| --- | ---: | ---: | ---: | ---: | ---: |
| Suite completa modo SQLite | 359 | 285 | 6370 | 0/0 | 74 |
| Suite completa con CORE_IMPORT_MYSQL=1, CORE_FUNCTIONAL_MYSQL=1, CORE_SCHEMA_MYSQL=1 | 359 | 358 | 12575 | 0/0 | 1 |
| Perfilado, por ejecución (antes/después) | 1 | 1 | 11 | 0/0 | 0 |

La segunda ejecución incluye tests aislados que siempre usan SQLite; las suites
de esquema/core nativo/importadores opt-in sí utilizan MySQL. No afirmar que los
359 casos se ejecutan exclusivamente sobre MySQL. No sumar suites superpuestas.
Las omisiones del modo SQLite son 73 tests de importación MySQL y el perfilado;
este último se ejecutó por separado dos veces. Tiempos suite: 22.041 s y 137.418 s;
pico reportado 125 MiB en ambas. No se generó cobertura de líneas con Xdebug.

Antes de las correcciones, la suite tenía dos expectativas obsoletas fallidas.
El primer experimento incremental detectó un problema de limpieza del propio
test (dos batches de migrations); se corrigió su teardown descartable. Un intento
inicial de perfilado coincidió con otra suite sobre el mismo esquema sintético,
falló durante setup y se descartó: las mediciones válidas se realizaron después,
sin otra suite escribiendo en ese par de bases. Ningún resultado fallido anterior
se usa como verificación final aprobada. Pint --test sobre siete PHP y
git diff --check completan la verificación; no cambian assets ni se requiere build.

Chromium: Login erróneo/correcto, carga/ojito, bienvenida/demo, navegación/sidebar,
menú/perfil/logout, alta/edición Usuarios y búsqueda Usuarios/bitácora en 1366/390;
Configurar días/fecha; Remesas búsqueda/plaza/semana/orden/atrás/adelante/Pedido;
fallback sin JS mediante teclado y formularios HTTP. Productos también verificó
abrir/guardar fórmula, conversión y costo con fetch en ambas resoluciones.
Sin errores JS ni overflow.
El script antiguo de no-JS esperaba estabilidad del botón animado y expiró al
hacer click; el envío con Enter se comprobó con el script actual de Remesas.
No se presenta ese timeout del automatismo como un bug reproducido de autenticación.
No se modificó la UI; reduced-motion se comprobó en el shell. Exports grandes
probados cruzan el lote de 500, no el PDF de 63628 accesos completos.

## H. Checklist preliminar de cutover y reversión (sin ejecutar)

1. Javi debe aprobar alcance del corte: módulos migrados y módulos que continúan
   en CI. Definir un único escritor por dominio y evitar reportes con historias
   divergentes. Migrar solo los módulos aprobados no cierra todo Italian.
2. Inventariar producción actual, versiones, volúmenes, fechas máximas, usuarios,
   permisos, archivos físicos y respaldos cifrados. Preservar .env/APP_KEY con
   acceso restringido; no copiarlos a documentación o Git.
3. Crear backup verificado de ambas bases y archivos. Ensayar restauración en otra
   instancia, medir RTO/RPO y cotejar conteos, FKs, agregados y hashes sin exponerlos.
4. Elegir snapshot consistente de TODAS las tablas relacionadas y detener writers
   CI/Laravel, workers, cron e integraciones. Artisan down no detiene procesos CI.
5. Conciliar diferencias entre copia vieja y producción actual ANTES de importar.
   Marcadores y passwords/roles/costos editados deben generar conflicto, no
   borrarse para forzar. Un destino nuevo requiere autorización y un plan para
   conservar cualquier escritura Laravel válida; no hacer migrate:fresh al actual.
6. Aplicar migrations incrementales revisadas. Orden importación: plazas;
   bootstrap solo instalación sin inicializar; usuarios; accesos; configuración;
   productos; historial Remesas. Dry-run y validaciones por etapa; no db:seed
   genérico para restablecer permisos. Conservar reportes sin datos sensibles.
7. Transferir comprobantes físicos y revisar paths/ACLs; conservar nombres/folios
   no copia el archivo. Publicar solo public/, PHP únicamente front controller;
   APP_DEBUG=false, HTTPS, cookies seguras, APP_KEY estable, cuentas separadas con
   privilegios mínimos, protección de backups/dumps y monitoreo de errores.
8. Antes de abrir: validar todas las filas/FKs/secuencias, casos de cinco roles,
   login SHA-1/Argon, acceso histórico, cambios Usuario, Configurar, Fórmulas,
   Pedido/Remesas en varias semanas/plazas, descargas y logout/CSRF.
9. Registrar punto de no retorno y responsables. Antes de nuevas escrituras Laravel,
   se puede devolver tráfico a CI congelado tras verificar sesiones/origen.
10. Después de nuevas escrituras Laravel, volver a CI pierde o duplica datos sin
    reconciliación. Congelar ambos, respaldar el estado nuevo y decidir con Javi
    una recuperación validada; no copiar tablas en sentido inverso automáticamente.

## I. Sincronización local

Creado: `docs/auditoria-etapa-7.md`.
Modificados: `docs/migration-context.md`, Services/Legacy/ProductsImporter.php,
Services/Legacy/RemittancesImporter.php y tests/Feature/{CoreSchemaTest,
ExampleTest,NativeRemittancesTest,ProductsImportTest,RemittancesImportTest}.php.
Sin archivos eliminados, nuevas migrations, cambios de AGENTS, dependencias,
.env, italian-legacy, italian.sql, permisos o datos reales.

Tras revisar y publicar/sincronizar únicamente migracion-bd:

```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan migrate:status
./vendor/bin/sail php -d memory_limit=512M vendor/bin/phpunit
```

No hay migrations nuevas que aplicar ni motivo para reimportar datos locales ya
correctos. Las pruebas opt-in MySQL requieren preparar **bases descartables** y
cuentas sintéticas descritas en AGENTS; nunca apuntarlas a italian/italian_laravel.
Para benchmark: CORE_IMPORT_MYSQL=1 REMITTANCES_PROFILE=1 y opcional
REMITTANCES_PROFILE_ROWS (1<filas<=135223), ejecutar RemittancesImportTest con
--filter test_profile_historical_batches. Ejecutar secuencialmente, no dos suites
de importación sobre el mismo par de bases. No usarlo en un esquema persistente.

## J. Dictamen

1. Etapas 0–6: integradas funcionalmente, con observaciones documentadas.
2. Módulos migrados: independientes de legacy durante operación normal; los
   importadores siguen requiriendo la fuente. Esto no declara migrado todo Italian.
3. Pedidos: base apta para comenzar análisis/desarrollo de su alcance, sin asumir
   migradas las reglas externas de inventario, ventas o administración de gastos.
4. Producción: NO aprobada todavía. Faltan aceptación funcional consolidada del
   responsable/cliente, conciliación de fuente actual, ensayo de cutover/restore,
   archivos y validación de capacidad y configuración del entorno real.
