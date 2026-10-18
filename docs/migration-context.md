# Contexto de migración — CodeIgniter a Laravel

## 1. Alcance, evidencia y prioridad

Base: auditoría estática de `9d7b44df`; actualizada tras incorporar por fast-forward
`66dc03df` (`laravel-update`) y desarrollar/validar Usuarios sobre ese estado. Distingue
código inspeccionado, ejecución con datos sintéticos y pendientes con datos reales.
El repositorio canónico de este entorno es `Proxidi/italian`, rama local `work`;
la rama `feature/inicio` del responsable pertenece a otro repositorio y no se
sincroniza automáticamente con este checkout.

Estructura comprobada:

- `italian-legacy/`: CodeIgniter original; referencia funcional y visual. No se modifica; el responsable permite copiar assets necesarios a Laravel.
- `italian-laravel/`: aplicación destino. Los nombres abreviados `legacy/` y `laravel/` de conversaciones previas no son las rutas de este checkout.
- `AGENTS.md`: reglas generales; existen instrucciones adicionales en ambos subdirectorios.

Método: lectura de fuentes, rutas, esquema SQL, metadatos de permisos del dump,
manifiestos/lockfiles y tests. La auditoría inicial no ejecutó aplicaciones ni
suites. En la tarea funcional de Usuarios se ejecutan únicamente pruebas sobre
SQLite `:memory:` con datos sintéticos, dentro de un contenedor PHP 8.5 sin red.
No se importó el dump, no se conectó a MySQL ni se ejecutaron migraciones/seeders
sobre una BD persistente. Los seeders de permisos usados por tests solo escriben
en esa SQLite efímera. No se certifica producción ni se leen credenciales.

Prioridad: instrucciones explícitas actuales del responsable para los requisitos;
código comprobado para describir lo implementado; validación apropiada para afirmar
comportamiento en ejecución. Una diferencia del código respecto de un requisito
se documenta como diferencia, no como cambio automático del requisito.

## 2. Filosofía confirmada y arquitectura futura

Laravel debe conservar reglas de negocio, información, acciones, permisos y
apariencia relevantes de CodeIgniter. La fidelidad visible no exige copiar su
arquitectura, consultas, componentes, librerías, sesiones o mecanismo de permisos.
Cada flujo se debe resolver de la forma más adecuada y nativa posible en Laravel.
Los controles de acciones pendientes pueden mantenerse visibles e inertes; eso no
significa que esas acciones estén migradas.

La base legacy también será rediseñada, con renombres de tablas y posiblemente
columnas. No hay esquema definitivo verificado en esta auditoría. El ejemplo
`tbl_usuarios → usuarios` sigue siendo una posibilidad heredada, no un nombre
aprobado; se retira la afirmación de «90 % seguro» por no ser verificable aquí.
No renombrar tablas anticipadamente ni construir abstracciones para un esquema
supuesto.

El código Laravel actual usa el esquema legacy, pero eso no demuestra que ambos
sistemas apunten a una misma instancia de BD. Las conexiones efectivas, datos y
estado de migraciones/seeders quedan pendientes. Los nombres físicos actuales
aparecen en el modelo, controladores y navegación: un rediseño no se limitaría
necesariamente a cambiar `$table` en `User`.

## 3. Stack declarado y versiones bloqueadas

| Elemento | Evidencia estática |
| --- | --- |
| CodeIgniter | `italian-legacy/system/core/CodeIgniter.php`: `CI_VERSION = '3.1.9'`. |
| PHP legacy | `italian-legacy/composer.json` declara `>=5.3.7`; no prueba el PHP desplegado. El PHP 7.4 mencionado anteriormente queda como antecedente sin confirmar. |
| Frontend legacy | Layouts cargan Bootstrap 3, AdminLTE, jQuery, DataTables/Buttons, JSZip y pdfmake. |
| Laravel | `composer.json`: `^13.17`; `composer.lock`: **v13.36.0** tras `laravel-update`. |
| PHP Laravel | Manifiesto y framework requieren `^8.3`; Sail declara 8.5 y las pruebas de esta tarea se ejecutaron en **PHP 8.5.11**. No acredita el PHP de producción. |
| MySQL / Sail | `italian-laravel/compose.yaml` declara `mysql:8.4` y runtime Sail 8.5. No se verificó la versión desplegada. |
| Autorización | `spatie/laravel-permission` **8.3.0** en el lockfile. |
| Exportaciones | Directos: `maatwebsite/excel` **4.0.4**, `barryvdh/laravel-dompdf` **v3.1.2**. Motores transitivos: PhpSpreadsheet **5.10.0**, Dompdf **v3.1.6**. |
| Desarrollo / tests | Sail **v1.68.0**, Breeze **v2.4.2**, PHPUnit **12.5.39** en el lockfile. |
| Frontend Laravel | Blade + Vite; lock npm: Vite **8.3.1**, plugin Laravel **3.2.0**, Tailwind **3.4.19**, Alpine **3.17.4**. |

Las versiones bloqueadas describen dependencias del checkout, no un proceso activo.
Los layouts activos usan CSS propio y JS nativo para login, Inicio y Usuarios;
`resources/js/app.js` conserva Alpine del scaffold. La existencia de recursos
legacy copiados en `public/` no demuestra que el layout nuevo los cargue.
Las integraciones de Excel/PDF están declaradas, bloqueadas e instaladas en
`vendor/`; su ausencia correspondía al commit anterior. Laravel Boost sigue ausente.
Versiones estables bloqueadas, sin sufijos dev/RC: Excel 4.0.4 requiere PHP ^8.3,
Illuminate ^12 || ^13 y PhpSpreadsheet ^5.9; Laravel DomPDF 3.1.2 requiere PHP ^8.1,
Illuminate hasta ^13 y Dompdf ^3.0. PHP 8.5 está admitido por esos rangos y por los
motores bloqueados. `composer validate --strict --no-check-publish` y
`composer check-platform-reqs --lock` pasan en PHP 8.5.11. No se actualizaron las
dependencias ni se afirma que estas sean las últimas versiones publicadas.

Fuentes: [Composer](../italian-laravel/composer.json),
[lock Composer](../italian-laravel/composer.lock),
[lock npm](../italian-laravel/package-lock.json),
[Vite](../italian-laravel/vite.config.js),
[Sail](../italian-laravel/compose.yaml). Los manifiestos y lockfiles no se modificaron en esta tarea funcional.

## 4. Superficie Laravel activa y archivos scaffold

[bootstrap/app.php](../italian-laravel/bootstrap/app.php) carga `routes/web.php`,
`routes/console.php` y la ruta de salud `/up`. `web.php` no incluye `routes/auth.php`.
Los providers propios inspeccionados no agregan esas rutas ni un bypass de Gate.

| Ruta declarada en `web.php` | Protección / comportamiento del código |
| --- | --- |
| `GET /` | Redirige a Inicio si hay sesión `web`, o a login si no. |
| `GET /login`, `POST /login` | `guest:web`; POST añade `throttle:5,1`. |
| `GET /Inicio` | `auth:web`; construye navegación y panel. |
| `POST /logout` | `auth:web`; formularios con CSRF. |
| `GET /Usuarios`, `GET /Usuarios/export` | `auth:web` + `can:users.directory.view`. |
| `POST /Usuarios` | `auth:web` + policy `create`: `users.directory.view` y `users.create`. |
| `GET /Usuarios/{user}/editar`, `PUT /Usuarios/{user}` | `auth:web` + policy `update`: `users.directory.view` y `users.update`; binding Eloquent por ID numérico. |
| `GET /Usuarios/listaAccesos`, `GET /Usuarios/listaAccesos/export` | `auth:web` + `can:access-log.view`. |
| `GET /Opciones` | `auth:web` + `can:configuration.view`; panel Configurar. |
| `GET /Opciones/fechas`, `GET /Opciones/fechas/export` | `auth:web` + `can:configuration.upload-window.view`. |
| `PUT /Opciones/fechas` | Además exige `configuration.upload-window.update`; valida y actualiza el único registro existente. |
| `GET /Usuarios/notificaciones`, `GET /Usuarios/notificaciones/export` | `auth:web` + `can:configuration.notifications.view`; esta pantalla pertenece a Configurar. |
| `PUT /Usuarios/notificaciones` | Además exige `configuration.notifications.update`; solo cambia la fecha del único registro existente. |
| `GET /Productos` | `auth:web` + `can:products.view`; panel con destinos a Productos, Insumos y Medidas según sus permisos. |
| `GET /Productos/list`, `GET /Productos/list/export` | `auth:web` + `can:products.catalog.view`; catálogo global, filtro por familia y exportaciones. |
| `GET /Productos/list/nuevo`, `POST /Productos/list` | Además exige `products.catalog.create`; alta de producto activo, sin inicializar otros módulos. |
| `GET /Productos/{product}/formulas` | Lectura de catálogo + `products.formulas.view`; relaciones producto–insumo y cantidades en unidad_id del insumo. |
| `POST /Productos/{product}/formulas` | Además exige `products.formulas.create`; asociación sin duplicados, cantidad/unidad válidas. |
| `GET /Productos/{product}/formulas/{formula}/editar`, `PUT /Productos/{product}/formulas/{formula}` | Además exige `products.formulas.update`; binding hijo acotado, escritura transaccional solo de Cantidad. |
| `GET /Medidas`, `GET /Medidas/export` | `auth:web` + `can:products.units.view`; catálogo global de unidades. |
| `POST /Medidas` | Además exige `products.units.create`; inserta únicamente Nombre. |
| `PUT /Medidas/{unit}` | Además exige `products.units.update`; binding numérico, cambia únicamente Nombre. |
| `GET /Insumos`, `GET /Insumos/export` | `auth:web` + `can:products.ingredients.view`; listado y descargas globales. |
| `GET /Insumos/{ingredient}/conversiones` | Mismo permiso de lectura; operaciones del insumo con relaciones válidas. |
| `PUT /Insumos/{ingredient}/conversiones/{conversion}` | Además exige `products.ingredients.conversions.update`; binding hijo acotado al insumo y comprobación transaccional. Solo Bodega/Pedido. |

Registro, recuperación/verificación/actualización de contraseña y perfil conservan
controladores, vistas y/o rutas scaffold. **No son funcionalidades activas por el
solo hecho de existir esos archivos.** No hay rutas activas de perfil, registro,
`dashboard`. Medidas está activa según la sección 21. En Usuarios están activas alta/edición administrativa; Eliminar sigue ausente.
`resources/views/paneles/`, `dashboard.blade.php` y otros restos tampoco prueban el
uso de esas vistas: el controlador activo sirve `inicio.index`.

Los controladores scaffold contienen `Hash::make()`, escrituras de campos estándar
y borrado de usuario. Esto no contradice que el login activo preserve SHA-1,
pero impide afirmar que todo el repositorio está adaptado al modelo legacy. No
habilitar esos flujos sin revisar esquema, contratos, permisos y compatibilidad.

## 5. Autenticación y sesión: comparación comprobada

Fuentes legacy: `application/controllers/Seguridad.php`,
`application/models/Mdl_seguridad.php`, `Mdl_usuarios.php` y `Mdl_ventas.php`.
Fuentes Laravel: [User](../italian-laravel/app/Models/User.php),
[controlador de sesión](../italian-laravel/app/Http/Controllers/Auth/AuthenticatedSessionController.php),
[LoginRequest](../italian-laravel/app/Http/Requests/Auth/LoginRequest.php),
[auth.php](../italian-laravel/config/auth.php).

Comprobado en ambos códigos:

- Autenticación por `Usuario` y `Password` SHA-1, con joins internos a plazas y roles.
- Registro del acceso con Fecha, Hora, usuario y plaza; redirección a Inicio.
- Cálculo de una fecha de dos días atrás; consulta de existencia en
  `tbl_reporte_ventas` por Fecha y plaza, sin filtros adicionales en ese método.

Comprobado en Laravel:

- `User` extiende `Authenticatable`, usa `HasRoles`, apunta a `tbl_usuarios`, sin timestamps.
- `Password`, `Hash` y `sha1_id` están ocultos en serialización; `Password` es el nombre
  de credencial. No hay cast `hashed` ni transformación de Password en el login activo.
- Guard `web` de sesión con provider Eloquent, por defecto `App\Models\User`.
- `LoginRequest`: `username` requerido, string, máximo 50; contraseña requerida,
  string, máximo 4096. El throttle de la ruta limita solicitudes POST; no se
  debe describir como un contador verificado exclusivamente de contraseñas fallidas.
- Tras la consulta manual SHA-1 exige uno de los cinco roles Spatie de acceso;
  puede devolver 403 aunque existan credenciales legacy válidas si faltan esos roles.
- Llama a `Auth::guard('web')->login(..., remember: false)` y regenera la sesión.
  No utiliza `Auth::attempt()` ni rehashea Password en ese recorrido.
- Guarda `modal = 1`, `msj` y `hoy`; logout invalida sesión y regenera token CSRF.
- Login escribe en `tbl_bitacora_accesos`: el alcance de «solo lectura» de Usuarios
  no significa que toda la aplicación carezca de escrituras.

### Avisos y modal: migración parcial

La documentación anterior dejaba todo el aviso de corte por caracterizar. Su
cálculo ya existe en el controlador Laravel; **su presentación no está migrada en
Inicio/layout/JS activo inspeccionado**, que no consume `modal`, `msj` ni `hoy`.

El legacy incluye además `Inicio::reasignar()` y llamadas AJAX desde
`application/views/layouts/footer.php` para notificaciones y modales según rol
(cortes, DLC, pedidos y otros avisos). Laravel muestra «Mis Notificaciones» como
pendiente y no tiene ese endpoint activo. No equiparar guardar tres valores de
sesión con reproducir todo el flujo de notificaciones. Validación del resultado
visible y de los escenarios con datos: pendiente.

## 6. Roles y autorización

Mapeo codificado de forma central en `App\Support\LegacyUserRoleSynchronizer`,
`SaveUser` y `LegacyUserRolesSeeder`; es coherente con la selección de paneles
legacy, pero no acredita las asignaciones actuales en la BD:

| `rol_id` | Denominación heredada | Rol Spatie | Panel legacy / panel Laravel |
| --- | --- | --- | --- |
| 1 | Dueño | `superadmin` | `superadmin` / `superadmin` |
| 2 | Manager | `admin` | `admin` / `manager` |
| 3 | Gerente | `root` | `root` / `root` |
| 4 | SubManager | `submanager` | `admin` / `manager` |
| 5 | Socio | `socio` | `socio` / `socio` |

`LegacyUserRolesSeeder` crea/busca roles web y ejecuta `syncRoles()` en una
transacción, por lotes de usuarios; rechaza `rol_id` sin correspondencia. Sustituye
asignaciones Spatie de los usuarios; no actualiza `tbl_usuarios.Password` ni otros
campos legacy. Es útil para el primer aprovisionamiento, pero ya no es necesario
volver a ejecutarlo después de cada alta o edición legacy: tras validar SHA-1 y sus
joins, el login Laravel reconcilia la asignación Spatie exacta de ese usuario desde
su `rol_id`. No crea roles ni permisos: si `rol_id` no está mapeado, o falta el rol
Spatie `web` correspondiente, devuelve 403 antes de crear sesión o bitácora.
`SaveUser` conserva la sincronización atómica de altas y cambios explícitos de rol
(sección 9).

> **Compatibilidad temporal:** usar `tbl_usuarios.rol_id` para reconciliar
> asignaciones Spatie solo existe mientras CodeIgniter y Laravel compartan la BD
> legacy. Debe revisarse y retirarse o reemplazarse al migrar al esquema definitivo,
> cuando `rol_id` y la base legacy dejen de ser la fuente autorizada.

`UsersReadPermissionsSeeder` define esta matriz de **relaciones rol-permiso**:

| Rol | Directorio (`users.directory.view`) | Bitácora (`access-log.view`) |
| --- | --- | --- |
| `root` | Sí | Sí |
| `superadmin` | No | Sí |
| `admin`, `submanager`, `socio` | No | No |

Usa `roles()->sync()` sobre cada uno de esos dos permisos, conserva otros permisos
y roles de usuarios, y limpia caché antes/después. Requiere que los roles root y
superadmin ya existan. **No elimina permisos directos a usuarios**; tampoco impide
asignaciones posteriores. Por tanto, la matriz no demuestra los permisos efectivos
de una BD real. No se encontró un bypass propio global para `superadmin`.

`config/permission.php` habilita la integración con Gate. Las rutas usan `can` y
los Form Requests de lectura/escritura vuelven a comprobar la capacidad. Durante
la convivencia, `rol_id` es la fuente autorizada para elegir el rol Spatie de
acceso al autenticar; los permisos efectivos siguen siendo únicamente los que
tenga ese rol (más permisos directos preexistentes). No se otorgan permisos por
defecto ni se convierte `rol_id` en un bypass de autorización.

### Legacy: navegación distinta de autorización

`Backend_lib::control()` exige login, toma los dos primeros segmentos de ruta,
resuelve un menú mediante `Mdl_backend::getID()` (un `LIKE` sobre `link`), consulta
permiso por menú/rol y exige `Leer != 0`. Sidebar y paneles tienen condiciones por
rol; `Inicio` usa control de sesión propio. La visibilidad de un menú no prueba
acceso servidor ni permite deducir por sí sola la matriz anterior.

Las afirmaciones heredadas sobre filas duplicadas/obsoletas de menús, ausencia de
filas de permisos del rol 4 y contenido de `tbl_grupos_roles_gastos` dependen de
datos y quedan sin confirmar. El dump ahora inspeccionado confirma permisos por acción de Usuarios (sección 9);
sigue sin acreditar el estado del despliegue. No fusionar Manager/SubManager ni
importar mecánicamente `tbl_menus`/`tbl_permisos` a Spatie.

Fuentes: [seeders](../italian-laravel/database/seeders),
[Backend_lib](../italian-legacy/application/libraries/Backend_lib.php),
[Mdl_backend](../italian-legacy/application/models/Mdl_backend.php).

## 7. Inicio y navegación Laravel

Comprobado en [AuthenticatedNavigation](../italian-laravel/app/Support/AuthenticatedNavigation.php),
[configuración de navegación](../italian-laravel/config/authenticated-navigation.php),
[layout](../italian-laravel/resources/views/layouts/app.blade.php) y sus componentes:

- Panel base por rol Spatie. Si hay varios roles, precedencia declarada:
  superadmin, root, admin, submanager, socio. Sin panel reconocido: 403.
- Header: `Nombre` con fallback a `Usuario`; nombre completo puede incluir apellidos;
  plaza consultada por `plaza_id`; avatar por Sexo; estado «Sesión: Activa».
- Perfil visible e inerte; logout POST con CSRF en header/sidebar.
- Destinos con permiso se ocultan cuando `can()` lo deniega; grupos vacíos se
  eliminan. Un destino sin ruta disponible queda inerte con aviso de pendiente.
- Inicio, Usuarios, Configurar, Productos/Medidas e Insumos tienen destinos
  activos. Las tarjetas/enlaces restantes siguen pendientes; ver secciones 20–23.
- CSS propio declara scroll separado y JS nativo maneja acordeón, colapso y móvil.
  Esa implementación está presente; apariencia, accesibilidad e interacción reales
  no se certificaron mediante navegador en esta revisión.

Los títulos de paneles corresponden a los patrones legacy inspeccionados:
Administración para Dueño/Gerente e Inicio para Manager/SubManager/Socio, con
subtítulo Panel de Control. Esto no prueba fidelidad visual completa en todos los roles; la revisión sintética de Usuarios se detalla en la sección 11.

## 8. Usuarios: reglas comprobadas en el legacy

Fuentes: `application/controllers/Usuarios.php`, `application/models/Mdl_usuarios.php`,
vistas `administrador/usuarios/list.php`, `listaAccesos.php` y `assets/js/tables.js`.

**Directorio:** consulta global, sin filtro de plaza, joins internos a roles/plazas,
orden `u.id ASC`. La pantalla contiene Nombre, Edad, Email, User Name, Plaza,
Editar y Eliminar, además de Agregar Usuario. No confundir el comentario del modelo
sobre «activa» con un filtro efectivo: `getUsuarios()` no lo aplica.

**Bitácora:** Fecha, Hora, Plaza, Usuario y Rol. Fecha exacta recibida por POST al
buscar; sin búsqueda explícita usa `date('Y-m-d')`. Orden Fecha/Hora descendente;
plaza de la fila de acceso, Nombre y rol actuales del usuario relacionado. No es
un snapshot histórico de nombre/rol. La tabla usa el identificador
`tbl_notificaciones`, aunque muestre accesos.

DataTables inicializa 100 filas para `tbl_usuarios` y 200 para
`tbl_notificaciones`. Es paginación cliente sobre resultados obtenidos por el
legacy, no paginación SQL equivalente a la nueva implementación.

## 9. Usuarios: implementación Laravel y reglas de escritura comprobadas

Fuentes legacy: `Usuarios.php`, `Mdl_usuarios.php`, `Mdl_roles.php`, `Mdl_plazas.php`,
`Backend_lib`, `Mdl_backend`, `administrador/usuarios/{list,editAdmin,editManager}.php`
y `perfiles/list.php`. Esquema y permisos: `italian-laravel/italian.sql`, solo leído.

### Lectura y alcance

`UsersReadController` conserva directorio y bitácora globales, joins internos,
fecha exacta y búsqueda/orden por GET. Paginación SQL 100/200, filtro escapado
literalmente para `!`, `%`, `_` y columnas/direcciones validadas con listas cerradas.
El directorio añade el ID a la selección interna para los enlaces de edición;
siguen siendo cinco datos visibles/exportables. No se envían Password, Hash ni
sha1_id al listado. Agregar Usuario abre un diálogo modal nativo, y Editar abre
un formulario completo. Eliminar conserva un control deshabilitado e inerte.

### Alta/edición administrativa

| Dato | Crear | Editar administrativo |
| --- | --- | --- |
| Nombre, Apepat, Apemat | Obligatorios, máximo 25 caracteres cada uno. | Igual. |
| Edad | Entero 0–127 (TINYINT firmado del dump). | Igual. |
| Sexo | H/M; H preseleccionado. | No aparece ni se modifica, como `update()` legacy. |
| Email | Obligatorio, formato email, máximo 75, único. | Igual, excluyendo el propio ID. |
| Usuario | Obligatorio, máximo 50, único. | Igual, excluyendo el propio ID. |
| Password | Obligatoria; persistencia temporal SHA-1. | Vacío conserva; texto nuevo se transforma a SHA-1. |
| Plaza / Rol | Catálogos completos de plazas y los cinco roles mapeados. | Cambiables, sin restricción por plaza/rol destino en legacy. |
| Hash / sha1_id | Bcrypt del ID / SHA-1 del ID tras insertar. | Se conservan sin cambios. |

El legacy valida required/unique en servidor; email/edad/sexo/plaza/rol dependían
en gran parte del HTML. Laravel añade validación servidor de formato, longitud,
rango y referencias existentes. Admite nombres con espacios/acentos: no reproduce
el `pattern="[a-zA-Z]*"` del modal, que excluía nombres válidos. No se muestran ni
se reenvían hashes: el legacy rellenaba Password con el SHA-1 y detectaba si el
POST coincidía con él. Laravel elimina esa exposición y usa el campo opcional.
Un string nuevo de 40 caracteres se trata como contraseña literal, nunca como hash.
El máximo de entrada es 4096, como en LoginRequest; se rechazan contraseñas de
solo espacios porque el login activo también las rechaza.

`SaveUserRequest` valida y autoriza; `UserPolicy` comprueba permisos semánticos.
`SaveUser` escribe con Eloquent en una transacción que incluye identificadores
legacy y asignación Spatie. Edición bloquea la fila (`lockForUpdate`, efectivo en
MySQL), conserva campos fuera del formulario y solo sincroniza roles si cambia
`rol_id`. Al cambiarlo sustituye las asignaciones Spatie por su rol web mapeado;
con rol sin cambios preserva asignaciones existentes. Conserva permisos directos.
Si falta el rol Spatie provisionado, la transacción falla sin guardar un usuario
parcial. Una colisión concurrente de Email/Usuario se presenta como validación.

No se crea estructura nueva, no hay cast hashed ni cambios del login. El alta
completa en una sola transacción las dos operaciones que legacy hacía separadas.
Mensajes y destino: la alta vuelve a Usuarios, muestra «Usuario Ingresado!» /
«Registro Exitoso!» en un diálogo nativo con check animado y sin botón, y redirige
automáticamente al listado una vez concluida la animación. La edición vuelve a
Usuarios con la alerta verde intensa «Perfil Actualizado», icono y cierre manual,
aproximando el aviso legacy. El diálogo de alta se muestra solo con el flash de
éxito; los errores de validación o guardado reabren el formulario con sus errores,
sin confirmación ni contraseña repoblada. El JS deshabilita el envío válido para
evitar duplicados. No se incorporaron Bootstrap/jQuery/SweetAlert; se conserva la
organización modal, campos, colores de acciones y formulario de edición con CSS
propio/JS nativo.

### Convivencia de roles y login verificada con pruebas sintéticas

CodeIgniter `Usuarios::store()` y `Usuarios::update()` escriben directamente
`tbl_usuarios.rol_id`; `Mdl_seguridad::autenticar()` solo verifica Usuario,
Password SHA-1 y joins de plaza/rol. No conoce `model_has_roles`. Laravel antes
comprobaba exclusivamente `hasAnyRole()` después de esa misma consulta: un usuario
creado o cambiado en CodeIgniter podía tener credenciales correctas y aun así
recibir 403 «El usuario no tiene un rol de acceso asignado». Como la inserción en
`tbl_bitacora_accesos` estaba después del 403, tampoco dejaba acceso registrado.

Ahora `AuthenticatedSessionController` usa el sincronizador central después de
validar esas credenciales y antes de la bitácora/sesión. Reemplaza una asignación
Spatie ausente o desactualizada por el único rol derivado de `rol_id`, incluso para
Dueño, Manager, Gerente, SubManager y Socio. Si falta el rol Spatie provisionado o
el ID legacy es inválido, conserva el 403, no crea pivotes, no inicia sesión y no
registra bitácora. Esto evita depender de un seeder manual por cada modificación
legacy sin ampliar permisos de Usuarios. Una modificación hecha por Laravel ya
sincroniza en su propia transacción; en el siguiente login también queda coherente
si otro sistema la hubiera alterado después.

Esta reconciliación desde `tbl_usuarios.rol_id` es un mecanismo temporal de
compatibilidad con CodeIgniter, no la arquitectura de autorización objetivo. Se
debe revisar expresamente al migrar al esquema definitivo y dejar de depender de
la BD legacy.

Las pruebas SQLite cubren los cinco roles creados sin pivot Spatie, un cambio de
rol directo que representa CodeIgniter, IDs legacy inválidos, falta de un rol
Spatie y las rutas Laravel de alta/edición. No se ejecutaron ambas aplicaciones
contra el dump compartido: siguen pendientes conflictos concurrentes reales,
configuración efectiva de roles/permisos de esa instancia y la comprobación visual
en navegadores objetivo.

### Autorización comprobada y activación pendiente

En el dump: menús 87 (Usuarios), 88 (store), 89 (editAdmin), 90 (update), 91
(delete2): Gerente `rol_id=3` tiene Leer=1; Dueño `rol_id=1` tiene Leer=0.
No hay permisos de estas acciones para Manager/SubManager/Socio. Para lectura
Bitácora (92), Gerente y Dueño tienen Leer=1. Esto confirma los seeders de lectura
y la matriz administrativa nueva; el dump no acredita producción.

`UsersWritePermissionsSeeder` asigna `users.create` y `users.update` exclusivamente
a `root`, idempotentemente, sin tocar permisos ajenos ni datos legacy. La policy
exige además `users.directory.view`. No hay bypass de `superadmin` ni permiso de
escritura implícito por poder leer el directorio. Asignaciones directas siguen
siendo efectivas como en Spatie; no se convierten rol_id ni Hash en autorización.
Este seeder **solo se ejecutó en SQLite de tests**. Para habilitar los botones en
una instalación existente, el equipo debe confirmar primero su destino y luego
provisionar esos permisos/roles. No se ejecutó automáticamente ni se agregó al
DatabaseSeeder scaffold.

Manager y Socio tienen en el dump editManager/updateManager (119/120); el enlace
sale de su perfil propio. Esos métodos no modifican plaza, rol ni Sexo, pero el
servidor legacy no comprueba propiedad del ID; además getID usa LIKE y puede
resolver updateManager como update por coincidencia parcial. No migrar esa
ambigüedad como permiso global. **El responsable decidió expresamente limitar
esta tarea al directorio administrativo y posponer el perfil Manager/Socio.**
No se habilitan rutas Perfiles ni se amplía el acceso de esos roles.

No se encontraron restricciones para editar administradores, el propio usuario
o usuarios de otra plaza desde el flujo administrativo. No se inventaron
exclusiones. El producto puede perder acceso administrativo si cambia el último
Gerente: el legacy no lo evita; introducir una protección requiere decisión del
equipo. No se manipularon datos reales para explorar este caso.

El README `README-usuarios-lectura.md` es histórico y conserva instrucciones de
una etapa anterior; no representa el módulo actual.

## 10. Excel, PDF, CSV y Copy: realidad y diferencias

En legacy, los layouts cargan Buttons, JSZip, pdfmake y `buttons.html5`; `tables.js`
contiene **24 ocurrencias de inicialización `.DataTable(`**. Se encontraron 28
archivos PHP de vistas con la cadena `.DataTable(`, frente a «alrededor de 26» en
el resumen previo. Son conteos textuales, no una medición de tablas activas.

Los patrones incluyen `excelHtml5`, `pdfHtml5`, Copy y opciones de columnas/títulos
variables. En Usuarios, `th:not(:last-child)` excluye Eliminar pero no necesariamente
Editar. Esto difiere de Laravel, que excluye ambas columnas de acciones. No
convertir defaults accidentales del plugin en reglas aprobadas sin revisar el
resultado requerido.

PHPExcel se usa para lectura de archivos en Ventas, Pedidos e Inventario; hay
recorridos de filas y celdas específicas (por ejemplo, G7/E6 en Pedidos). Esto
justifica evaluar la solución transversalmente, pero no demuestra una decisión
actual confirmada de cambiar de paquete.

### Laravel actual

| Operación | Implementación comprobada |
| --- | --- |
| XLSX | `UsersReadSpreadsheet` implementa concerns de Laravel Excel (`FromGenerator`, binder explícito string, título, propiedades, autoancho y AfterSheet). Descarga real con `Excel::download`. |
| PDF | `Pdf::loadView('usuarios.pdf')` de Laravel DomPDF. A4 vertical, DejaVu Sans; remote/PHP/JavaScript deshabilitados explícitamente. |
| CSV | Sigue disponible como formato por defecto del endpoint; BOM UTF-8, `;`, neutralización de prefijos de fórmula. |
| Impresión | `format=print` conserva vista independiente. |
| Copy | JS propio, encabezados y filas de la página visible; omite acciones y neutraliza fórmulas. |

Las consultas XLSX/PDF mantienen filtros, orden y todas las filas coincidentes,
independientemente de `page`. Lectura de BD por `lazy(500)`, sin colección completa;
las bibliotecas siguen materializando workbook o HTML/render. No hay garantía de
memoria constante ni mejora automática de velocidad por usar wrappers.

XLSX conserva título en A1 fusionado, fila 2 vacía, encabezados en fila 3, datos
como strings desde fila 4, título 16pt/negrita, encabezados negrita, autoancho,
freeze A4 y autofiltro si hay datos. No exporta ID ni columnas de acciones.
Datos que empiezan por `=` permanecen texto, sin fórmulas ejecutables.
PDF conserva la vista previa, datos escapados, encabezados y fecha en Bitácora.
Las descargas incluyen nombres/MIME correctos y caché privada no-store.

CSV/print y Copy no cambiaron. Persisten las diferencias conocidas respecto de
DataTables: Copy solo copia página visible; búsqueda por Enter y paginación
servidor. La exclusión accidental de solo la última columna del plugin legacy no
se reproduce: se mantienen cinco datos y se omiten Editar/Eliminar.

La afirmación anterior sobre paquetes ausentes era correcta para `9d7b44df`,
pero quedó obsoleta al traer `66dc03df laravel-update`: desde ese commit los
wrappers están instalados. La migración del código de Usuarios a sus APIs se
realizó en esta tarea; instalar el paquete por sí solo no la había completado.

Fuentes: [exportador](../italian-laravel/app/Exports/UsersReadSpreadsheet.php),
[controlador](../italian-laravel/app/Http/Controllers/UsersReadController.php),
[tools](../italian-laravel/resources/views/usuarios/partials/tools.blade.php),
[Copy](../italian-laravel/resources/js/users-read.js).

## 11. Validación ejecutada y límites

`tests/Feature/UsersReadTest.php` incluye lectura, escritura, matriz de los cinco
roles, validación, permisos independientes, rollback, identidad legacy, cambio de
rol, contraseña vacía/nueva, login SHA-1 con datos sintéticos y ausencia de
escrituras en consultas/exports. Crea su propio esquema mínimo en SQLite
`:memory:` y limpia conexión al terminar. No usa RefreshDatabase ni MySQL.

Descargas reales: XLSX se abre con IOFactory y comprueba 505 filas filtradas y
ordenadas (atraviesa el lote 500), strings seguros, encabezados, estilos,
autofiltro y freeze. PDF se genera con Dompdf real; inspecciona el DOM previo al
render porque Dompdf libera nodos, comprueba 105 filas, orden, escape, opciones,
A4 y firma `%PDF-`. Bitácora XLSX/PDF comprueba fecha exacta/plaza/rol.
Pruebas de escritura comprueban permisos del servidor, rollback si falla la
asignación Spatie y compatibilidad con el login activo sin modificarlo.

Ejecución PHP 8.5.11 / PHPUnit 12.5.39 en imagen existente
`italian-cloud-php:8.5`, `docker run --rm --network none`. Se forzó SQLite y caches
de arranque/vistas en `/tmp`, sin arrancar servicios ni montar datos MySQL.
Comando de suite: `php vendor/bin/phpunit --filter UsersReadTest --do-not-cache-result`.
Primera entrega: **39 tests / 300 assertions**. La corrección posterior del login
amplió Usuarios a **48 tests / 352 assertions**. En la entrega de Configurar se
ejecutaron de nuevo Usuarios y Configurar juntos: **68 tests / 622 assertions**, todos
pasan (detalle de la validación actual en la sección 20).
`composer validate --strict --no-check-publish` y
`composer check-platform-reqs --lock` pasan. Vite compila con
`npm run build -- --outDir /tmp/italian-users-vite-build`, sin cambiar artefactos
versionados. Pint aplicado solo a PHP de este cambio; `git diff --check` revisado.

Revisión visual/interactiva adicional: HTML real renderizado por Laravel con
fixtures SQLite (harness temporal externo al repositorio, 1 test/2 aserciones),
CSS compilado y JS nativo del layout/Usuarios, en Chromium local mediante
Playwright. Modal abierto/cerrado y Escape, anchos 1366/390, ausencia de overflow
horizontal, Password vacío, rol/plaza seleccionados y ausencia de errores JS:
comprobados. Capturas inspeccionadas en escritorio/móvil bajo
`/tmp/italian-users-visual/`; no son capturas de producción ni una validación E2E
contra MySQL. Se contrastó la estructura con los formularios fuente legacy; no
se ejecutó CodeIgniter. No hicieron falta assets adicionales ni copias manuales.

Pendiente: MySQL real (collation latin1_swedish_ci, unicidad con acentos/mayúsculas,
charset latin1, locks/concurrencia), volumen de producción, timezone, permisos de
la instalación y pruebas end-to-end con sesiones/CSRF reales. La suite HTTP de
Laravel omite CSRF por diseño de tests; las rutas reales siguen con middleware
web, formularios @csrf y mutación POST/PUT. No se alteró infraestructura.

Persisten tests/factory scaffold incompatibles con modelo/rutas legacy. No se
reactivaron rutas ni se ejecutó toda esa suite ajena al alcance. Las migraciones
y DatabaseSeeder scaffold no construyen una BD legacy funcional: no ejecutarlos
como preparación automática. La existencia de un seeder no acredita ejecución
fuera del aislamiento aquí descrito.

## 12. Medidas y otros módulos: auditoría inicial y estado actualizado

Comprobado en legacy: `Medidas::index()` usa `Mdl_productos::getUnidades()` sobre
`tbl_unidades`, ordenadas por Nombre y sin filtro de plaza. La vista presenta Nombre
y Editar. `Productos::actualizarUnidad()` recibe `id_editar/nombre_editar` por AJAX
y actualiza Nombre; el formulario de edición apunta a ese endpoint. Son
responsabilidades repartidas, no una razón para copiar el acoplamiento en Laravel.

La vista contiene además botón y formulario «Agregar Unidad». En el flujo
inspeccionado no se identificó su conexión a un handler de creación; no confundir
esa UI con un alta funcional verificada ni eliminarla por esa razón. No se
identificó eliminación en esa pantalla. No se deduce de estos archivos que toda
la BD carezca de factores de conversión.

Medidas como piloto, `medidas.view`/`medidas.update` y alcance de solo renombrar eran
propuestas heredadas. La primera etapa de Productos ya tiene Medidas activa;
capacidades definitivas de esta entrega y diferencias en la sección 21.
El nombre Medidas corresponde aquí al catálogo de `tbl_unidades`, distinto del
módulo legacy `Unidades` de consultas/estadísticas.

Existen controladores legacy de Ventas, Depósitos, Remesas, Inventario, Pedidos,
Nóminas, Usuarios, Permisos, Productos, Insumos, Medidas, Unidades, Opciones y
Perfiles. Su existencia no equivale a una caracterización exhaustiva. El antiguo
mapa de dependencias y el orden orientativo de migración no se toman como plan
aprobado: revalidar el flujo concreto antes de iniciar cada módulo.

## 13. Nóminas e índices heredados

Se conservaron como solicitudes históricas, **sin ejecución ni estado de BD
confirmado**, los índices propuestos para `tbl_rol_semanal_nomina`:

- `idx_rol_plaza_lugar_turno_fecha`: `(plaza_id, rol_cafeteria_id, turno_id, Fecha)`.
- `idx_rol_plaza_fecha_dia`: `(plaza_id, Fecha, dia_id)`.

Se observan en `Mdl_nominas.php` filtros relacionados por plaza, turno, lugar/día
y rango de Fecha, y consultas sin un orden explícito en los métodos de roles
inspeccionados. `Nominas::roles()` incluye helpers de preparación y medición
temporal de tiempos/consultas. No se midió rendimiento ni se verificó si los
índices existen o si el plan de ejecución los usa.

Que un índice produzca accidentalmente un orden no constituye contrato. Si el
orden importa, la futura implementación debe expresarlo explícitamente.
`information_schema` y `EXPLAIN` quedan para una tarea con BD adecuada/autorizada;
ninguna optimización del esquema temporal determina el nuevo diseño.

## 14. Zona horaria: diferencia estática confirmada

`italian-laravel/config/app.php` fija `timezone = 'UTC'`. Login y fecha por defecto
de la bitácora usan `now()`. El legacy usa `date()`/`strtotime()` y declara
`time_reference = 'local'`; no se encontró fijación de timezone en el recorrido de
arranque/login inspeccionado. Ese valor de CodeIgniter no determina por sí solo la
zona PHP efectiva del servidor.

La zona del negocio, del PHP legacy desplegado, de MySQL y las conversiones
necesarias siguen pendientes. La zona del chat o del host cloud no prueba la zona
del negocio. No se modificó configuración ni se insertaron accesos para probarla.
Confirmar escenarios de cambio de día y de corte antes de declarar paridad.

## 15. Correcciones frente al contexto heredado

| Afirmación anterior | Estado después de la auditoría |
| --- | --- |
| Carpetas `legacy/` y `laravel/`. | En este checkout son `italian-legacy/` y `italian-laravel/`. |
| PHP legacy 7.4 y conexiones compartidas como hechos actuales. | No acreditados por esta revisión estática; se separan objetivo, declaración y despliegue. |
| Login, Inicio, Usuarios y seeders «según historial / verificar». | Fuentes inspeccionadas y recorridos concretos descritos; datos/ejecución aún pendientes. |
| Aviso de corte enteramente por caracterizar. | Cálculo existente en Laravel; presentación y notificaciones completas pendientes. |
| Integraciones Laravel Excel/Dompdf instaladas y Laravel 13.36.0. | Ausentes en la auditoría inicial; confirmadas tras traer `66dc03df`. APIs de Usuarios migradas en esta tarea. |
| Matriz de permisos como acceso efectivo de todos los usuarios. | Define relaciones de roles; permisos directos y estado de la BD pueden alterar el acceso. |
| Paridad completa de Copy/exportaciones/UI. | Hay diferencias concretas de alcance, acciones, interacción y validación pendiente. |
| Tests pasando como garantía actual y fallos scaffold conocidos. | La auditoría inicial no ejecutó tests; Usuarios y Configurar pasan juntos con 68 tests / 622 aserciones en aislamiento (sección 20). Scaffold ajeno sigue sin ejecutarse. |
| Preparación automática e instalación de Boost en las instrucciones locales. | Guía aclarada para respetar el alcance: la auditoría estática no requiere instalar herramientas, dependencias ni ejecutar servicios. |
| «90 %» de certeza sobre el nombre usuarios. | Eliminado; diseño formal pendiente. |
| Aproximadamente 26 vistas con inicializadores. | 28 archivos PHP contienen `.DataTable(`; conteo textual, no inventario de tablas activas. |

## 16. Pendientes para las siguientes migraciones

1. Esquema formal nuevo y estrategia de migración de datos, incluyendo renombres,
   relaciones y salida de la compatibilidad SHA-1 al retirar CodeIgniter.
2. Conexiones/versión del despliegue, esquema y datos efectivos; ejecución de seeders,
   permisos directos y asignaciones reales de los cinco roles.
3. Paridad visual en navegador por rol y tamaño de pantalla; alcance requerido de
   Copy, búsquedas, paginación y columnas/títulos de exportaciones frente al legacy.
4. Login con escenarios de negocio, presentación de avisos y notificaciones, y zona
   horaria acordada, con datos adecuados en una tarea posterior.
5. Volumen de producción y revisión visual de exportaciones con herramientas reales.
   Integraciones adoptadas y contenido/MIME/nombres/filtros comprobados con datos sintéticos.
6. Decidir el destino de scaffold incompatible; no reactivar sus rutas por accidente.
   La suite de Usuarios ya se ejecuta con SQLite en memoria.
7. Estado y utilidad de índices de Nóminas mediante inspección autorizada de BD,
   sin asumir que el dump o una conversación anterior reflejan producción.
8. Caracterizar por módulo las reglas y permisos restantes del legacy; los enlaces
   de navegación pendientes no representan implementación funcional.

Para cada trabajo futuro: leer estas reglas, seguir controlador/modelo/vista/JS
legacy relevante, separar requisito de accidente técnico, inspeccionar Laravel,
resolver el flujo con mecanismos apropiados, validar lo modificado y actualizar
este documento. Las cuestiones dependientes de datos reales permanecen pendientes
hasta contar con evidencia; una BD vacía no bloquea la lectura del repositorio.

## 17. Eliminar Usuarios: auditoría, sin implementación Laravel

### Recorrido real

`list.php` pide confirmación «El usuario se eliminará permanentemente» y envía
POST AJAX `FFfR8 = usuario.Hash` a `Usuarios/delete2`. El constructor pasa por
Backend_lib (sesión, menú de la acción y Leer), luego delete2 exige petición AJAX
y llama `Mdl_usuarios::delete($hash)`. SQL equivalente:

```sql
DELETE FROM tbl_usuarios WHERE Hash = ?;
```

No UPDATE, bandera Activo, ocultamiento ni borrado lógico. No actualiza ningún
campo, no ejecuta borrados relacionados desde ese método, no revoca sesiones y
no tiene transacción que coordine limpieza de otras tablas. Devuelve 1 si hay
filas afectadas, 0 en caso contrario; el manejo de errores SQL del despliegue no
se certificó. Hash no es único en el DDL: una colisión/duplicado afectaría a todas
las filas coincidentes, aunque el alta normal produce hashes Bcrypt diferentes.

Restricciones: el permiso de la acción (menú 91; root sí, superadmin no en el
dump), sesión y AJAX. Sin exclusión del propio usuario, de Dueño/Gerente, por
plaza o por último administrador. AJAX y conocer Hash no son autorización
suficiente por sí mismos; el control efectivo depende de Backend_lib.

### Relaciones e historial según el dump

El dump declara FKs hacia tbl_usuarios, sin ON DELETE CASCADE ni SET NULL, en:
`tbl_gastos`, `tbl_movimientos_inventario`, `tbl_platillos_vendidos`,
`tbl_platillos_vendidos_resumen`, `tbl_reporte_ventas` y `tbl_respaldos_inventario`.
Con esas restricciones activas, referencias existentes bloquean el borrado
(RESTRICT/NO ACTION); no hay cascada de borrado de historial declarada.
No se probó la operación ni se afirma que producción tenga idénticas constraints.
El borrado desde CodeIgniter tampoco limpia los pivotes polimórficos Spatie de
Laravel: no invoca eventos de su modelo. Una futura operación coordinada debe
incluir esas asignaciones sin alterar el historial de negocio.

`tbl_bitacora_accesos.usuario_id` tiene índice pero no FK a Usuarios. Si el DELETE
puede completarse, sus filas no se borran; el inner join de getListaAccesosFecha
las deja fuera del resultado al faltar el usuario. Hay además columnas usuario_id
sin FK en detalles de platillos, historial de inventario, tickets y reportes de
maquinaria; el método tampoco las limpia. No confundir `tbl_usuarios_nomina` y su
historial con cuentas de login: es otro catálogo con otro flujo de eliminación.
Los joins a usuarios de Ventas/Remesas/Inventario también dependen de que la fila
exista; no se auditó cada reporte del sistema para afirmar impactos completos.

Un nuevo login falla después de un borrado exitoso: Mdl_seguridad necesita la fila
por Usuario/Password y joins a plaza/rol. Sin embargo, sesiones CodeIgniter ya
abiertas conservan datos en memoria y Backend_lib solo comprueba `login`, no la
existencia actual de la cuenta. El método no fuerza logout: no garantiza revocar
inmediatamente esas sesiones. Laravel vuelve a resolver el usuario por ID en
peticiones nuevas, pero no implementamos ni probamos borrado para ello.

### Por qué no añadir SoftDeletes durante la convivencia

Requiere columna deleted_at y un scope que solo Eloquent conoce. CodeIgniter
seguiría autenticando y mostrando esa misma fila porque no filtra deleted_at;
los joins Query Builder de ambos proyectos tampoco heredan ese scope. Produciría
diferencias entre listados, bitácora y acceso sin desactivar la cuenta en ambos.
Preservar fila ayuda a las FKs, pero no equivale al comportamiento del DELETE
legacy ni resuelve por sí solo revocación o conservación visible del historial.

Decisión confirmada por el responsable: conservar Eliminar deshabilitado hasta
consultar a Javi (tutor) si se usará SoftDeletes, una columna Estado u otra
solución. Conservar IDs/historial y no añadir deleted_at, SoftDeletes ni un DELETE
Laravel mientras esa decisión siga pendiente. Antes de habilitarlo,
decidir con el equipo si el producto necesita baja de acceso o destrucción de
cuenta. Una baja coordinada que preserve la fila sería preferible para historial,
pero necesita diseño/aprobación de estado compartido, filtros/login en ambos
sistemas y revocación de sesiones. No hay campo de inactividad utilizable ya
confirmado en tbl_usuarios; no simular baja cambiando Password, rol o username.
Si el equipo exige DELETE físico, verificar constraints y dependencias reales,
tratamiento de cuentas privilegiadas, sesiones y pivotes Spatie antes de diseñar
la operación; nunca desactivar FKs ni borrar historial para forzarla.

No se agregó endpoint de eliminación, deleted_at, trait SoftDeletes, migración ni
escritura sobre una BD persistente.

## 18. Archivos de la entrega de Usuarios

Rutas relativas a `italian-laravel/`, salvo indicación contraria:

| Grupo | Archivos |
| --- | --- |
| Escritura | `app/Actions/Users/SaveUser.php`, `app/Http/Controllers/UsersWriteController.php`, `app/Http/Requests/Users/SaveUserRequest.php`, `app/Policies/UserPolicy.php`, `database/seeders/UsersWritePermissionsSeeder.php`. |
| Lectura/exportaciones | `app/Http/Controllers/UsersReadController.php`, `app/Exports/UsersReadSpreadsheet.php`. |
| Enlaces/modelo | `routes/web.php`; `app/Models/User.php` solo aclara el comentario de sincronización de roles. |
| Vistas | `resources/views/usuarios/table.blade.php`, `edit.blade.php`, `partials/create.blade.php`, `partials/fields.blade.php`. |
| Recursos | `resources/css/users-read.css`, `resources/js/users-read.js`. |
| Pruebas | `tests/Feature/UsersReadTest.php`. |
| Documentación raíz | `../docs/migration-context.md`; `../AGENTS.md` corrige estado de integraciones y distingue rehash automático de cambio explícito de contraseña. |

`italian-laravel/AGENTS.md` conserva las modificaciones locales de la auditoría
anterior; esta tarea no añadió cambios a esa guía. No se modificó legacy,
infraestructura, esquema SQL, migraciones, manifiestos, lockfiles ni vendor.
El responsable autorizó publicar esta entrega en el repositorio canónico
`Proxidi/italian`, rama `master`, para descargarla en su equipo. Se entrega
mediante Git, sin despliegue ni operaciones sobre la BD.

## 19. Base local del responsable y siguiente activación

El responsable confirmó que importó `italian-laravel/italian.sql` en su MySQL
local mediante `sail mysql italian < italian.sql`. Esto confirma la procedencia
de su base local, pero no el estado posterior de roles/permisos Spatie ni una
conexión compartida con este entorno cloud. No se repitió esa importación aquí.

Para activar alta/edición en esa instalación, una vez revisada su conexión local,
el seeder específico es `UsersWritePermissionsSeeder`. Conserva los permisos de
lectura ya provisionados y requiere el rol web `root` existente. No ejecutar
DatabaseSeeder ni reconstruir/importar la base para activar estos permisos.

## 20. Configurar (Gerente): recorrido comprobado e implementación

### Alcance y fuentes

El panel legacy `Opciones::index()` sirve
`administrador/opciones/panel_opciones.php`, cuyas dos tarjetas abren
`Opciones/fechas` (Corte & DLC) y `Usuarios/notificaciones` (Notificaciones).
Ambas subpantallas forman parte de esta entrega; no son el módulo independiente
«Mis Notificaciones» de Inicio. Se inspeccionaron `Opciones.php`,
`Mdl_opciones.php`, los métodos relacionados de `Usuarios.php`/`Mdl_usuarios.php`,
las tres vistas, `Backend_lib`, `Mdl_backend`, `assets/js/tables.js`, los consumidores
de Inicio/Ventas/Inventario y exclusivamente las secciones relevantes del dump.

### Estructura, datos y carácter global

El archivo `italian-laravel/italian.sql`, leído sin importarlo, declara:

| Tabla | Estructura relevante | Filas presentes en el dump |
| --- | --- | --- |
| `tbl_rango_archivos` | `id TINYINT` firmado, PK/autoincrement; `Numero_Dias TINYINT` firmado NOT NULL; `Descripcion VARCHAR(50)` NOT NULL. | Una: ID 1, días 1, «Rango de Fechas para DLC & Corte atrasados». |
| `tbl_notificaciones` | `id TINYINT` firmado, PK/autoincrement; `Fecha DATE` NOT NULL; `Descripcion VARCHAR(50)` NOT NULL. | Una: ID 1, fecha `2019-01-01`, «Sistema de Notificaciones Corte y DLC». |

Ninguna tiene plaza, usuario, tipo de configuración ni FKs declaradas hacia ella.
Los lectores relevantes toman la primera fila sin un selector de ámbito. Esto
acredita un ajuste global en el recorrido y el dump inspeccionados; **no demuestra
cuántas filas hay en producción ni impone unicidad en el esquema**.

`Opciones::actualizarRangoDias()` exige AJAX, recibe `numero_dias_valor`, acepta
`>= 1` y llama a `actualizarRangoArchivos()`: un UPDATE **sin WHERE** de
`Numero_Dias`, dentro de una transacción. Devuelve 1/0 para éxito/error y 2 para
valor menor a 1. Aunque la vista recibe el ID al abrir el modal, el servidor no
lo usa. La tabla lista Días, Descripción y Editar; DataTables `tbl_datos` añade
Excel/PDF/Copy, búsqueda, orden y 80 filas por página. Éxito: aviso superior derecho
«Rango correctamente actualizado!!!», sin OK, temporizado a 1500 ms.

`Usuarios::notificaciones()` consulta todas las filas. Al enviar `actualizar`,
`actualizarFechaNotificacion()` ejecuta un UPDATE **sin WHERE** de `Fecha`.
**No crea notificaciones, no cambia su descripción y no envía mensajes**. La vista
muestra un formulario de fecha y la tabla # / Fecha Inicio / Descripción, con las
mismas herramientas DataTables. No se encontró alta o eliminación en este recorrido.

### Dependencias preservadas y discrepancia pendiente

- `Mdl_opciones::getNumeroDias()` devuelve la primera fila como `Dias`.
  **Corrección tras el trazado completo del consumidor:** el método privado
  `Inventario::fechaCorrecta()` lee Dias (fallback 1), pero solo calcula una fecha
  que no compara después; tampoco se encontró ninguna llamada a ese método.
  No está acreditado un límite de antigüedad DLC efectivo basado en esta opción.
- `Ventas::store2()` consulta la fila, pero si existe fuerza **30 días** en vez
  de leer `Dias`; sin fila usa 1. Su mensaje menciona otro plazo. La migración de
  Configurar guarda el valor; **no corrige Ventas ni decide qué plazo debe usar**.
  Esa regla debe acordarse con Javi antes de migrar el consumidor.
- `Inicio::reasignar()` lee la primera `Fecha` de `tbl_notificaciones` y la pasa
  a las consultas de cortes/DLC faltantes. Actualizar esa fecha conserva el dato
  que consume CodeIgniter si comparte efectivamente la BD con Laravel. No ejecuta
  el sistema de avisos ni implica haber migrado su presentación en Inicio Laravel.

### Permisos

El dump contiene los menús 209 `Opciones`, 210 `Opciones/fechas`, 211
`Opciones/actualizarRangoDias` y 94 `Usuarios/notificaciones`. Gerente (`rol_id=3`)
tiene Leer=1 en los cuatro; Dueño tiene Leer=0 en 94 y no se encontraron concesiones
para los otros roles en esos cuatro menús. `Backend_lib` exige sesión, menú y
Leer; `Mdl_backend` resuelve el enlace mediante LIKE, sin convertir esa peculiaridad
en el mecanismo Laravel. Esto describe el dump y no certifica permisos desplegados.

`ConfigurationPermissionsSeeder` provisiona cinco capacidades `web`, asignadas
inicialmente solo a `root`:

- `configuration.view` (panel).
- `configuration.upload-window.view` y `configuration.upload-window.update`.
- `configuration.notifications.view` y `configuration.notifications.update`.

Rutas y Form Requests comprueban capacidades; guardar requiere lectura y escritura
de la subpantalla. Los permisos de Usuarios son independientes: poder configurar
Notificaciones no concede acceso a su directorio. No hay bypass Dueño/superadmin
ni autorización por `rol_id`. La navegación usa esos mismos permisos. No se exige
el permiso del panel como condición adicional de cada subpantalla, pues legacy
controlaba cada URL por separado. Concesiones directas Spatie siguen siendo
efectivas. El seeder conserva permisos ajenos/directos y es idempotente; restablece
las relaciones de sus cinco permisos a `root`, como los seeders del proyecto.

**Activación:** solo se ejecutó este seeder en SQLite efímera. En una instalación
existente, revisar primero la conexión/destino y provisionar después
`ConfigurationPermissionsSeeder` con el rol `root` ya existente. No ejecutar
DatabaseSeeder, migraciones ni una importación del dump para activar el módulo.
La futura pantalla de Permisos debe definir cómo se coordinan decisiones
administrativas con seeders que restablecen matrices iniciales.

### Implementación y diferencias intencionales

- `ConfigurationController`, tres Form Requests y consultas/escrituras
  encapsuladas en `App\Queries\ConfigurationSettings`; no se dispersan las tablas
  temporales por controladores/vistas. Se reutiliza el layout autenticado y su
  resolución de navegación. Rutas exactas en la sección 4.
- **Solo se guarda con exactamente una fila y un ID coincidente con el formulario.**
  La comprobación se repite sin filtros de búsqueda, bajo transacción y
  `lockForUpdate`; el UPDATE usa WHERE por ID. Con cero/múltiples filas o ID
  sustituido entre lectura y envío no hay escrituras, inserciones ni éxito falso.
  Se listan los datos y se deshabilita la edición con una explicación. Si una
  instalación tiene varias filas, queda pendiente decidir su significado y alcance:
  no se elige una arbitrariamente ni se replica el UPDATE masivo legacy.
- Días: entero 1–127, por el límite del TINYINT firmado, con error en español.
  Fecha: formato real `Y-m-d` y rango representable por MySQL DATE
  (`1000-01-01` a `9999-12-31`); sin prohibiciones inventadas para pasado/futuro.
  Solo se escriben Numero_Dias o Fecha, nunca Descripcion ni IDs.
- Lectura por GET y mutación por PUT con CSRF, patrón POST/redirect/GET y flash
  de éxito después de guardar. El POST AJAX `Opciones/actualizarRangoDias` se
  sustituye por PUT `/Opciones/fechas`; no se agregó un alias AJAX antiguo.
- Panel, títulos, tarjetas blancas, iconos, tablas y modal reproducen la estructura
  legacy con Blade/CSS/JS propios. Se copiaron únicamente `calendar.png` y
  `notification.png` desde assets legacy. Sin jQuery/AdminLTE/Bootstrap/SweetAlert.
  El aviso de rango se cierra automáticamente a 1500 ms; Notificaciones añade una
  confirmación explícita tras guardar. Nunca se muestra éxito con errores.
- Búsqueda por Enter, columnas de ordenamiento cerradas, paginación servidor de 80,
  exportación de todos los resultados filtrados/ordenados y Copy de la página
  visible, siguiendo el patrón de Usuarios. Excel/PDF conservan título «Datos»;
  se excluye Editar de los datos exportados, en vez de exportar el control.
- El escritor genérico existente se extrajo a `TabularSpreadsheet` y el PDF a
  `exports.table`; `UsersReadSpreadsheet` hereda el mismo escritor y `usuarios.pdf`
  incluye la misma plantilla compartida. No se alteró el contrato de Usuarios;
  se ejecutó su suite completa como comprobación de regresión. XLSX usa el binder
  seguro existente, PDF escapa valores y deshabilita recursos remotos/PHP/JS.
- Envío duplicado bloqueado en JS, diálogo nativo con Escape/cierre, errores
  anunciados y soporte de reducción de movimiento; controles restaurados al
  regresar mediante la caché de navegación del navegador.

### Validación ejecutada y límites

PHP 8.5.11 / PHPUnit 12.5.39, imagen existente `italian-cloud-php:8.5` con
`--network none`, caches de Laravel/vistas en `/tmp`. Se crearon exclusivamente
tablas mínimas y fixtures sintéticos en SQLite `:memory:`; no se montó ni consultó
MySQL, no se importó italian.sql ni se arrancaron los servicios de aplicación/BD.

`ConfigurationTest` + `UsersReadTest`: **68 tests / 622 aserciones**, todos pasan.
Cobertura: cinco roles, invitados, permisos de lectura/escritura independientes,
navegación, validación y límites, campos preservados, no creación, cardinalidad
cero/múltiple, IDs obsoletos, búsqueda literal, paginación 80, descarga XLSX real
con todas las filas/filtros/orden y fórmulas como texto, PDF real y seeders.
Las 48 pruebas de Usuarios siguen pasando tras extraer los exportadores compartidos.

Vite compila con `npm run build -- --outDir /tmp/italian-configuration-vite-build`;
no se modificaron artefactos compilados versionados. Pint en PHP cambiado,
`node --check` en JS y `git diff --check` pasan. La configuración Vite solo añade
los dos entrypoints del módulo; no se modificó infraestructura.

Revisión visual/interactiva en Chromium mediante Playwright sobre HTML renderizado
por Laravel con SQLite sintética: panel y assets, modal abierto/cerrado/Escape,
validación/reapertura, vista móvil 390 y escritorio 1366, ausencia de overflow,
prevención de doble envío, restauración al volver y cierre automático del éxito;
sin errores JS. Harness externo: 1 test / 7 aserciones. Capturas temporales
en `/tmp/italian-configuration-visual/`, inspeccionadas y contrastadas con las
vistas fuente/captura del PDF Scrum; no son una ejecución E2E de CodeIgniter.

Pendiente: verificar cardinalidad y datos reales, aprovisionamiento efectivo de
permisos, collations/DATE/TINYINT y locks/concurrencia en MySQL, sesiones/CSRF en
una instancia completa y efectos visibles en Inicio/consumidores legacy con una
BD realmente compartida. Definir el plazo de Ventas y qué hacer ante múltiples
filas. No se migraron Inicio, Ventas, DLC, Eliminar ni funcionalidades adicionales;
no se cambió autenticación, SHA-1, esquema, SQL, dependencias o AGENTS.md.

## 21. Productos, etapa 1: panel y Unidades/Medidas (2026-10-18)

### Recorrido comprobado y límites

Se inspeccionaron Productos::index/actualizarUnidad, Medidas::index,
Mdl_productos::getUnidades/actualizarUnidad y sus consultas de catálogos,
panel_productos.php, unidades/list.php, routes.php, Backend_lib/Mdl_backend,
el inicializador tbl_unidades de assets/js/tables.js y exclusivamente referencias
de unidades necesarias en modelos consumidores y DDL/datos dirigidos del dump.
No se importó italian.sql ni se consultó una BD compartida.

El panel contiene cuatro tarjetas: Productos, Insumos, Unidades (Medidas) y Costo
de Venta. En la etapa 1 solo Unidades es funcional; las otras tres conservan
su presencia visual, deshabilitadas como pendientes. No se activan listados/CRUD
de Productos, Insumos, fórmulas, costos ni módulos consumidores en esa entrega.
Estado posterior: Insumos se activa en la etapa 2, sección 23.
Productos y Fórmulas se implementan posteriormente en la etapa 3 descrita al
final de este documento; las limitaciones de esta sección corresponden a la
entrega original de la etapa 1.

Medidas lista todas las unidades, globalmente, ordenadas por Nombre. La tabla
legacy muestra Nombre y Editar, búsqueda/orden, 50 filas por página,
Excel/PDF/Copy con título «Unidades Insumos». Editar abre un modal; envía
id_editar/nombre_editar a Productos/actualizarUnidad. Ese controlador exige AJAX,
pero no valida nombre, longitud o existencia del ID; Mdl_productos actualiza
únicamente Nombre con WHERE id. Puede informar éxito aunque el ID no exista.
La vista marca Nombre como required. La dependencia del controlador Productos
es reparto histórico de responsabilidades; no constituye una regla de negocio.

**Alta legacy incompleta:** Agregar Unidad abre #agregarUnidad y el formulario
guardarNuevaUnidad, sin action ni listener/handler de guardado en el recorrido
y assets inspeccionados. No se encontró inserción a tbl_unidades en los modelos
de aplicación ni una acción de creación en Medidas/Productos. No se presenta ese
scaffold como alta funcional comprobada. Esta entrega completa el alta solicitada
explícitamente (incluida en la validación de la tarea), escribiendo solo Nombre;
es una diferencia intencional respecto al formulario legacy incompleto.

**Eliminar:** no existe control, endpoint o método de eliminación en el recorrido
legacy de Medidas. Laravel no incorpora botón, permiso ni ruta DELETE; tampoco
desactivación/SoftDeletes. Una futura eliminación necesitará decisión explícita
y revisión de referencias. No hay una baja segura deducible de esta pantalla.

### Persistencia, relaciones y efectos de renombrar

El dump define tbl_unidades con id INT firmado autoincrement/PK y Nombre
VARCHAR(50) NOT NULL. No hay timestamps, Estado, factores de conversión ni índice
único del nombre. El dump contiene unidades como 1 Gramos, 2 Mililitros, 3 Piezas,
5 Kilogramos, 10 Litros y 13 No Asignado; no acredita el catálogo de producción.
No se crean ni reemplazan esas filas desde seeders de datos.

Referencias comprobadas:

| Tabla | Columnas | Relación en el dump |
| --- | --- | --- |
| tbl_insumos | unidad_id, unidad_pedido, unidad_final | FKs a tbl_unidades.id, sin cascadas de eliminación declaradas. |
| tbl_insumos_gastos | unidad_id | FK a tbl_unidades.id, sin cascada declarada. |
| tbl_captura_inventario | Unidad_Bodega, Unidad_Piso, Unidad_Total | FKs a tbl_unidades.id, sin cascadas declaradas. |
| tbl_historial_completo_if | Unidad_Bodega, Unidad_Piso, Unidad_Total | Identificadores copiados de captura; sin FK declarada a unidades. |
| tbl_productos | Ninguna directa | La unidad llega indirectamente por tbl_productos_insumos → tbl_insumos. |

Mdl_productos usa joins de unidades para Insumos; Mdl_pedidos usa unidad_pedido,
Mdl_remesas usa tbl_insumos_gastos.unidad_id y Mdl_inventario usa unidad_final o
las tres unidades de captura. Son consumidores existentes, no migrados aquí.
tbl_unidades_vendidas y tbl_unidades_pago son tablas distintas, no este catálogo.
Familias y categorías de Insumos son necesarias en etapas futuras, pero no en
el panel estático ni en el catálogo de unidades: no se agregan consultas o CRUD
anticipados para ellas.

Renombrar una unidad usada es permitido por el legacy y solo cambia su etiqueta.
Laravel conserva el ID, referencias, cantidades y conversiones existentes, sin
reescrituras masivas ni cambios de tipos de unidad. Los consumidores que resuelven
Nombre por join mostrarán la nueva etiqueta también para registros anteriores.
Esto no convierte cantidades; cambiar «Gramos» por «Kilogramos» sería un cambio
semántico que el formulario no sabe resolver. No se infiere una conversión del
nombre ni se renumeran IDs. Borrar una unidad referenciada sería rechazado por
las FKs descritas; aun sin FKs, joins internos podrían ocultar registros huérfanos.

### Autorización y activación

Los menús relevantes del dump son 72 Productos, 56 Medidas y 256
Productos/actualizarUnidad. Rol 3 Gerente tiene Leer=1 en los tres; Dueño
tiene Leer=0 en 72/56, y no hay otras concesiones relevantes encontradas.
Backend_lib controla cada URL mediante menú/Leer y no mediante visibilidad.
El dump no certifica permisos instalados actualmente en otra instancia.

ProductsPermissionsSeeder provisiona `web`: products.view, products.units.view,
products.units.create y products.units.update, inicialmente solo a root/Gerente.
Alta usa una capacidad nueva separada para completar el formulario solicitado;
no concede acceso a otros roles. Rutas y Form Requests comprueban capacidades;
alta/edición requieren lectura de unidades más la capacidad de escritura.
El permiso del panel es independiente del de su subpantalla, siguiendo las
comprobaciones por URL legacy. No se usa rol_id para autorizar ni hay bypass.
Navegación Inicio/sidebar usa los mismos permisos y oculta destinos denegados.

**Activación pendiente en la instalación del responsable:** tras comprobar destino
de conexión y que exista root/web, ejecutar únicamente
`sail artisan db:seed --class=Database\\Seeders\\ProductsPermissionsSeeder` desde
italian-laravel. No ejecutar DatabaseSeeder, migraciones o importaciones para
activar esta etapa. El seeder es idempotente, conserva otros permisos y permisos
directos, limpia caché antes/después; restablece las relaciones de sus cuatro
capacidades exclusivamente a root. Aquí solo se ejecutó en SQLite efímera.

### Implementación y diferencias

- ProductsController, MeasurementUnit Eloquent (tabla encapsulada, sin timestamps)
  y Form Requests UnitsReadRequest/SaveUnitRequest. Rutas semánticas products.index,
  products.units.index/store/update/export, URLs Productos/Medidas (sección 4).
  No se replica el POST AJAX Productos/actualizarUnidad ni se activa /Unidades.
- Alta: INSERT de Nombre con ID generado por la BD. Edición: UPDATE Nombre WHERE
  id, con binding numérico y 404 para inexistentes; no se inventa éxito para IDs
  ausentes. Cada escritura es una sola sentencia atómica; no hay escrituras
  relacionadas que requieran una transacción adicional.
- Nombre obligatorio, texto y hasta 50 caracteres por esquema, recortado por
  middleware Laravel. Acepta tildes, comillas y nombres duplicados (sin inventar
  una restricción unique). Solo se usan datos validados, nunca IDs/campos extra.
- Blade y CSS/JS propios: tarjetas/colores/estructura legacy, modales dialog
  nativos, Escape/cierre, errores en español, datos escapados también en atributos,
  reapertura después de validación, confirmación temporal sin OK (1000 ms),
  bloqueo de doble envío y restauración al volver, soporte de reduced-motion.
  Se copiaron solo unit.png y rule.png del legacy; coffe2.png ya existía.
- Búsqueda literal por Nombre, orden asc/desc con desempate por ID, paginación
  servidor 50; Excel/PDF de todos los resultados filtrados/ordenados, Copy de la
  página visible. Título «Unidades Insumos», sin exportar el control Editar.
  Se reutilizan TabularSpreadsheet y exports.table, no vistas específicas de
  Configurar/Usuarios; workbook texto para fórmulas y PDF escapado/sin remotos.
  No se incorporan jQuery, DataTables, Bootstrap ni dependencias nuevas.

### Validación y pendientes

ProductsUnitsTest: **19 pruebas / 160 aserciones**, PHP 8.5.11/PHPUnit 12.5.39.
Suite combinada ProductsUnitsTest + ConfigurationTest + UsersReadTest:
**87 pruebas / 782 aserciones**, todas pasan. SQLite :memory: con fixtures mínimos,
conexión explícita de pruebas y contenedor --network none; sin MySQL ni servicios
compartidos. Incluye invitados/cinco roles, permisos independientes/faltantes,
alta, edición con referencias/cantidades e historial intactos, validaciones,
duplicados, tildes/comillas, ID inexistente, ausencia de DELETE, navegación,
búsqueda literal/escape/orden/paginación, exportación XLSX real fuera de la página
con fórmula como texto, PDF real y seeder repetible.

Vite compila en /tmp/italian-products-vite-build; Pint sobre PHP cambiado,
node --check y git diff --check pasan. Chromium/Playwright sobre HTML renderizado
por Laravel con SQLite sintética: escritorio 1366 y móvil 390, cuatro tarjetas y
assets cargados, solo una tarjeta activa, sin overflow horizontal, alta/edición,
comillas/tildes, Escape/cierre, validación/reapertura, prevención de doble envío,
restauración pageshow y cierre del aviso. Harness externo: 1 prueba / 6 aserciones;
capturas /tmp/italian-products-visual inspeccionadas visualmente. No acredita E2E
contra CodeIgniter ni la BD real.

Pendientes: activación de permisos en el destino confirmado, prueba de MySQL real
(collation/longitud/autoincrement y restricciones instaladas), sesiones/CSRF en
entorno completo y observación de consumidores coexistentes. La política de
eliminación sigue pendiente; la gestión posterior de conversiones de Insumos
se limita al recorrido demostrado de la sección 23;
Costos/fórmulas/Productos avanzados y demás módulos siguen fuera de alcance;
el estado posterior de Insumos y sus conversiones se documenta en la sección 23.
Sin cambios en legacy, esquema, autenticación, dependencias, infraestructura o
AGENTS.md. Esta entrega se publicó en `master` como `c42e3a76`.

### Decisión de tablas y avisos visuales compartidos (implementada en sección 22)

La tabla de Usuarios (`usuarios/table.blade.php` + users-read.css) fue la primera
implementación completa de listado y se ajustó de forma más estrecha a la captura
legacy: encabezados y celdas centrados, borde inferior de encabezado más marcado,
botones de acción compactos y paginación. Configurar y Medidas se implementaron
después con CSS de módulo propio; reproducen controles, exportación, búsqueda y
paginar, pero sus tablas tienen bordes, alineación y espaciados distintos. Esta
diferencia no responde a un requisito de datos, autorización, Laravel ni a una
incompatibilidad entre módulos: es deuda de consistencia visual surgida al no
haber extraído todavía una base común.

Los listados no deben copiarse módulo por módulo. En una tarea visual transversal
autorizada, extraer una base Blade/CSS propia para tabla autenticada reutilizable
(contenedor, barra de herramientas, buscador, scroll, encabezados ordenables,
paginación, exportación/Copy y acciones), con opciones explícitas para columnas,
alineación y acciones. Debe conservar el HTML semántico, autorización del servidor,
consultas/paginación/exports propios de cada módulo y las diferencias visibles que
el legacy realmente requiera; no introducir DataTables, jQuery, Bootstrap,
AdminLTE ni una dependencia nueva solo para ello. Usuarios, Configurar y Medidas
son los primeros candidatos y deben migrarse juntos para evitar tres variantes.

Lo mismo aplica a confirmaciones: Usuarios tiene una alerta de éxito destacada y
un diálogo de alta; Configurar y Medidas usan tarjetas de éxito temporizadas con
CSS/JS propios. En la misma tarea transversal se puede crear un componente propio
de avisos con variantes de actualización y creación animada, duración, icono,
anuncio accesible y `prefers-reduced-motion`. Laravel aporta flash de sesión y
Blade, pero no una librería de animaciones que sea necesario adoptar. No cambiar
estas pantallas de forma aislada hasta decidir y aplicar esa base común con pruebas
visuales y de accesibilidad. La base ya se implementó; para nuevos módulos aplicar
la regla comprobada y la matriz por acción de la sección 22, sin asumir que todas
las altas/ediciones deben animarse.

## 22. Base visual compartida de listados y confirmaciones (2026-10-18)

El responsable autorizó estandarizar tablas y confirmaciones tras comparar
Usuarios, Configurar y Medidas. Requisito explícito: **animar solo donde corresponda
y el legacy tenga esa interacción**. Esta entrega implementa la decisión anterior;
la apariencia base es la tabla Laravel de Usuarios que ya fue ajustada al legacy.

### Regla de reutilización para siguientes módulos

Usar los componentes Blade `components/ui/` para los elementos que ya son
comunes: listing, toolbar, table, sort-heading, pagination, table-action/action-icon
y feedback. Los estilos `ui-list.css` y `ui-feedback.css` y el JS `ui.js` se cargan
una vez desde el layout autenticado. Las vistas del módulo suministran datos,
columnas, filtros, rutas, acciones y permisos; no añadir consultas, decisiones de
autorización ni nombres físicos de tablas a los componentes visuales.

No copiar de nuevo una tabla/CSS/Copy por módulo, ni importar directamente una
vista de Usuarios para representar un catálogo diferente. Mantener variaciones
reales del legacy mediante opciones/clases/componentes simples, sin un framework
genérico de tablas ni nuevas dependencias. No se añadieron librerías, jQuery,
AdminLTE, DataTables o Bootstrap.

La base comparte celdas centradas, bordes claros, encabezados con borde inferior
marcado, flechas de orden, botones compactos SVG, herramientas Excel/PDF/Copy,
buscador, foco visible, estado vacío y paginación. El ancho mínimo móvil de 700px
es explícito para Usuarios/Bitácora (scroll dentro de la tabla), no para los
catálogos pequeños. Se conservan acciones naranja/rojo en Usuarios y verde en
Medidas/Corte & DLC: son diferencias comprobadas del legacy. No se agrega ninguna
acción Eliminar ni se habilita la existente de Usuarios.

Se usan los componentes en los cinco listados: directorio de Usuarios, Bitácora,
Corte & DLC, Notificaciones y Medidas. Las formas de alta/edición conservan sus
campos y validaciones, y el CSS específico solo conserva lo que pertenece a cada
módulo (tarjetas, formularios y diálogos de edición). Ningún archivo fue eliminado.

### Confirmaciones: decidir por recorrido, nunca automáticamente por POST/PUT

Se releyeron las vistas legacy de Usuarios/list, Usuarios/editAdmin,
opciones/fechas, notificaciones/add y productos/unidades/list. Resultado:

| Acción | Legacy comprobado | Variante Laravel compartida |
| --- | --- | --- |
| Alta de Usuarios | SweetAlert de éxito con círculo/check, «Usuario Ingresado!», «Registro Exitoso!», seguido de navegación al listado. | Dialog centrado con check SVG animado; sin OK y redirección a los 2200ms, excepción ya autorizada por el responsable. |
| Edición de Usuarios | Alerta verde destacada con icono/cierre; texto legacy erróneo «Peril Actualizado». | Banner estático verde, cierre manual, texto corregido «Perfil Actualizado». |
| Actualizar Corte & DLC | SweetAlert de éxito top-end, sin OK, 1500ms. | Dialog superior derecho con el mismo check animado, sin OK, cierre a los 1500ms. |
| Actualizar Medidas | SweetAlert de éxito top-end, sin OK, 1000ms. | Dialog superior derecho con el mismo check animado, sin OK, cierre a los 1000ms. |
| Actualizar fecha de Notificaciones | POST y recarga de la tabla, sin SweetAlert/animación. | Aviso simple estático de guardado, conservando la confirmación explícita añadida al migrar; sin animación ni temporizador. |
| Alta de Medidas | Formulario incompleto, sin guardado conectado ni confirmación verificable. | Aviso simple estático de la nueva alta solicitada en etapa 1; sin inventar una animación legacy. |

`feedback` ofrece variantes banner/notice/dialog; el módulo elige explícitamente
la variante, posición, duración y eventual URL de redirección. El JS nunca decide
animar por el verbo HTTP o por el texto del mensaje. La animación comparte dibujo
del círculo y check con SVG/CSS y aparición del popup; no copia SweetAlert como
dependencia. Respeta `prefers-reduced-motion`, títulos/descripcion accesibles,
diálogo nativo con foco y Escape, cierre temporal y recuperación del foco.
Solo el alta de Usuarios redirige después de animar; los otros avisos no disparan
una segunda petición de navegación. Se mantiene el patrón POST/redirect/GET
existente y se evita duplicar un banner detrás de la confirmación de alta.

No se muestran éxitos con errores de validación. ProductsController solo añade
metadato flash `unit-updated=true` después de guardar una edición para distinguir
su aviso del alta; no cambia la escritura de Nombre ni sus permisos/reglas.
Las demás acciones/controladores, rutas, consultas, seeders, filtros y exports
siguen siendo propios de cada módulo.

### Copy y comportamiento de las tablas preservado

`ui.js` copia solo los registros de la página visible de su listado, usando el
texto de los encabezados sin flechas y excluyendo acciones. Conserva el título
«Unidades Insumos» en Copy de Medidas; Usuarios y Configurar conservan su cabecera.
Escapa tabuladores/saltos y protege valores que empiezan por =, +, - o @ como texto
(la variante anterior de Medidas no compartía esa protección). Mensaje de copia
accesible, fallo de clipboard informado y Copy deshabilitado sin registros.

Las descargas siguen pasando por sus endpoints autorizados y conservan título,
columnas, filtros, orden y alcance completo de resultados. No se cambia el número
de filas por página ni el comportamiento de los formularios/bloqueos de envío.

### Comprobaciones ejecutadas y límites

ProductsUnitsTest + ConfigurationTest + UsersReadTest: **92 pruebas / 820 aserciones**
correctas, incluyendo casos nuevos de confirmación por acción, ausencia de
animaciones donde no existen en legacy y supresión de éxito con errores. Se
conservan pruebas de permisos, altas/ediciones, relaciones, límites, filtros y
descargas reales XLSX/PDF. Datos sintéticos en SQLite :memory:, PHP 8.5.11 y
PHPUnit 12.5.39, contenedor sin red y sin MySQL compartido.

Vite compila en `/tmp/italian-ui-vite-build`; sintaxis JS, Pint y `git diff --check`
se comprueban. Harness externo de renderizado: **3 pruebas / 22 aserciones**.
Chromium/Playwright sobre las vistas reales renderizadas por Laravel comprobó los
cinco listados a 1366/390px: estilos compartidos, colores de acciones, ausencia de
overflow de página, scroll interno de Usuarios, catálogos pequeños adaptados,
Copy con fórmula como texto y acciones excluidas, estado vacío, formularios,
Escape, errores/reapertura, bloqueo de doble envío, restauración pageshow,
avisos animados con sus tiempos, redirección de Usuarios, avisos estáticos que
permanecen y reduced-motion. Sin errores JavaScript. Capturas inspeccionadas y
harness en `/tmp/italian-ui-visual/`; no acredita una sesión E2E sobre BD real.

No se modificaron legacy, esquema, dependencias, infraestructura, AGENTS.md,
autenticación o permisos. Vite solo registra los assets compartidos. Para probar
esta entrega no se necesitan nuevos seeders ni escrituras de configuración.

La estandarización de esta sección se publicó en master como `dc2197d2`.

## 23. Productos, etapa 2: Insumos y conversiones (2026-10-18)

### Recorrido demostrado y límite del alta

Fuentes dirigidas: Insumos::index/save/checarBanderas/consultarOperacionesInsumo/
actualizarOperaciones; Productos (no contiene CRUD adicional de Insumos);
Mdl_productos::getInsumos/getCategorias/getUnidades y escritores/lectores de
conversiones; productos/insumos/list.php; Mdl_remesas::getGrupos; Backend_lib,
Mdl_backend, rutas; consumidores puntuales de Inventario, Pedidos y Ventas;
DDL, catálogos y permisos relevantes de italian.sql. Dump leído, nunca importado;
no representa necesariamente producción.

**Alta no funcional demostrada:** la vista la marca «No terminado aún», comenta
Guardar y envía por AJAX a **Insumos/saveDontExist**. No existe ese método ni alias
en routes.php; el 404_override es Seguridad, no un guardado alternativo. Por tanto
el formulario no acredita un alta efectiva. Laravel conserva Agregar Insumo
deshabilitado y explica «Alta pendiente de definición», sin POST/store ni permiso
de creación. No se añade un formulario que prometa guardar ni una animación de
alta basada solo en el código de éxito de ese AJAX inalcanzable.

El método distinto **Insumos::save()**, no conectado por esa vista, intenta:

| Rama | Escritura pretendida | Problemas comprobados |
| --- | --- | --- |
| Base | INSERT tbl_insumos: Nombre, clave_pedido, categorias_insumos_id, unidad_id, unidad_pedido, unidad_final, inv_produccion, inv_fisico. | Omite Pedido_Pasteleria, Analisis_CV y Costo_Venta, NOT NULL sin DEFAULT en el dump. No escribe pedido aunque lea la bandera; queda DEFAULT 1. Usa unidad 13 si faltan producción/pedido. No valida texto, longitudes ni referencias en servidor. |
| Producción | Siete filas en tbl_inventario_productos, plazas **1–7**, stocks y Cantidad=0. | IDs fijos, no consulta catálogo ni decide altas posteriores de plazas. |
| Físico | Siete filas tbl_captura_inventario con cantidades cero, unidades Piso/Bodega/Total recibidas, Bandera=0, Centinela=1; luego tbl_insumos_inventario con Nombre/insumo_id/Bodega/Piso/Total y DLC/Pedido=*,1. | Chequea existencia pero ambos caminos INSERT; no hace UPDATE. formula_pedido se ignora. Transacciones separadas permiten datos parciales; banderaError puede perder un fallo previo al sobrescribirse con éxito posterior. |
| Remesa | Construye grupo_id=1, insumo_id, unidad_id, grupo_rol_id='*,1', Nombre='*,1'. | Vuelve a insertar **dataIF** (inexistente si no se activó Físico, duplicada si sí) en tbl_captura_inventario y envía ese array a tbl_insumos_inventario, que no tiene grupo_id/unidad_id/grupo_rol_id. Nunca inserta tbl_insumos_gastos; ignora categoría y límite elegidos. |
| Pedido | Lee bandera, clave, unidad y formula_pedido. | Guarda clave/unidad en la fila base, pero no persiste la bandera ni la fórmula elegida. No hay inicialización adicional demostrada. |

La vista ofrece categoría/nombre y Sí/No para producción/físico/remesa/pedido;
condiciona unidades, fórmulas (por defecto *,1), clave y categoría/límite de remesa
mediante JS y required HTML. checarBanderas exige al menos un destino; no hay
validación servidor equivalente para todos los demás campos. getGrupos excluye
24/26 de tbl_grupo_gastos. Estos datos describen la intención del formulario,
**no una regla aprobada que permita completar sus ramas rotas**.

La necesidad de preparar existencias y capturas por plaza está respaldada por los
lectores de esos módulos, pero no puede decidirse qué plazas inicializar, qué
campos omitidos usar ni cómo crear Remesas con seguridad. Se detiene solo el alta;
no se reproducen escrituras corruptas ni se crean filas por deducción. Hace falta
una definición del equipo antes de habilitarla. Tampoco existe edición general
de Nombre/categoría/unidades/banderas ni eliminación en este recorrido. Costo de
Venta es otra pantalla y no se migra aquí.

### Lectura, relaciones y unidades

Lista global sin filtro de plaza ni banderas, INNER JOIN a categoría y tres
unidades, como Mdl_productos. Laravel modela Ingredient, IngredientCategory e
IngredientConversion y reutiliza MeasurementUnit. No crea catálogos ni carga
opciones que solo necesitaría el alta bloqueada.

- tbl_insumos: Nombre VARCHAR(100), clave_pedido VARCHAR(45), categoría y las tres
  unidades; se conserva toda la fila, incluidos costos, categorías de pedido,
  banderas y orden de captura. tbl_categorias_insumos es la categoría de insumo,
  distinta de tbl_grupo_gastos y de las categorías de pedido/quebranto.
- tbl_unidades: unidad_id = inicial/producción, unidad_final = resultado de
  inventario, unidad_pedido = pedido. **La vista legacy intercambia UnidadPedido
  y UnidadFinal bajo U Fin/U Ped. Laravel corrige la correspondencia** en tabla,
  Copy, XLSX y PDF; conserva encabezados y los tres datos, sin modificar IDs.
- tbl_insumos_inventario: id PK, insumo_id FK **no única**, Nombre y operaciones
  Bodega/Piso VARCHAR(200), Total/DLC/Pedido VARCHAR(50), CVI/CVG VARCHAR(20).
  Sin timestamps. Solo Bodega/Pedido son editables por esta pantalla.

tbl_captura_inventario, tbl_insumos_gastos, tbl_inventario_productos, tbl_pedidos,
tbl_platillos_vendidos, tbl_productos_insumos y tbl_respaldos_inventario declaran
FKs a Insumos sin cascadas de borrado en el dump. Hay historial adicional con IDs
copiados; no se afirma que estas sean todas las referencias posibles de producción.
No se implementa DELETE, desactivación, SoftDeletes ni modificación del esquema.

IngredientsListing encapsula los joins/filtros y las seis columnas exportables:
# / Nombre / Clave Ped / U Ini / U Fin / U Ped. Paginación SQL de **300**, como la
pantalla DataTables; orden inicial **ID ASC**, correspondiente al orden implícito
de la primera columna de DataTables (el modelo previo ordenaba Nombre antes de
ese orden cliente). Se pueden ordenar las seis columnas con lista cerrada y
desempate por ID, buscar literalmente (incluidos %, _ y !) sobre datos visibles.
Excel/PDF «Datos Insumos» usan integraciones instaladas y consultas por lotes,
conservan todo el resultado filtrado/ordenado e ignoran page; omiten Operaciones.
Copy conserva título y solo página visible. XLSX es texto seguro; PDF escapa HTML
y desactiva recursos remotos/PHP/JS. No se promete memoria constante del documento.

### Conversiones: contrato y escritura migrada

La acción visible Operaciones consulta **todas** las filas de conversión del
insumo y abre «Configurando Insumo», con Bodega/Pedido/Editar. El modal de edición
solo envía esos dos campos y el ID de la fila. actualizarOperaciones valida
required y una expresión regular de pares operador,número, luego UPDATE por ID
de Bodega/Pedido. No toca unidades, existencias ni otros campos y no recalcula
historial. El regex legacy permite más pares que algunos consumidores soportan,
admite separadores omitidos y no excluye divisiones por cero.

| Campo | Consumidor comprobado y significado | Ejemplo |
| --- | --- | --- |
| Bodega (editable) | Inventario::consultarBodega → Mdl_inventario::getOperacionBodega → _calcularFormula. Convierte la captura de bodega antes de sumarla con Piso. Ejecuta como máximo tres pares en orden, sin precedencia algebraica. | *,25 convierte 2 cajas en 50 unidades; -,615,/,1000 transforma 1615 en 1. |
| Pedido (editable) | Pedidos::conversorPedido → Mdl_inventario::getOperacionPedido; se usa al cuadrar, recibir y completar faltantes. Solo ejecuta **un** par; con más devuelve 0. Guarda el resultado en Cantidad_Final. | *,150 convierte 2 unidades de pedido en 300 de inventario; /,1000 transforma 1500 en 1.5. |
| Piso (preservado) | Inventario::consultarPiso, mismo motor, convierte captura de piso. | -,615,/,1000 representa descontar tara y convertir escala. |
| Total (preservado) | Inventario::consultarTotal convierte la suma de Bodega/Piso, después de sus conversiones. | /,25 transforma una suma de 50 en 2. |
| DLC (preservado) | Existen Inventario::consultarDLC y Mdl_inventario::getOperacionDLC, pero no se encontraron llamadas al método privado consultarDLC. Su efecto activo no queda demostrado por esa definición; no se migra ese flujo. | /,1000 expresa gramos a kilogramos cuando esas sean las unidades del insumo; no acredita su aplicación efectiva en DLC. |
| CVI/CVG (preservados) | Ventas::conversorOperacionCVI/CVG y Mdl_ventas, conversiones para análisis de costos; lectores admiten un par. | *,2 duplica el valor de entrada. No se activan aquí costos ni Ventas. |

Los ejemplos explican la aritmética; las dimensiones concretas dependen del
insumo y sus unidades, no del nombre de la columna. Laravel **guarda el contrato
textual compatible**, no evalúa expresiones ni migra el motor de Inventario/Pedidos.
Los nuevos valores afectan cálculos futuros de CodeIgniter si ambas aplicaciones
comparten realmente la BD; no se recalculan cantidades almacenadas o historial.

CompatibleConversion/Form Request valida longitudes del esquema, pares cerrados
con *, +, -, /, coeficientes decimales no negativos, separadores completos,
operandos finitos y divisor distinto de cero. Bodega admite hasta tres pares,
Pedido uno, por las capacidades efectivas de sus consumidores. Espacios externos
se recortan por middleware; no se altera/reinterpreta silenciosamente una fórmula
existente. **Diferencia de seguridad:** se rechazan cadenas que el editor legacy
aceptaba pero los consumidores ignoraban o reducían a cero. No se modifican
operaciones existentes hasta un guardado válido y explícito.

UpdateIngredientConversion usa una transacción, bloqueo de fila y binding hijo
acotado al insumo. Recomprueba referencias y que haya exactamente una conversión
con el ID esperado. Sin filas muestra vacío (sin fabricar defaults); con varias
lista los datos pero bloquea edición con explicación y rechaza PUT. Los lectores
legacy toman una fila sin orden y no puede deducirse cuál debe gobernar. Cambiar
el ID para editar una conversión de otro insumo, un ID obsoleto o una referencia
inexistente devuelve error/404 sin escrituras. Locks coordinan escritores Laravel,
no aseguran coordinación con todos los escritores CodeIgniter ni certifican
concurrencia MySQL. Cada UPDATE escribe exclusivamente Bodega/Pedido.

### Permisos, UI y activación

Backend_lib controla las URLs vía menú/Leer. Dump: menús 41 Insumos, 42
Insumos/save, 212 consultarOperacionesInsumo y 279 actualizarOperaciones tienen
Leer=1 para rol 3 Gerente; Dueño tiene Leer=0 en 41/42 y no hay otras concesiones
en esos menús. La consulta LIKE de getID no se copia a Laravel. No certifica
permisos desplegados ni convierte mostrar Agregar en un alta funcional.

IngredientsPermissionsSeeder crea **solo** products.ingredients.view y
products.ingredients.conversions.update (`web`), inicialmente para root. Rutas y
Form Requests comprueban lectura/escritura; el panel products.view sigue siendo
independiente. Sidebar y tarjeta Insumos se activan según lectura; no se amplían
permisos de Usuarios/Configurar/Medidas, otros roles ni autenticación.

**Activación pendiente en la instalación del responsable:** después de confirmar
conexión/destino y root/web, provisionar únicamente
`sail artisan db:seed --class=IngredientsPermissionsSeeder`.
No ejecutar DatabaseSeeder, migraciones ni importar el dump. El seeder conserva
permisos ajenos/directos y asignaciones de usuarios; repetirlo restablece la matriz
inicial de sus dos permisos, como los seeders previos. Aquí se ejecutó únicamente
en SQLite efímera. No se cambian seeders anteriores para activarlo por accidente.

Blade/CSS/JS propios reutilizan x-ui.* y products.layout. Operaciones conserva
modal de consulta y modal de edición, con botones verde/cian, campos, mensajes,
Escape/foco, errores anunciados y reapertura con valores inválidos, bloqueo de
doble envío/restauración pageshow. La consulta conserva GET autorizado con enlace real; desde la mejora del
2026-10-18, JavaScript intercepta el enlace mediante fetch y extrae únicamente
los diálogos de la respuesta HTML, sin reemplazar el documento. La escritura
conserva PUT con CSRF y la misma Action/Form Request; devuelve JSON si el cliente
lo solicita y mantiene redirect/GET para el envío tradicional. Se conservan
filtros y página tras guardar. Éxito «Insumo actualizado!!», check animado superior derecho,
sin OK y **1500 ms**, como ese recorrido legacy. No se dispara otra navegación
desde el temporizador y nunca se muestra éxito con errores. Sin nuevas dependencias.

### Validación y decisiones pendientes

IngredientsTest: **43 pruebas / 337 aserciones**. Regresión combinada con
ProductsUnitsTest, ConfigurationTest y UsersReadTest: **135 pruebas / 1157
aserciones**, todas correctas. PHP 8.5.11 / PHPUnit 12.5.39, imagen existente
italian-cloud-php:8.5 con --network none, conexión nueva SQLite :memory: explícita,
caches/vistas en /tmp. Fixtures sintéticos, sin migraciones/seeders en MySQL,
importación, conexión ni escrituras sobre una BD compartida.

Cobertura nueva: invitados/cinco roles, permisos independientes/directos y seeder
repetible, enlaces del panel/sidebar; alcance global y joins, unidades corregidas,
escape, búsqueda literal, seis órdenes y páginas de 300; XLSX real de 505 filas
filtradas (supera el lote 500), orden/columnas/texto seguro; PDF real con fuente
HTML inspeccionada y opciones seguras; gramática/longitudes de conversiones,
cuatro operadores, cadenas compatibles, entradas inválidas, división por cero,
rollback tras fallo después del UPDATE, campos y tablas relacionados intactos,
relaciones inexistentes, IDs cruzados/obsoletos y conversiones cero/múltiples.
Comprueba ausencia de POST/alta, edición general y DELETE, en vez de simular un
alta que no está habilitada. La edición no necesita inicialización ni efectos
secundarios: se verifica que existencias, remesas, pedidos e historial no cambian.

Vite compila a /tmp/italian-ingredients-vite-build usando dependencias existentes;
Pint sobre PHP cambiado, node --check de products.js y git diff --check pasan.
Harness externo de render Laravel: **1 prueba / 12 aserciones**. Chromium/Playwright
sobre esas vistas reales con fixtures: escritorio 1366 y móvil 390, Copy seguro
sin acciones, unidades bajo encabezados correctos, enlaces/estados vacíos,
alta inerte, diálogos consulta/edición, datos con comillas/HTML escapados,
foco/Escape/regreso al listado, errores/reapertura, duplicados bloqueados,
éxito sin OK y cierre a 1500ms, reduced-motion, doble envío y restauración
pageshow, sin errores JS. Capturas inspeccionadas en
/tmp/italian-ingredients-visual. No es una sesión E2E contra CodeIgniter/MySQL.

Archivos: modelos Ingredient/IngredientCategory/IngredientConversion,
IngredientsListing, CompatibleConversion, IngredientsReadRequest y
SaveIngredientConversionRequest, UpdateIngredientConversion, IngredientsController,
IngredientsPermissionsSeeder, vista products/ingredients y IngredientsTest;
rutas, navegación, panel, CSS/JS de Productos y variante cian del componente
visual compartido. No se eliminaron archivos ni cambiaron AGENTS.md, legacy,
esquema, autenticación, dependencias, lockfiles, infraestructura o Vite config.
Entrega local, sin publicar ni desplegar en esta intervención.

Pendiente con el equipo: alta real de Insumos (campos obligatorios, flags y
conversiones iniciales, plazas actuales/futuras y contrato correcto de Remesas);
resolver filas duplicadas/ausentes de conversión si aparecen en una instalación;
decidir si se amplían los motores consumidores en una etapa posterior, antes de
aceptar cadenas más largas. No se presupone necesidad de editar catálogo general
ni eliminar insumos desde esta pantalla. Pendiente validación con datos reales:
permisos, relaciones instaladas, charset/collation, locks/concurrencia MySQL,
sesiones/CSRF E2E y efectos visibles de las conversiones en CodeIgniter coexistente.


## Ajuste de interacción de conversiones y formulario de Usuarios — 2026-10-18

Petición del responsable: conservar también la interacción visible del legacy.
Insumos/list.php consulta y guarda operaciones mediante AJAX; al guardar muestra
«Insumo actualizado!!» arriba a la derecha, sin OK durante 1500 ms, y luego cierra
ambos modales. La implementación anterior Laravel usaba navegación/formulario
tradicional, con recarga completa; esa diferencia se corrige mediante mejora
progresiva con JavaScript nativo/fetch.

- Abrir Operaciones consulta la ruta GET existente con fetch, importa únicamente
  los diálogos de la respuesta HTML y mantiene el listado, búsqueda, orden y
  página actuales. Cerrar/Escape devuelve el foco al enlace sin navegar.
- Guardar envía FormData con CSRF y `_method=PUT`, `Accept: application/json` y
  credenciales same-origin. IngredientsController conserva validación, permisos,
  binding y transacción originales; solo agrega respuesta JSON tras éxito.
  El formulario tradicional sigue recibiendo redirect/GET.
- Validación 422 muestra mensajes con textContent y marca los campos inválidos;
  401/419, otros errores y fallos de red no muestran éxito. Ante una respuesta
  incierta no se reenvía automáticamente. Se bloquean envíos duplicados y el
  cierre de los diálogos mientras la petición está en curso.
- Éxito reutiliza x-ui.feedback con apertura manual (`autoOpen=false`), check y
  popup animados, posición superior derecha, sin OK y 1500 ms; después cierra
  ambos diálogos sin navegar y restaura el foco. Respeta el CSS existente de
  prefers-reduced-motion. La inicialización automática de otros módulos conserva
  su comportamiento por defecto.
- Sin JavaScript, los enlaces siguen navegando normalmente. Un noscript muestra
  consulta y formulario como secciones con Bodega/Pedido precargados; el envío
  tradicional funciona con las mismas reglas y CSRF. El éxito tiene texto visible
  alternativo sin depender de abrir un dialog desde JavaScript.
- Editar Usuario no cargaba users-read.css, aunque el formulario compartido usa
  sus clases. La vista ahora carga esa hoja existente: etiquetas sobre los campos,
  ancho completo, altura mínima de 34 px y separación vertical de 15 px, también
  en móvil. No se cambian campos ni validaciones. La contraseña permanece vacía
  para conservar la actual; no se expone el hash como hacía el legacy.

Validación de esta intervención: PHP 8.5.11 / PHPUnit 12.5.39, contenedor existente
sin red, SQLite :memory: explícita y caches/vistas en /tmp. Insumos + Usuarios +
Medidas + Configurar: **138 pruebas / 1172 aserciones**, todas correctas. Tres
pruebas nuevas cubren JSON sin redirect, cambios limitados a Bodega/Pedido,
validación/permisos sin escritura y HTML tradicional precargado. Vite compila en
/tmp; node --check, Pint sobre PHP modificado y git diff --check pasan.

Comprobación Chromium/Playwright con HTML renderizado por Laravel y fixtures
sintéticos, endpoints fetch simulados: escritorio 1366 y móvil 390; apertura,
guardado, 422, confirmación y cierre sin navegación del documento; foco/Escape,
campos de Usuarios a ancho completo y contraseña vacía; formulario sin JavaScript
visible y precargado. Sin errores JavaScript. Estos checks de navegador no son
una sesión E2E con autenticación/CSRF/MySQL reales: esa validación permanece
pendiente en la instalación del responsable. No se modifican legacy, esquema,
AGENTS.md, dependencias, autenticación ni infraestructura.

## Productos, etapa 3: catálogo y fórmulas (2026-10-18)

### Recorrido y tablas comprobados

Revisión dirigida de Productos::list/save/activarProducto/desactivarProducto,
Formulas/Mdl_formulas, métodos de catálogo/escritura de Mdl_productos,
productos/productos/list.php y vistas antiguas de formulas; tables.js para
tbl_datos; Backend_lib/Mdl_backend; DDL/datos de familias, productos, relaciones,
menús/permisos y FKs relevantes de italian.sql. Se siguieron
Mdl_inventario::conversion y lectores de Mdl_unidades/Mdl_depositos para entender
historial. No se importó el dump ni se consultó una instancia compartida.

El recorrido activo es Productos → Productos/list. Lista global #, Clave, Nombre,
Familia y botón cian «+» de Fórmula; filtro de familia; productos activos e
inactivos, estos últimos rojo/naranja. DataTables tbl_datos: 80 filas, ID ASC,
búsqueda/orden y Excel/PDF/Copy con título «Datos». La tabla del modal de fórmula
no tiene búsqueda/exportación propia en este recorrido.

Productos/save lee nombre/clave/familia_id y la vista marca los tres obligatorios.
consultarExistencia rechaza Nombre **o** Clave repetidos. Mdl_productos::save
inserta exclusivamente tbl_productos con Estado=1 en una transacción; no crea
inventarios, plazas ni receta por defecto. DDL: Nombre varchar(100) UNIQUE,
Clave varchar(50) UNIQUE, familia_id FK a tbl_familia_productos, Estado tinyint.
No hay edición general ni DELETE de productos en este controlador.
activarProducto/desactivarProducto solo actualizan Estado=1/0; no tienen botones
en esta pantalla ni entradas específicas en tbl_menus del dump. No se migran.
getProductos y Mdl_inventario::conversion tampoco filtran Estado: desactivar no
equivale a retirar receta o impedir cualquier consumo por esos lectores.

Formulas::index está comentado; no existen list/add aunque las vistas antiguas
panel_formulas/list los enlazan. Formulas::update permite cambiar producto/insumo,
pero no es el endpoint del editor activo. No se activan estos recorridos antiguos.

La fórmula es el conjunto de filas de **tbl_productos_insumos**: id, producto_id,
insumo_id, **Cantidad FLOAT**. FKs a tbl_productos.id y tbl_insumos.id sin cascadas;
no hay índice UNIQUE del par, cabecera de receta, unidad persistida, orden,
rendimiento, costo ni versión. El dump contiene **2562 filas**, **seis pares
producto/insumo repetidos**, **seis cantidades cero** y ninguna negativa en esa
tabla. El dump no certifica el estado actual de producción.

### Cantidades, unidades y efectos secundarios

El modal activo muestra Nombre / Insumo / Cantidad / Unidad / Editar / Eliminar.
getFormulaModalLeer y consultarUnidad usan **tbl_insumos.unidad_id**. La selección
unidad_id_formulas enviada por el formulario es ignorada por store: no se elige
una dimensión independiente ni se convierte cantidad. Ejemplo: unidad_id=Gramos,
Cantidad=7.7 expresa 7.7 en esa unidad para la asociación al producto; no se
infiere una dimensión solo por el nombre del ingrediente.

El catálogo getInsumosInventario exige inv_produccion=1 y joins a categoría y
**unidad_final**, orden por ID. Existe una diferencia entre esa unidad de catálogo
y la mostrada por el editor (unidad_id). Laravel conserva el catálogo y además
exige unidad inicial válida; no sustituye unidad_id por unidad_final ni evalúa las
conversiones de Insumos. Las asociaciones existentes siguen visibles/editables
si conservan insumo/unidad_id válidos y no están duplicadas, aunque el insumo ya
no sea elegible para nuevas altas, igual que la consulta activa legacy.

Formulas/store evita repetir el par e inserta; no valida cantidades server-side.
actualizarFormula (editor activo) actualiza solo Cantidad por ID e is_numeric,
sin comprobar pertenencia al producto. Input de edición min=0, step=0.001; alta
step=any sin min. No hay orden semántico de componentes ni operaciones encadenadas
entre filas. Laravel usa ID para un orden estable, sin inventar un reordenador.

Los escritores comprobados no calculan costos/rendimientos, cantidades derivadas
ni existencias; no escriben Inventario, Remesas, Pedidos ni Ventas. Se conserva
esa ausencia de efectos secundarios. Mdl_inventario::conversion(Clave) consulta
Cantidad/insumo_id mediante la clave del producto, pero no se encontró una llamada
directa en el PHP revisado. La definición no demuestra un consumo activo;
consumidores dinámicos, externos o desplegados quedan pendientes de validación.

Cambiar nombre de insumo/unidad cambia el texto por joins, sin cambiar Cantidad.
Cambiar unidad_id reinterpretaría cantidades almacenadas sin conversión: no se
migra ese cambio general de insumos. Cambiar flags/categoría/unidad_final afecta
la elegibilidad del catálogo. Eliminar insumos encuentra las FKs de receta e
históricos ya descritas en la etapa 2; no se habilita esa acción.

### Eliminaciones y límites

Formulas/delete hace DELETE físico por ID de tbl_productos_insumos, sin validar
pertenencia, versionar receta ni modificar históricos. No se encontró FK entrante
a la fila. tbl_platillos_vendidos guarda Clave, insumo_id, Cantidad, Unidad y Total;
tbl_platillos_vendidos_resumen también referencia Clave. Son referencias/valores
por producto/insumo, no por ID de fórmula. Mdl_unidades/Mdl_depositos unen reportes
por Clave. Las FKs de históricos y receta bloquean borrados de productos
referenciados sin cascada en el dump; cambiar Clave tampoco es una edición inocua.

**Eliminar fórmula queda deshabilitado**, sin rutas DELETE/SoftDeletes. No se
certifica qué debería pasar con procesos futuros o reconstrucciones históricas
al retirar un componente: definir retirada/versionado con el equipo. No se
migran edición/estado/borrado general de productos, ni el antiguo cambio de
producto/insumo en Formulas::update. No se escogen/mezclan/borran duplicados.

### Implementación y diferencias intencionales

- Product/ProductFamily/ProductIngredient encapsulan las tres tablas nuevas;
  reutilizan Ingredient/MeasurementUnit y sus catálogos. ProductsListing contiene
  joins/filtros/orden y ProductFormulas elegibilidad/lectura. Dos controladores
  separan catálogo y fórmulas sin ampliar ProductsController (panel/Medidas).
- Rutas products.catalog.index/create/store/export y
  products.formulas.index/edit/store/update. Se conserva /Productos/list y se
  anidan fórmulas en /Productos/{product}/formulas con binding hijo acotado.
- Form Requests: requeridos, longitudes reales, familia/insumo/unidad existentes,
  duplicados Nombre/Clave y cantidades numéricas finitas **>=0** representables
  en FLOAT (sin desbordar ni subdesbordar a cero). Tildes permitidas; step=any
  conserva precisión de entrada del alta sin truncar a tres decimales. FLOAT
  conserva precisión limitada temporal y SQLite no certifica redondeo MySQL.
  Rechazar negativos en alta corrige incoherencia con min=0 de edición, sin
  reescribir datos existentes. No se inventan cálculos de costo/rendimiento.
- La unidad es derivada: JS muestra la del insumo y servidor compara unit_id con
  unidad_id actual; si cambió desde la apertura rechaza el guardado. No persiste
  unit_id. Edición solo cambia Cantidad y rechaza mover producto/insumo/unidad.
- SaveFormula bloquea producto, relación e insumo en transacción y recomprueba
  referencias/duplicados. Los pares repetidos se listan con aviso y se bloquea su
  edición; huérfanos de insumo/unidad también permanecen visibles y bloqueados.
  No se escriben productos con familia inválida. Alta de producto transforma una
  violación UNIQUE concurrente en error de validación. Los locks coordinan
  escritores Laravel, no certifican coordinación con CodeIgniter/concurrencia MySQL.
- XLSX/PDF reutilizan integraciones y TabularSpreadsheet para todo el resultado
  filtrado/ordenado ignorando page; consultas por lotes, XLSX como texto seguro,
  PDF escapado/sin remotos/PHP/JS; sin columna de acciones. Copy solo página visible.
- Blade reutiliza x-ui.listing/table/toolbar/pagination/sort-heading/table-action
  y feedback. Solo se añadió icono «+» a action-icon. Productos inactivos coloreados,
  tabla de receta con scroll local móvil. product-catalog.js se importa desde
  products.js existente, sin nuevas dependencias ni configuración Vite.
- Abrir/añadir/editar fórmulas usa fetch y refresca solo el panel. Éxitos arriba
  derecha con check animado, sin OK y **1000 ms**: alta mantiene formulario abierto
  con cantidad vacía; edición cierra editor y conserva consulta. Alta de producto
  navega al listado tras el éxito como location.reload legacy. Errores 422 dentro
  del formulario, sin éxito ni pérdida de valores; errores sesión/permisos/red y
  respuestas inciertas nunca reenvían automáticamente. Duplicados de envío
  bloqueados, foco/Escape y reduced-motion existentes conservados. Sin JS,
  enlaces/formularios HTTP con CSRF siguen funcionando mediante secciones noscript;
  unidad elegida del catálogo siempre debe coincidir con el insumo en servidor.

### Permisos y activación

Dump: Gerente (3) tiene Leer=1 para menús Productos (72), list (73), save (74),
Formulas/store (34), update (35), delete (36), consultarFormula (37),
consultarFormulaModal (38), actualizarFormula (39), consultarUnidad (40).
Formulas raíz (32) tiene Leer=0. Dueño tiene Leer=0; no se encontraron concesiones
de esos menús a otros roles. Backend_lib exige login/Leer mediante lookup LIKE;
esa coincidencia de URLs no se copia ni autoriza operaciones nuevas.

ProductCatalogPermissionsSeeder define exclusivamente products.catalog.view/create
y products.formulas.view/create/update, guard web, inicialmente root. Lectura de
catálogo requerida para todo; fórmulas exige además lectura y permiso por escritura.
products.view del panel sigue independiente. Tarjeta Productos y entrada sidebar
se activan con catalog.view, sin ampliar otros módulos/roles. No hay permisos de
eliminar ni de edición/estado general del producto.

**Activación pendiente en la instalación del responsable:** confirmar conexión y
destino, luego `sail artisan db:seed --class=ProductCatalogPermissionsSeeder`.
No ejecutar DatabaseSeeder, migraciones ni importar el dump. Aquí solo se ejecutó
el seeder en fixtures SQLite :memory:. Repetible, conserva permisos ajenos/directos
y asignaciones de usuarios, restablece la matriz inicial de estos cinco permisos.

### Validación y pendientes

PHP 8.5.11/PHPUnit 12.5.39, imagen existente --network none, SQLite :memory:
explícita y caches/vistas en /tmp: **39 pruebas nuevas/258 aserciones**;
combinadas con Usuarios/Configurar/Medidas/Insumos: **177 pruebas/1430 aserciones**,
todas correctas. Invitados/cinco roles, permisos por acción/seeder/directos/ajenos,
filtros/escape/búsqueda literal/orden/páginas80; XLSX real de 506 filas filtradas
superando lote500, orden/columnas/texto seguro y PDF real; altas HTTP/JSON,
campos/duplicados; cantidades y FLOAT, unidad inicial distinta de final,
elegibilidad, ceros/negativos/no finitos, unidad cambiada/campos manipulados,
IDs cruzados/obsoletos, huérfanos/duplicados, rollback producto/relación,
históricos intactos y ausencia de editar producto/estado/eliminar.

Harness externo render Laravel: **1 prueba/7 aserciones**. Chromium/Playwright con
esas vistas y respuestas fetch simuladas: 1366/390 px, listado/estado/Copy,
diálogos, errores422, cantidades/unidad derivada, éxito, doble envío, refresco sin
navegar, siguiente componente, Escape/foco, fallo de consulta y formularios sin
JS; vacío/duplicados. Capturas revisadas en /tmp/italian-catalog-visual.
Vite, node --check, Pint y git diff --check pasan. No es E2E de sesión/CSRF/MySQL real.

Pendiente: activar permisos en destino confirmado; validar collation/charset,
FLOAT/locks/concurrencia MySQL, sesiones/CSRF y consumidores externos/dinámicos;
resolver duplicados y definir retirada/versionado con el equipo. El alta rota de
Insumos sigue deshabilitada y pendiente de Javi. No se migran Costo de Venta,
Inventario, Remesas, Pedidos ni reglas de Ventas. No se modifican legacy, esquema,
AGENTS.md, infraestructura, dependencias ni lockfiles. Entrega local sin desplegar.
