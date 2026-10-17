# Contexto de migración — CodeIgniter a Laravel

## 1. Propósito y criterio actual

Este documento conserva decisiones, restricciones, hallazgos y estado de la migración de The Italian Coffee Company.

El repositorio combinado contiene:

- CodeIgniter legacy como referencia funcional y visual;
- Laravel como nueva aplicación objetivo.

La definición más reciente de Javi es:

> Laravel debe conservar con la mayor fidelidad posible lo que el usuario ve, la información mostrada, las acciones disponibles y las reglas de negocio del legacy; por detrás, código, arquitectura, herramientas y base de datos pueden y deben rediseñarse para trabajar de forma nativa, segura, rápida y mantenible en Laravel.

Esto implica dos reglas simultáneas:

1. **fidelidad visible y funcional**: no eliminar ni rediseñar arbitrariamente lo que el legacy muestra;
2. **libertad técnica interna**: no copiar mecanismos CodeIgniter cuando Laravel ofrezca una solución mejor.

El legacy sirve para descubrir requisitos del producto, no como arquitectura que deba reproducirse.

---

## 2. Stack

### Legacy

- CodeIgniter 3.1.9.
- PHP 7.4.
- MySQL.
- Base histórica `italian`.
- Bootstrap 3, AdminLTE, jQuery, DataTables y otras dependencias globales.

### Laravel

- Laravel 13.
- PHP 8.5.
- MySQL 8.4.
- Laravel Sail / Docker.
- Blade + Vite.
- `spatie/laravel-permission`.
- Autenticación web con guard `web` y sesiones Laravel.
- Form Requests.
- PHPUnit.

Verificar siempre versiones exactas contra `composer.json`, `composer.lock` y el contenedor real.

---

## 3. Nueva dirección para la base de datos

Javi confirmó que la migración incluirá una **migración/rediseño de base de datos**, no solo reutilización permanente del esquema CodeIgniter.

Objetivos mencionados:

- mejorar la estructura;
- cambiar nombres de tablas;
- probablemente eliminar el prefijo `tbl_`;
- posiblemente renombrar algunas columnas;
- mantener los datos que sigan siendo necesarios.

### Nombre `usuarios`

Javi indicó que `tbl_usuarios` pasará probablemente a:

```text
usuarios
```

Esto se considera aproximadamente **90 % seguro**, pero todavía no debe tratarse como esquema final hasta recibir la definición formal.

No asumir automáticamente que todas las demás tablas conservarán exactamente sus columnas actuales ni que todo cambio será solo de nombres.

### Consecuencia arquitectónica

El esquema legacy debe tratarse como una restricción temporal de persistencia.

Mientras se sigue desarrollando antes de recibir el esquema definitivo:

- usar modelos Eloquent cuando representen bien las entidades;
- evitar repetir nombres físicos de tablas por todo el código;
- encapsular consultas complejas en lugares coherentes;
- no inventar una capa de abstracción enorme solo para anticipar cambios desconocidos;
- no crear migraciones definitivas copiando ciegamente el esquema legacy;
- mantener tests que permitan detectar regresiones cuando cambien tablas/columnas.

Un cambio como `tbl_usuarios → usuarios` debería afectar principalmente la capa de persistencia si el código está bien encapsulado.

Los cambios de columnas pueden requerir más adaptación, pero son manejables mediante modelos, accessors/casts, consultas centralizadas y refactors cubiertos por tests.

---

## 4. Convivencia temporal de CodeIgniter y Laravel

Actualmente ambos sistemas siguen trabajando con la base legacy.

Esto condiciona temporalmente varias decisiones que **no forman parte de la arquitectura objetivo**.

### Contraseñas

CodeIgniter usa:

- tabla `tbl_usuarios`;
- columna `Password`;
- SHA-1.

Mientras CodeIgniter continúe autenticando contra ese esquema:

- Laravel valida temporalmente SHA-1;
- Laravel no modifica ni rehashea `tbl_usuarios.Password`;
- no usar cast `hashed`;
- no usar `Hash::make()`, `Hash::needsRehash()` ni migración automática durante login.

Cuando exista el nuevo esquema y se retire la dependencia de CodeIgniter, deberá diseñarse la migración a hashes modernos.

---

## 5. Roles

Mapeo actual:

| `rol_id` legacy | Nombre legacy | Rol Spatie |
|---:|---|---|
| 1 | Dueño | `superadmin` |
| 2 | Manager | `admin` |
| 3 | Gerente | `root` |
| 4 | SubManager | `submanager` |
| 5 | Socio | `socio` |

Decisiones:

- `submanager` es un rol independiente.
- No fusionar `admin` y `submanager` aunque compartan UI.
- No crear bypass global para `superadmin`; Dueño no posee todos los permisos legacy.
- Mientras exista convivencia, `tbl_usuarios.rol_id` continúa siendo la fuente legacy de rol.
- Laravel usa Spatie para autorización.
- Cuando se implemente edición de roles durante convivencia, `rol_id` y Spatie deberán actualizarse coherentemente, preferiblemente en la misma transacción.

### Seeder temporal

Se implementó `LegacyUserRolesSeeder` usando `syncRoles()` para sincronizar desde `rol_id` hacia Spatie durante desarrollo/transición.

No debe convertirse en el flujo normal de producción para editar usuarios.

**Verificar estado real contra el repositorio Laravel actual.**

---

## 6. Autenticación legacy

Archivos relevantes:

- `application/controllers/Seguridad.php`
- `application/models/Mdl_seguridad.php`
- `application/models/Mdl_usuarios.php`
- `application/models/Mdl_ventas.php`
- `application/controllers/Inicio.php`

Flujo observado:

1. recibe username/password;
2. aplica SHA-1;
3. autentica al usuario;
4. registra acceso;
5. carga datos de sesión;
6. redirige a `/Inicio`.

La sesión legacy contiene más información que la necesaria para una sesión Laravel moderna.

Laravel debe obtener identidad desde `Auth::guard('web')` y consultar información adicional solo cuando corresponda.

### Bitácora de login

El legacy registra al menos:

- Fecha;
- Hora;
- `usuario_id`;
- `plaza_id`.

Este comportamiento visible/funcional debe conservarse mientras siga siendo un requisito del sistema.

### Modal/aviso de corte

Existe lógica legacy relacionada con `modal`, `msj`, `hoy` y consultas de ventas alrededor del login.

El flujo completo todavía debe caracterizarse antes de migrarlo.

---

## 7. Autenticación Laravel

Según el historial ya se implementaron:

- `App\Models\User` extendiendo `Authenticatable`;
- uso temporal de `tbl_usuarios`;
- `LoginRequest`;
- `AuthenticatedSessionController`;
- guard `web`;
- sesiones nativas;
- login mediante Auth;
- regeneración de sesión;
- logout POST;
- invalidación de sesión;
- regeneración de CSRF;
- `guest:web` para login;
- `auth:web` para áreas autenticadas;
- throttle aproximado de 5 intentos/minuto;
- validación SHA-1 temporal.

Archivos relevantes:

- `app/Models/User.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `database/seeders/LegacyUserRolesSeeder.php`
- `routes/web.php`

El código real del repositorio tiene prioridad sobre este resumen.

---

## 8. Autorización legacy

Componentes principales:

- `application/libraries/Backend_lib.php`
- `application/models/Mdl_backend.php`
- `tbl_roles`
- `tbl_menus`
- `tbl_permisos`
- controles hardcodeados en controladores/vistas.

### Navegación y autorización no son lo mismo

#### Navegación

Sidebar/dashboard dependen principalmente de condiciones hardcodeadas por `rol_id`.

Modificar `tbl_permisos` no implica que el menú cambie automáticamente.

#### Autorización

`Backend_lib::control()` relaciona la ruta actual con `tbl_menus`/`tbl_permisos`.

El sistema histórico presenta:

- coincidencias parciales de rutas;
- entradas duplicadas;
- rutas/acciones obsoletas;
- acciones internas;
- acciones implementadas fuera de `tbl_menus`;
- excepciones AJAX;
- condiciones adicionales por rol.

`Leer` funciona en la práctica más como “puede ejecutar esta acción” que como un CRUD literal de lectura.

### Consecuencia para Laravel

No importar mecánicamente `tbl_menus` y `tbl_permisos` a Spatie.

Caracterizar cada módulo y crear permisos semánticos, por ejemplo:

- `users.directory.view`
- `access-log.view`
- `medidas.view`
- `medidas.update`

Las rutas y acciones deben aplicar autorización server-side mediante esos permisos.

---

## 9. SubManager / rol 4

Hallazgos previos:

- rol 4 no tenía filas normales en `tbl_permisos` en los datos analizados;
- comparte visualmente panel con Manager;
- aparece relacionado en estructuras de gastos como `tbl_grupos_roles_gastos`.

Conclusión:

- no asumir que SubManager hereda permisos de Manager;
- no fusionar los roles;
- caracterizar cada flujo antes de asignar permisos Laravel.

---

## 10. Fidelidad visual como requisito

Javi confirmó expresamente que Laravel debe mantenerse **lo más fiel posible visualmente a CodeIgniter**.

Regla:

> Todo lo relevante que muestre el legacy debe seguir mostrándose en Laravel, aunque por detrás el código, las consultas, las librerías y la BD funcionen de otra manera.

Por tanto:

- no convertir una migración en un rediseño;
- conservar datos, columnas, botones, etiquetas, jerarquía y distribución relevantes;
- mantener controles aún no migrados como deshabilitados si hace falta para conservar la pantalla;
- una herramienta Laravel diferente es válida siempre que conserve el resultado visible y funcional.

Ejemplo: si el legacy muestra una tabla mediante DataTables, Laravel no necesita usar DataTables. Puede usar Blade, paginación/query builder/Eloquent/JS propio u otra solución apropiada, pero debe conservar la información, controles y apariencia requeridos.

---

## 11. `/Inicio` legacy

Selección de panel por rol:

| Rol legacy | Vista/panel |
|---:|---|
| 1 Dueño | `superadmin` |
| 2 Manager | `admin` |
| 3 Gerente | `root` |
| 4 SubManager | `admin` |
| 5 Socio | `socio` |

Esto define el panel visual, no equivalencia de permisos.

Rasgos observados:

- Dueño/Gerente muestran “Panel de Administración”.
- Subtítulo “Panel de Control”.
- Header usa principalmente `Nombre`.
- Dropdown muestra avatar, usuario, plaza, estado y acciones.
- Sidebar/dashboard varían por rol.

---

## 12. `/Inicio` Laravel

Según el historial se implementó una navegación reutilizable.

Archivos reportados:

- `config/authenticated-navigation.php`
- `app/Support/AuthenticatedNavigation.php`
- `resources/views/layouts/app.blade.php`
- componentes de header/sidebar/footer/icon
- dashboard/cards
- `resources/views/inicio/index.blade.php`
- `resources/css/authenticated.css`
- `resources/js/authenticated.js`

Decisiones conocidas:

- panel base por rol;
- opciones filtrables por permisos;
- destinos no migrados pueden quedar deshabilitados;
- nombre corto desde `Nombre` con fallback a `Usuario`;
- nombre completo puede incluir apellidos;
- plaza actual desde `tbl_usuarios.plaza_id → tbl_plazas.id → tbl_plazas.Nombre` mientras siga el esquema legacy;
- sidebar tipo acordeón;
- scroll independiente entre sidebar/contenido;
- sin necesidad de Bootstrap/AdminLTE/jQuery en la nueva implementación.

**Verificar estado actual contra la rama/repo Laravel real.**

---

## 13. Modelo de navegación Laravel

Modelo deseado:

```text
rol
→ define panel / estructura base

permisos
→ definen capacidades concretas
→ protegen rutas
→ controlan visibilidad cuando corresponde
```

Ejemplo actual:

```text
root
→ users.directory.view
→ access-log.view

superadmin
→ access-log.view
→ NO users.directory.view
```

Esto evita la desincronización legacy entre navegación y autorización.

---

## 14. Usuarios — primera etapa

Primera etapa acordada: solo lectura.

Incluye:

- Listado de Usuarios.
- Bitácora de Accesos.

No incluye todavía:

- alta;
- edición;
- eliminación;
- deshabilitación;
- cambios de rol;
- cambios de contraseña;
- notificaciones;
- perfil.

### Listado legacy

Columnas:

- Nombre
- Edad
- Email
- User Name
- Plaza
- Editar
- Eliminar

Es global; no se filtra por plaza.

Editar/Eliminar pueden permanecer visibles pero deshabilitados hasta migrar esas acciones.

### Bitácora legacy

Columnas:

- Fecha
- Hora
- Plaza
- Usuario
- Rol

Características:

- fecha exacta;
- default al día actual;
- plaza desde el acceso registrado;
- rol mostrado desde el rol actual del usuario relacionado, no un snapshot histórico.

---

## 15. Usuarios — Laravel

Según el historial se implementaron:

- `UsersReadController`;
- `DirectoryRequest`;
- `AccessLogRequest`;
- permisos `users.directory.view` y `access-log.view`;
- rutas de listado, bitácora y exports;
- búsqueda server-side;
- ordenamiento;
- paginación;
- protección XSS;
- búsqueda con wildcards tratados literalmente;
- navegación basada en permisos.

### Matriz de permisos actual

- `root`
  - `users.directory.view`
  - `access-log.view`
- `superadmin`
  - `access-log.view`
- `admin`
  - ninguno de esos dos
- `submanager`
  - ninguno de esos dos
- `socio`
  - ninguno de esos dos

No existe bypass global para `superadmin`.

### Paginación reportada

- Usuarios: 100 por página.
- Bitácora: 200 por página.

### Seeder

`UsersReadPermissionsSeeder` asigna estos permisos a los roles correctos sin eliminar permisos no relacionados.

Verificar todo contra el código actual antes de modificarlo.

---

## 16. Fidelidad visual de Usuarios

La primera versión Laravel se veía demasiado moderna/diferente respecto al legacy.

Se acordó aproximarla mucho más a las capturas originales.

Objetivo:

- encabezado compacto;
- panel blanco;
- botones Excel/PDF/Copy pequeños;
- buscador alineado como legacy;
- tabla densa;
- paginación similar;
- Agregar/Editar/Eliminar visibles pero deshabilitados cuando aún no estén migrados;
- sin necesidad de adoptar AdminLTE/DataTables internamente.

Esta decisión queda reforzada por la instrucción posterior de Javi: **la fidelidad visual es requisito**.

---

## 17. Exportaciones legacy

Investigación global encontró:

- DataTables Buttons cargado globalmente;
- JSZip;
- pdfmake;
- `buttons.html5`;
- aproximadamente 24 inicializadores centrales en `assets/js/tables.js`;
- alrededor de 26 vistas con configuraciones propias.

Módulos con patrón de exportación incluyen:

- Usuarios;
- Perfiles;
- Notificaciones/Opciones;
- Ventas;
- Depósitos;
- Remesas/Gastos;
- Inventario;
- Pedidos;
- Productos/Insumos/Unidades;
- Nóminas.

Patrón común:

```js
dom: 'Bfrtip',
buttons: [
    { extend: 'excelHtml5', ... },
    { extend: 'pdfHtml5', ... },
    { extend: 'copy', ... }
]
```

Características:

- Excel genera `.xlsx` real.
- PDF descarga `.pdf`.
- Copy copia contenido de tabla.
- títulos, columnas y tamaño de página varían por pantalla.
- algunas tablas excluyen acciones;
- otras exportan visibles;
- otras usan defaults.

PHPExcel legacy se usa principalmente para leer/importar archivos, no para generar esos reportes tabulares.

---

## 18. Importaciones Excel legacy

Existen importaciones relevantes en:

- Ventas;
- Pedidos;
- Inventario.

Incluyen casos no triviales como:

- lectura de celdas concretas (`G7`, `E6`);
- recorrido de filas/celdas;
- procesamiento de archivos de corte.

Por eso la solución de Excel se decidió de forma transversal y no solo por Usuarios.

---

## 19. Excel/PDF en Laravel

Decisión global actual:

### Excel / XLSX

`maatwebsite/excel`

Usa PhpSpreadsheet internamente y cubre exportaciones/importaciones de forma más integrada con Laravel.

### PDF

`barryvdh/laravel-dompdf`

Para listados tabulares simples y PDFs basados en Blade.

PDFs visualmente complejos deben evaluarse por módulo.

### Estado Composer reportado

Dependencias directas esperadas:

```json
"barryvdh/laravel-dompdf": "^3.1",
"maatwebsite/excel": "^4.0"
```

`phpoffice/phpspreadsheet` y `dompdf/dompdf` se instalaron inicialmente de forma directa y luego se retiraron de `composer.json`; deben permanecer solo como transitivas si las requieren las integraciones anteriores.

La instalación con `--with-all-dependencies` actualizó también dependencias compatibles, incluido Laravel aproximadamente de `13.34.0` a `13.36.0`.

Verificar contra `composer.json`/`composer.lock` actuales.

---

## 20. Contrato de exportaciones

La implementación técnica puede diferir del legacy, pero el usuario debe conservar los controles/resultados visibles que correspondan.

Para cada módulo:

- Excel descarga `.xlsx` real.
- PDF descarga `.pdf`.
- Copy conserva el comportamiento necesario de la tabla.
- filtros activos deben respetarse.
- permisos deben comprobarse server-side.
- columnas exportables se definen explícitamente.
- evitar exportar acciones salvo que tenga sentido.
- proteger contra fórmulas de Excel.
- escapar contenido PDF.
- probar autorización, filtros, MIME, filename y descarga.

---

## 21. Pruebas actuales

`UsersReadTest` fue ejecutado exitosamente antes de la reorganización:

- 16 tests;
- 111 assertions;
- todos pasando.

Cubre, entre otros:

- invitados;
- matriz de roles;
- exports;
- columnas;
- alcance global;
- fecha exacta;
- orden;
- paginación;
- validación;
- búsqueda;
- XSS;
- wildcards literales;
- navegación;
- ausencia de escrituras;
- idempotencia del seeder.

### Tests scaffold obsoletos

La suite completa conserva tests heredados/scaffold para registro, password reset, email verification, password confirmation/update, perfil, eliminación de cuenta y ExampleTest.

Muchos fallan porque esperan el esquema y rutas estándar de Laravel/Breeze, no la autenticación legacy personalizada.

No interpretar esas fallas automáticamente como regresiones ni implementar funciones fuera de alcance solo para hacerlos pasar.

Debe decidirse cuáles eliminar, adaptar o reemplazar.

---

## 22. Medidas

Medidas fue caracterizado como posible módulo piloto.

### Significado

Catálogo de unidades de medida en `tbl_unidades`, por ejemplo:

- Kg
- Gr
- Lts
- Pzas

No contiene factores de conversión.

“Medidas” no es lo mismo que el módulo “Unidades” de ventas/estadísticas.

### Legacy

- entrada: `/Medidas`;
- actualización: `/Productos/actualizarUnidad`.

Ese acoplamiento es técnico, no una regla de negocio que deba copiarse.

### Diseño Laravel conceptual

- `medidas.view`
- `medidas.update`
- ruta propia del recurso/capacidad.

### Alcance conocido

- listar;
- renombrar existentes;
- sin creación por ahora;
- sin eliminación;
- global, sin plaza.

El botón “Agregar Unidad” puede mantenerse visualmente deshabilitado por fidelidad mientras la acción no exista.

Revalidar contra el repositorio antes de implementar.

---

## 23. Índices recientes solicitados por Javi

Javi pidió optimizar `tbl_rol_semanal_nomina` con dos índices mientras se sigue trabajando sobre la BD legacy.

### Índice 1

```sql
ALTER TABLE tbl_rol_semanal_nomina
  ADD INDEX idx_rol_plaza_lugar_turno_fecha
  (plaza_id, rol_cafeteria_id, turno_id, Fecha);
```

Objetivo: acelerar listados/conteos de `Nominas::roles()` que filtran por plaza, lugar/rol de cafetería, turno y rango de fecha.

### Índice 2

```sql
ALTER TABLE tbl_rol_semanal_nomina
  ADD INDEX idx_rol_plaza_fecha_dia
  (plaza_id, Fecha, dia_id);
```

Objetivo: acelerar conteos por día y permitir un índice cubridor para la consulta indicada por Javi.

### Estado

**Verificar en la BD actual si ya fueron aplicados.**

Comprobación prevista mediante `information_schema.STATISTICS` y `EXPLAIN`.

Javi advirtió que, sin el primer índice, algunos listados podían volver a aparecer ordenados por `id` en lugar de `Fecha` porque el legacy no define `ORDER BY` explícito.

Conclusión para la migración: si el orden es funcionalmente importante, Laravel debe expresarlo con `ORDER BY`; nunca depender del orden accidental producido por un índice o plan de ejecución.

Estos índices optimizan el esquema temporal; no deben condicionar el diseño del esquema nuevo.

---

## 24. Mapa funcional general

Los controladores legacy no equivalen limpiamente a módulos de negocio.

Ejemplos:

- Pedidos recibe pedidos, pero Inventario incorpora stock.
- Medidas muestra unidades, pero Productos las actualiza.
- Perfiles muestra datos, mientras Usuarios modifica.
- Remesas alimenta datos usados posteriormente por Ventas.
- Inventario concentra varias responsabilidades.

Por tanto:

> Migrar por necesidades y responsabilidades funcionales, no por archivos/controladores CodeIgniter.

Módulos identificados:

- Ventas
- Depósitos
- Remesas / Gastos
- Inventario
- Pedidos
- Nóminas
- Usuarios
- Permisos
- Productos
- Insumos
- Medidas
- Unidades
- Opciones / Configuración
- Perfiles
- Inicio
- Notificaciones

---

## 25. Orden funcional orientativo

Mapa previo:

```text
Medidas / configuración
→ Insumos / productos / recetas
→ stock / movimientos
→ gastos
→ ventas + DLC
→ consultas financieras
→ pedidos
→ inventario físico
→ COGS / análisis
```

No es un calendario obligatorio.

Usuarios fue priorizado primero por ser transversal y permitir una etapa read-only de bajo riesgo.

---

## 26. Método de migración por módulo

Desde la nueva definición de Javi, el flujo recomendado es:

```text
LEGACY
  ↓
1. ¿Qué problema de negocio resuelve?
  ↓
2. ¿Qué información y acciones ve el usuario?
  ↓
3. ¿Qué reglas/permisos/alcan​​ces deben preservarse?
  ↓
4. ¿Qué datos necesita realmente?
  ↓
5. ¿Qué partes son accidentes técnicos de CodeIgniter?
  ↓
LARAVEL
  ↓
6. Diseñar la solución nativa más apropiada
  ↓
7. Reproducir fielmente el resultado visual/funcional requerido
  ↓
8. Implementar + probar
  ↓
9. Actualizar este documento
```

No asumir que:

- un controlador = un módulo;
- una tabla HTML legacy exige DataTables;
- una ruta legacy debe conservarse internamente;
- una fila de `tbl_menus` = un permiso Laravel;
- una peculiaridad del esquema actual debe existir en el esquema nuevo;
- fidelidad visual significa copiar arquitectura.

---

## 27. Zona horaria pendiente

Existe una cuestión pendiente alrededor de “hoy” en Laravel.

El entorno había reportado configuración UTC en una revisión previa.

La bitácora y filtros de fecha deben corresponder al horario real usado por el negocio/legacy.

Verificar en el entorno actual:

```php
config('app.timezone');
now()->format('Y-m-d H:i:s P');
```

Comparar con una fila recién creada en `tbl_bitacora_accesos` y la configuración legacy.

No asumir zona horaria sin comprobarla.

---

## 28. Pendientes prioritarios

1. Confirmar el diseño formal de la nueva base de datos cuando Javi lo entregue.
2. Confirmar qué tablas además de `tbl_usuarios → usuarios` cambiarán de nombre.
3. Confirmar renombres/eliminaciones/adiciones de columnas y relaciones.
4. Diseñar la estrategia de migración de datos al nuevo esquema.
5. Revisar qué compatibilidades temporales dejan de ser necesarias al retirar CodeIgniter.
6. Verificar estado real de autenticación, Inicio, Usuarios, Composer y tests en el monorepo.
7. Verificar si los dos índices de Nóminas ya fueron aplicados en la BD actual.
8. Resolver timezone antes de depender de `now()` para comportamiento de negocio.
9. Mantener fidelidad visual mientras se reemplazan mecanismos legacy por soluciones Laravel nativas.

---

## 29. Regla de prioridad de fuentes

Cuando haya contradicción:

1. una instrucción nueva y explícita de Javi tiene prioridad sobre decisiones antiguas;
2. el código y la BD reales tienen prioridad sobre recuerdos de implementación;
3. este documento debe actualizarse para reflejar la decisión vigente.

Los puntos no confirmados deben permanecer marcados como pendientes o con su nivel de certeza.
