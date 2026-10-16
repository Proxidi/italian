# Reglas de trabajo — Migración CodeIgniter a Laravel

## Alcance

Este repositorio contiene dos sistemas relacionados:

- `legacy/`: sistema original en CodeIgniter 3. Es la referencia funcional y visual.
- `laravel/`: nueva implementación en Laravel 13. Es el único sistema que debe modificarse salvo instrucción explícita.

Si la estructura real del repositorio combinado difiere de esos nombres, inspeccionarla antes de editar y adaptar estas reglas a las rutas reales.

## Principio de migración

La migración conserva:

- reglas de negocio;
- comportamiento visible;
- datos y alcances relevantes;
- permisos efectivos;
- experiencia de usuario que el cliente espera.

La migración NO debe copiar innecesariamente:

- arquitectura interna de CodeIgniter;
- acoplamientos entre controladores;
- consultas o modelos legacy si Laravel ofrece una solución mejor;
- dependencias globales antiguas;
- autorización basada en coincidencias de URL;
- JavaScript/jQuery/AdminLTE/DataTables solo por paridad interna.

El legacy es referencia funcional y visual, no una arquitectura que deba reproducirse.

## Fidelidad visual

Las pantallas Laravel deben parecerse lo más posible al legacy cuando esa apariencia siga siendo relevante para el cliente.

No convertir una migración en un rediseño sin autorización explícita.

Se permite cambiar la implementación interna, pero deben preservarse en lo posible:

- distribución;
- jerarquía visual;
- tamaños;
- espaciados;
- colores;
- tablas;
- botones;
- etiquetas;
- interacción visible.

Cuando una acción legacy todavía no esté migrada, puede conservarse visualmente como control deshabilitado e inerte si eso mejora la fidelidad.

## Stack destino

- Laravel 13.
- PHP 8.5.
- MySQL 8.4.
- Laravel Sail / Docker.
- Blade + Vite.
- CSS propio y JavaScript nativo cuando sea suficiente.
- Autenticación web tradicional con guard `web` y sesiones nativas.
- Autorización Laravel con `spatie/laravel-permission`.
- Validación mediante Form Requests.
- PHPUnit para pruebas automatizadas.

No introducir Passport, Sanctum, Bootstrap, AdminLTE, jQuery o DataTables como solución general salvo que una necesidad real y justificada lo requiera.

## Convivencia temporal con CodeIgniter

CodeIgniter y Laravel comparten temporalmente la misma base de datos.

`tbl_usuarios.Password` usa SHA-1 porque CodeIgniter todavía depende de ese formato.

Mientras exista convivencia:

- Laravel debe validar temporalmente ese SHA-1.
- Laravel NO debe rehashear ni reemplazar `tbl_usuarios.Password`.
- No usar cast `hashed`, `Hash::make()`, `Hash::needsRehash()` ni migración automática de contraseña durante login.
- La migración a un hash moderno será una etapa explícita posterior al retiro de CodeIgniter.

## Roles y permisos

Mapeo temporal confirmado:

- `1` Dueño → `superadmin`
- `2` Manager → `admin`
- `3` Gerente → `root`
- `4` SubManager → `submanager`
- `5` Socio → `socio`

Reglas:

- `admin` y `submanager` son roles distintos aunque compartan actualmente parte de la UI.
- No crear un bypass global para `superadmin`.
- Durante la convivencia, `tbl_usuarios.rol_id` sigue siendo la fuente legacy de rol y debe mantenerse sincronizado con Spatie cuando se modifique un usuario.
- No usar `rol_id` directamente como nuevo mecanismo de autorización Laravel.
- La autorización de Laravel debe expresarse con permisos semánticos Spatie.

## Navegación y autorización

En el legacy, navegación y autorización son mecanismos separados:

- sidebar/dashboard: principalmente lógica hardcodeada por `rol_id`;
- autorización: `Backend_lib::control()`, `tbl_menus`, `tbl_permisos` y excepciones específicas.

Por tanto, cambiar permisos legacy no garantiza que la navegación cambie.

En Laravel:

- ocultar un enlace nunca sustituye la autorización;
- las rutas/acciones deben comprobar permisos en servidor;
- la navegación debe responder a esos mismos permisos semánticos cuando corresponda;
- los roles pueden definir panel/estructura base, pero las capacidades concretas deben depender de permisos.

No importar mecánicamente `tbl_menus`/`tbl_permisos` uno a uno. Caracterizar cada módulo y diseñar permisos semánticos.

## Rutas

Preservar funcionalidad y comportamiento, no necesariamente URLs internas históricas.

Mantener una URL legacy solo cuando sea relevante para compatibilidad o experiencia externa.

No reproducir acoplamientos absurdos del legacy si Laravel puede expresar la responsabilidad con una ruta y permiso más semánticos.

## Decisiones transversales

Antes de introducir una dependencia o decisión arquitectónica que pueda reutilizarse en varios módulos —por ejemplo exportaciones, importaciones, PDF, archivos, autorización o almacenamiento—:

1. inspeccionar primero sus usos relevantes en todo el legacy;
2. inspeccionar el estado actual de Laravel;
3. justificar la decisión global;
4. evitar generalizar a partir de un único módulo.

Para cambios locales, inspeccionar solo el contexto necesario. No recorrer todo el legacy sin una razón concreta.

## Exportaciones

Contrato general acordado:

- Excel → descarga directa de `.xlsx` real;
- PDF → descarga directa de `.pdf`;
- Copy → debe reproducir el comportamiento efectivo del legacy de la tabla correspondiente;
- Excel/PDF deben respetar filtros y permisos;
- no exportar columnas de acciones salvo que exista una razón funcional;
- proteger contra inyección de fórmulas en hojas;
- escapar datos en PDFs;
- usar endpoints autorizados del lado servidor en Laravel.

Dependencias globales elegidas:

- `maatwebsite/excel` para XLSX/importaciones;
- `barryvdh/laravel-dompdf` para PDFs tabulares simples.

No usar PhpSpreadsheet ni Dompdf directamente en controladores salvo una justificación específica.

## Pruebas

Añadir pruebas proporcionales al riesgo y comportamiento de cada cambio.

Antes de considerar terminado un módulo:

- ejecutar sus pruebas específicas;
- compilar Vite si cambian assets;
- revisar manualmente el comportamiento visual si la fidelidad de UI importa;
- comprobar descargas reales cuando existan exports.

La suite conserva todavía pruebas scaffold de Breeze que no representan necesariamente el sistema actual. No intentar “arreglar” registro, password reset, verificación de email o perfil solo para satisfacer tests obsoletos. Revisar primero si ese comportamiento pertenece realmente a la migración.

## Seguridad y datos

- No modificar datos legacy innecesariamente.
- Mantener autorización server-side.
- Validar inputs con listas cerradas para sort/format/columnas.
- Evitar SQL dinámico basado directamente en input.
- Escapar salida HTML.
- Mantener CSRF en formularios mutables.
- No introducir borrado físico de usuarios sin decisión explícita.
- No cambiar esquema o índices sin justificarlo y verificar impacto sobre CodeIgniter.

## Documentación viva

Consultar `docs/migration-context.md` antes de modificar módulos relacionados.

Toda afirmación marcada como “pendiente de verificar contra el repositorio Laravel” debe comprobarse contra el código real antes de usarla como base.

Después de:

- completar una migración funcional;
- cambiar una decisión arquitectónica;
- introducir una dependencia transversal;
- descubrir una regla de negocio importante;

actualizar `docs/migration-context.md`.

No inventar estado del proyecto. Si algo no puede comprobarse, dejarlo explícitamente como pendiente.
