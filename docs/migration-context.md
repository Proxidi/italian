# Contexto de migración — CodeIgniter a Laravel

## 1. Propósito

Este documento transfiere el contexto acumulado de la migración de The Italian Coffee Company.

El repositorio combinado debe contener:

- el sistema CodeIgniter legacy como referencia funcional y visual;
- el sistema Laravel actual como destino de la migración.

Regla central:

> Migrar comportamiento y reglas de negocio, no la arquitectura legacy.

Laravel debe preservar lo que el cliente necesita ver y hacer, pero implementar cada responsabilidad de forma nativa, mantenible, segura y razonable para Laravel.

Este documento es una bitácora técnica viva. Debe actualizarse conforme avance la migración.

---

## 2. Stack

### Legacy

- CodeIgniter 3.1.9.
- PHP 7.4.
- MySQL.
- Base de datos histórica: `italian`.
- UI basada en Bootstrap 3, AdminLTE, jQuery, DataTables y múltiples dependencias globales.

### Laravel

- Laravel 13.
- PHP 8.5.
- MySQL 8.4.
- Sail / Docker.
- Blade + Vite.
- CSS propio.
- JavaScript nativo cuando sea suficiente.
- `spatie/laravel-permission` para autorización.
- Autenticación web con guard `web` y sesiones nativas.

El estado exacto de versiones y paquetes debe verificarse siempre contra `composer.json`, `composer.lock` y el entorno real.

---

## 3. Convivencia temporal de ambos sistemas

CodeIgniter y Laravel comparten temporalmente la misma base de datos local.

Esto obliga a conservar compatibilidad con el esquema legacy mientras ambos sistemas estén activos.

### Contraseñas

CodeIgniter usa:

- tabla `tbl_usuarios`;
- columna `Password`;
- SHA-1.

Durante la convivencia:

- Laravel valida temporalmente la contraseña comparando SHA-1.
- Laravel no debe modificar ni rehashear `tbl_usuarios.Password`.
- No usar cast `hashed`.
- No usar `Hash::make()`, `Hash::needsRehash()` ni una migración automática de contraseña durante login.
- La migración a hashes modernos será una etapa posterior al retiro de CodeIgniter.

---

## 4. Roles

Mapeo confirmado:

| `rol_id` legacy | Nombre legacy | Rol Spatie |
|---:|---|---|
| 1 | Dueño | `superadmin` |
| 2 | Manager | `admin` |
| 3 | Gerente | `root` |
| 4 | SubManager | `submanager` |
| 5 | Socio | `socio` |

Reglas:

- `submanager` debe seguir siendo independiente.
- No fusionar `admin` y `submanager` aunque compartan parte del panel.
- No crear un bypass global para `superadmin`; Dueño no tiene todos los permisos legacy.
- Mientras CodeIgniter siga vivo, `tbl_usuarios.rol_id` continúa siendo la fuente legacy de rol.
- Laravel usa Spatie para autorización.
- Cuando en el futuro se implemente edición de roles, `rol_id` y Spatie deberán actualizarse en la misma transacción mientras exista convivencia.

### Seeder temporal de roles

Según el trabajo previo se implementó `LegacyUserRolesSeeder` usando `syncRoles()`.

Su objetivo es sincronizar desde `tbl_usuarios.rol_id` hacia Spatie durante desarrollo/transición.

No debe convertirse en el mecanismo normal de producción para cambiar el rol de un usuario.

**Pendiente de verificar contra el repositorio Laravel combinado.**

---

## 5. Autenticación legacy

Archivos relevantes del legacy:

- `application/controllers/Seguridad.php`
- `application/models/Mdl_seguridad.php`
- `application/models/Mdl_usuarios.php`
- `application/models/Mdl_ventas.php`
- `application/controllers/Inicio.php`

Flujo observado:

1. recibe username/password;
2. aplica SHA-1;
3. autentica contra el usuario legacy;
4. registra acceso;
5. carga datos de sesión;
6. redirige a `/Inicio`.

La sesión legacy contiene más información que Laravel necesita mantener como sesión de autenticación.

Laravel debe obtener la identidad desde `Auth::guard('web')` y consultar datos adicionales solo cuando sean necesarios.

### Bitácora de login

El legacy registra al menos:

- Fecha;
- Hora;
- `usuario_id`;
- `plaza_id`.

Este comportamiento debe preservarse mientras sea parte de la funcionalidad vigente.

### Modal/aviso de corte

Existe lógica legacy relacionada con `modal`, `msj` y `hoy`, además de consultas de ventas cercanas al login.

La relación exacta entre controlador, vistas y JavaScript no quedó completamente caracterizada.

**Pendiente:** verificar todo el flujo antes de migrar esa funcionalidad.

---

## 6. Autenticación Laravel

Según el historial de implementación, Laravel ya cuenta con:

- `App\\Models\\User` extendiendo `Authenticatable`;
- tabla legacy `tbl_usuarios`;
- `LoginRequest`;
- `AuthenticatedSessionController`;
- guard `web`;
- sesiones nativas;
- login mediante `Auth::login()` / guard equivalente;
- regeneración de sesión tras autenticación;
- logout POST;
- invalidación de sesión;
- regeneración de token CSRF;
- `guest:web` para login;
- `auth:web` para áreas autenticadas;
- throttling aproximado de 5 intentos/minuto;
- validación temporal SHA-1.

Archivos que deben verificarse directamente en el nuevo repositorio:

- `app/Models/User.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `database/seeders/LegacyUserRolesSeeder.php`
- `routes/web.php`

No asumir que el contenido actual coincide exactamente con este resumen hasta comprobarlo.

---

## 7. Autorización legacy

Archivos/tablas principales:

- `application/libraries/Backend_lib.php`
- `application/models/Mdl_backend.php`
- `tbl_roles`
- `tbl_menus`
- `tbl_permisos`
- lógica adicional hardcodeada en controladores/vistas.

### Separación importante: navegación vs autorización

En el legacy son mecanismos distintos.

#### Navegación

El sidebar/dashboard contiene principalmente lógica hardcodeada por `rol_id`.

Por ello, cambiar permisos en `tbl_permisos` no garantiza que el sidebar o dashboard cambien automáticamente.

#### Autorización

`Backend_lib::control()` usa la ruta actual y la relaciona con `tbl_menus`/`tbl_permisos`.

El sistema histórico presenta problemas como:

- coincidencias parciales de rutas;
- entradas duplicadas;
- rutas/acciones obsoletas;
- acciones internas;
- acciones existentes fuera de `tbl_menus`;
- excepciones AJAX;
- controles hardcodeados adicionales.

`Leer` funciona en la práctica más como “puede ejecutar esta acción” que como un CRUD estrictamente de lectura.

### Consecuencia para Laravel

No importar mecánicamente `tbl_menus` y `tbl_permisos` a Spatie.

Cada módulo debe caracterizarse y convertirse en permisos semánticos.

Ejemplo:

- `users.directory.view`
- `access-log.view`
- `medidas.view`
- `medidas.update`

Las rutas deben usar permisos explícitos, por ejemplo middleware `can:`.

Ocultar un enlace nunca es autorización suficiente.

---

## 8. Rol 4 / SubManager

Hallazgos previos:

- rol 4 no tenía filas normales en `tbl_permisos` en los datos analizados;
- aun así comparte visualmente panel con Manager;
- existen relaciones de rol 4 en estructuras de gastos como `tbl_grupos_roles_gastos`.

Conclusión:

- no asumir que SubManager hereda permisos de Manager;
- no fusionar ambos roles;
- caracterizar cada flujo real antes de asignar permisos Laravel.

---

## 9. `/Inicio` legacy

El legacy selecciona panel por `rol_id`:

| Rol legacy | Panel |
|---:|---|
| 1 Dueño | `superadmin` |
| 2 Manager | `admin` |
| 3 Gerente | `root` |
| 4 SubManager | `admin` |
| 5 Socio | `socio` |

Esto define panel visual, no equivalencia de permisos.

### Rasgos visuales relevantes

- Dueño/Gerente muestran “Panel de Administración”.
- Subtítulo: “Panel de Control”.
- Perfil del header usa principalmente `Nombre`.
- El dropdown de usuario muestra avatar, usuario, plaza, estado y acciones.
- Sidebar y dashboard contienen accesos diferentes por rol.
- Muchas rutas aún no migradas deben aparecer como pendientes/deshabilitadas cuando corresponda.

---

## 10. `/Inicio` Laravel

Según el historial se creó una navegación configurada y reutilizable.

Archivos relevantes reportados:

- `config/authenticated-navigation.php`
- `app/Support/AuthenticatedNavigation.php`
- `resources/views/layouts/app.blade.php`
- componentes de header/sidebar/footer/icon
- dashboard/cards
- `resources/views/inicio/index.blade.php`
- `resources/css/authenticated.css`
- `resources/js/authenticated.js`

### Decisiones conocidas

- panel base definido por rol;
- navegación concreta filtrada por permisos cuando corresponde;
- destinos aún no migrados pueden quedar deshabilitados;
- perfil usa `Nombre` corto con fallback a `Usuario`;
- nombre completo puede usar apellidos;
- plaza se obtiene desde `tbl_usuarios.plaza_id → tbl_plazas.id → tbl_plazas.Nombre`;
- sidebar con comportamiento acordeón;
- layout con scroll independiente entre sidebar y contenido;
- sin Bootstrap/AdminLTE/jQuery como dependencia de la nueva UI.

**Pendiente de verificar contra el repositorio Laravel combinado.**

---

## 11. Filosofía de navegación Laravel

Modelo deseado:

```text
rol
→ define tipo de panel / estructura base

permisos
→ definen capacidades concretas
→ protegen rutas
→ pueden controlar visibilidad de opciones
```

Ejemplo:

```text
root
→ users.directory.view
→ access-log.view

superadmin
→ access-log.view
→ NO users.directory.view
```

Esto evita la desincronización legacy entre lo que aparece en menú y lo que realmente está autorizado.

---

## 12. Usuarios — primera etapa

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

El listado es global; no se filtra por plaza.

Las columnas de edición/eliminación deben mantenerse visualmente si ayudan a la fidelidad, pero permanecer deshabilitadas hasta migrar esas acciones.

### Bitácora legacy

Columnas:

- Fecha
- Hora
- Plaza
- Usuario
- Rol

Características:

- filtro por fecha exacta;
- por defecto usa el día actual;
- plaza mostrada desde el acceso registrado;
- rol mostrado desde el rol actual del usuario relacionado;
- no usa un snapshot histórico de rol.

---

## 13. Usuarios — Laravel

Según el historial se implementaron:

- `UsersReadController`;
- `DirectoryRequest`;
- `AccessLogRequest`;
- permisos:
  - `users.directory.view`
  - `access-log.view`
- rutas de listado/bitácora;
- rutas de exportación;
- búsqueda server-side;
- sort server-side;
- paginación server-side;
- escape de salida;
- búsqueda con comodines tratados literalmente;
- navegación permission-aware.

### Matriz de permisos acordada

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

No hay bypass global para `superadmin`.

### Paginación reportada

- Usuarios: 100 por página.
- Bitácora: 200 por página.

### Seeder

`UsersReadPermissionsSeeder` asigna los dos permisos anteriores a los roles correctos.

Debe conservar permisos no relacionados.

**Pendiente de verificar contra el repositorio Laravel combinado.**

---

## 14. Fidelidad visual de Usuarios

La primera implementación Laravel se veía demasiado rediseñada respecto al legacy.

Se acordó corregirla para aproximarse mucho más a las capturas originales.

Objetivo visual:

- encabezado compacto;
- panel blanco;
- botones de exportación pequeños tipo DataTables;
- buscador alineado como en legacy;
- tabla densa;
- paginación similar;
- `Agregar Usuario`, `Editar`, `Eliminar` visibles pero deshabilitados;
- sin introducir Bootstrap/AdminLTE/DataTables.

Regla general:

> La implementación interna puede cambiar por completo, pero Victor debe reconocer visualmente la misma pantalla migrada.

---

## 15. Exportaciones legacy

Investigación global posterior a Usuarios encontró:

- DataTables Buttons cargado globalmente;
- JSZip;
- pdfmake;
- `buttons.html5`;
- aproximadamente 24 inicializadores centrales en `assets/js/tables.js`;
- alrededor de 26 vistas con configuraciones DataTables propias.

Módulos con este patrón incluyen:

- Usuarios;
- perfiles;
- notificaciones;
- opciones;
- Ventas;
- Depósitos;
- Remesas;
- Gastos;
- Inventario;
- Pedidos;
- Productos;
- Insumos;
- Unidades;
- Nóminas;
- reportes relacionados.

Patrón:

```js
dom: 'Bfrtip',
buttons: [
    { extend: 'excelHtml5', ... },
    { extend: 'pdfHtml5', ... },
    { extend: 'copy', ... }
]
```

### Comportamiento

- Excel genera `.xlsx` real.
- PDF descarga `.pdf`.
- Copy copia contenido de tabla.
- títulos/columnas/tamaño de página varían.
- algunas tablas excluyen acciones;
- otras exportan solo columnas visibles;
- otras no especifican exclusiones.

No se encontró generación tabular XLSX/PDF desde controladores legacy.

PHPExcel incluido en el legacy se usa principalmente para leer/importar archivos.

---

## 16. Importaciones Excel legacy

Existen importaciones relevantes en módulos como:

- Ventas;
- Pedidos;
- Inventario.

No son triviales.

Ejemplos encontrados:

- lectura de celdas concretas como `G7` y `E6`;
- recorrido de filas/celdas;
- procesamiento de archivos de corte.

Esto motivó una decisión global de infraestructura en Laravel, no limitada a Usuarios.

---

## 17. Decisión global Excel/PDF en Laravel

Después de revisar el legacy completo en este aspecto se decidió utilizar:

### Excel / XLSX

`maatwebsite/excel`

Motivos:

- integración Laravel;
- usa PhpSpreadsheet internamente;
- exportación;
- importación;
- queries;
- mapeo;
- estilos;
- múltiples hojas;
- chunks/colas cuando haga falta.

### PDF

`barryvdh/laravel-dompdf`

Motivos:

- integración Laravel;
- usa Dompdf;
- Blade → PDF;
- descarga directa;
- suficiente para listados tabulares simples.

PDFs visualmente complejos deben evaluarse por módulo.

### Estado actual de Composer

Dependencias directas esperadas:

```json
"barryvdh/laravel-dompdf": "^3.1",
"maatwebsite/excel": "^4.0"
```

`phpoffice/phpspreadsheet` y `dompdf/dompdf` fueron instaladas primero directamente por error y luego retiradas de `composer.json`.

Deben permanecer solo como dependencias transitivas si son requeridas por las integraciones anteriores.

La instalación con `--with-all-dependencies` actualizó también varias dependencias compatibles, incluido Laravel de aproximadamente `13.34.0` a `13.36.0`.

Verificar estado real con:

```bash
composer show --direct
composer why phpoffice/phpspreadsheet
composer why dompdf/dompdf
```

y revisar extensiones del contenedor:

```bash
php -m | grep -Ei 'zip|xml|dom|gd|mbstring'
```

No usar `--ignore-platform-reqs`.

---

## 18. Contrato futuro de exportaciones

Para cada módulo:

- Excel debe descargar `.xlsx` real.
- PDF debe descargar `.pdf` directamente.
- Excel/PDF deben respetar filtros activos.
- Deben exportar todos los resultados autorizados que correspondan, no depender del DOM visible.
- Copy debe reproducir el comportamiento efectivo del legacy de la tabla concreta.
- Las columnas exportables se definen explícitamente.
- No exportar Editar/Eliminar salvo que haya una razón real.
- La ruta de exportación usa el mismo permiso que la pantalla.
- Los datos de usuario deben tratarse de forma segura para evitar fórmulas en Excel.
- PDFs deben escapar contenido.
- No habilitar recursos remotos innecesarios en PDF.
- Probar autorización, filtros, MIME, filename y descarga.

---

## 19. Pruebas actuales

`UsersReadTest` fue ejecutado correctamente antes de la reorganización:

- 16 tests;
- 111 assertions;
- todos pasando.

Incluye validaciones de:

- invitados;
- matriz de roles;
- acceso directo a exports;
- columnas;
- alcance global;
- fecha exacta;
- orden;
- paginación;
- validación;
- búsqueda;
- exports;
- XSS;
- wildcard literal;
- navegación;
- ausencia de escrituras;
- idempotencia del seeder.

### Suite completa

La suite conserva tests scaffold de Breeze para:

- registro;
- reset de password;
- verificación de email;
- confirmación/actualización de password;
- perfil;
- eliminación de cuenta;
- ExampleTest.

Muchos fallan porque esperan:

- rutas que ya no existen;
- modelo/esquema estándar Laravel;
- tabla `users` o factory estándar;
- comportamiento que no forma parte de la migración actual.

No interpretar esas fallas automáticamente como regresiones.

El nuevo repositorio debe decidir explícitamente qué tests scaffold:

- eliminar;
- reemplazar;
- adaptar;

antes de considerar confiable el resultado de `sail artisan test` completo.

No implementar registro/password-reset/profile solo para hacer pasar tests obsoletos.

---

## 20. Medidas

Medidas fue caracterizado como posible módulo piloto.

### Significado

“Medidas” representa catálogo de unidades de medida.

Tabla principal:

- `tbl_unidades`

Campos relevantes observados:

- `id`
- `Nombre`

Ejemplos:

- Kg
- Gr
- Lts
- Pzas

No hay factores de conversión.

### Diferencia importante

“Medidas” NO es el mismo módulo que “Unidades”.

“Unidades” legacy se relaciona con cantidades/estadísticas de ventas.

### Flujo legacy

- entrada: `/Medidas`
- actualización: `/Productos/actualizarUnidad`

Ese cruce hacia Productos es un acoplamiento legacy y no debe reproducirse necesariamente.

Laravel debería modelar la capacidad como algo equivalente a:

- `medidas.view`
- `medidas.update`

y rutas semánticas propias del recurso.

### Alcance acordado

- listar;
- renombrar unidades existentes;
- sin creación por ahora;
- sin eliminación;
- global, sin plaza.

La UI legacy tiene un botón “Agregar Unidad”, pero la creación no está realmente implementada. Puede mantenerse visualmente deshabilitado si aporta fidelidad.

### Permisos legacy observados

Gerente/root es el rol relevante para esta funcionalidad.

Revalidar directamente contra el repositorio combinado antes de implementar.

---

## 21. Mapa funcional general del legacy

Los controladores no equivalen limpiamente a módulos de negocio.

Ejemplos importantes:

- Pedidos recibe pedidos, pero Inventario incorpora stock.
- Medidas muestra unidades, pero Productos actualiza una unidad.
- Perfiles muestra datos, mientras Usuarios maneja modificaciones.
- Remesas alimenta información usada posteriormente por Ventas.
- Inventario concentra múltiples responsabilidades.

Por tanto:

> Migrar por responsabilidades funcionales, no por archivos/controladores.

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

## 22. Orden aproximado de dependencias funcionales

Mapa previo orientativo:

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

No tratar esto como calendario obligatorio.

Usuarios fue priorizado primero por ser entidad transversal y permitir una primera etapa read-only de bajo riesgo.

---

## 23. Principios de diseño para nuevas migraciones

Antes de migrar un módulo:

1. caracterizar comportamiento legacy;
2. identificar tablas y dependencias;
3. verificar roles/permisos efectivos;
4. identificar alcance por plaza/global;
5. identificar inputs/outputs;
6. revisar UI y comportamiento visual;
7. separar peculiaridades accidentales de reglas de negocio;
8. diseñar permisos semánticos;
9. implementar Laravel nativamente;
10. crear pruebas;
11. comparar visual/funcionalmente;
12. actualizar este documento.

No asumir que:

- un controlador = un módulo;
- una fila de `tbl_menus` = un permiso Laravel;
- un rol visible en sidebar = permiso real;
- una ausencia de fila en `tbl_permisos` explica todo el comportamiento;
- una peculiaridad interna de CodeIgniter debe copiarse.

---

## 24. Pendientes inmediatos al abrir el monorepo

La primera tarea del nuevo Codex debe ser una auditoría, no una nueva implementación.

Debe:

1. leer `AGENTS.md`;
2. leer este documento;
3. inspeccionar el Laravel real;
4. contrastar autenticación, navegación, `/Inicio`, Usuarios y dependencias;
5. verificar todos los puntos marcados como pendientes;
6. corregir este documento cuando contradiga el código real;
7. no modificar funcionalidad durante esa primera auditoría salvo autorización explícita.

### Verificaciones prioritarias

- `composer.json` / `composer.lock`;
- versión real de Laravel;
- `maatwebsite/excel`;
- `barryvdh/laravel-dompdf`;
- extensiones PHP requeridas;
- autenticación SHA-1;
- roles/Spatie;
- `AuthenticatedNavigation`;
- rutas de Usuarios;
- `UsersReadTest`;
- estado real de vistas/CSS/JS de Usuarios;
- timezone usada por `now()` frente a la bitácora legacy;
- tests Breeze obsoletos;
- cualquier diferencia entre este documento y el código.

---

## 25. Zona horaria

Existe una cuestión pendiente alrededor de la fecha “hoy” en Laravel.

Laravel había reportado configuración UTC en el historial previo.

La bitácora y filtros de “hoy” deben corresponder al horario real usado por el negocio/legacy.

Verificar en el nuevo repositorio y entorno:

```bash
php artisan tinker
```

y después:

```php
config('app.timezone');
now()->format('Y-m-d H:i:s P');
```

Comparar con:

- hora real del negocio;
- una fila recién creada en `tbl_bitacora_accesos`;
- configuración legacy.

No asumir zona horaria sin comprobarla.

---

## 26. Estado de documentación

Este documento nació de una conversación anterior en la que el agente tenía acceso completo al legacy pero no al repositorio Laravel actual.

Por eso:

- los hallazgos legacy son referencia útil;
- todo estado Laravel debe verificarse contra el nuevo checkout combinado;
- el código real tiene prioridad sobre este documento cuando exista contradicción;
- una contradicción debe corregirse aquí para evitar que se perpetúe.

El objetivo no es que este documento sustituya al código, sino que conserve decisiones, razones, restricciones y hallazgos que no son evidentes al leer archivos aislados.
