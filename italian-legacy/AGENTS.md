# Guía para agentes — CodeIgniter

## Alcance y contexto

- Estas instrucciones aplican a todo este repositorio. Las instrucciones explícitas del usuario tienen prioridad.
- Si dos reglas entran en conflicto, priorizar en este orden: seguridad, preservar contratos existentes, alcance mínimo, estilo. Si una corrección de seguridad requiere cambiar un contrato, explicar el impacto y adaptar sus consumidores dentro del alcance autorizado.
- El proyecto utiliza PHP con CodeIgniter 3.1.9 y vistas renderizadas en servidor, con recursos JavaScript y CSS en `assets/`.
- La aplicación incluye usuarios, permisos, productos, insumos, inventario, pedidos, ventas, remesas, depósitos, nóminas y reportes.
- Trabajar dentro de este repositorio; no explorar otros proyectos salvo que la tarea lo requiera y el usuario lo autorice.
- Concentrar los cambios en `application/` y en los recursos propios de `assets/`. Modificar archivos raíz de configuración solo cuando sea necesario para la tarea.
- No modificar el framework en `system/`, dependencias en `application/third_party/`, `assets/Classes/` o `vendor/`, ni recursos de terceros, salvo solicitud explícita.
- No alterar archivos operativos o cargados por usuarios en `assets/files/`, `assets/filesPedido/`, `assets/filesPlatillos/` o `assets/remesas/`, ni contenido de `application/cache/`. `application/logs/` se puede leer para depurar, pero no modificar.
- No agregar ni actualizar dependencias sin revisar compatibilidad, impacto y necesidad. No migrar PHP, CodeIgniter ni el frontend por iniciativa propia.
- No generar documentación, reportes u otros artefactos adicionales salvo solicitud explícita.

## Estrategia de análisis

- Identificar primero el flujo y el controlador responsable; seguir sus modelos, vistas, librerías y recursos directamente relacionados.
- Abrir solo los archivos necesarios. Usar búsquedas dirigidas y ampliar al repositorio únicamente cuando no se conozca la ubicación del flujo.
- Para búsquedas de texto literal, preferir `rg -n -F` si ya está disponible; como alternativa usar `git grep -n -F --untracked -e '<texto>' -- application assets/js ':!assets/js/footer' ':!application/config/database.php' ':!application/config/config.php' ':!application/cache' ':!application/logs' ':!application/third_party'`. Usar expresiones regulares solo cuando la búsqueda lo requiera y conservar las exclusiones con cualquiera de las herramientas.
- Dirigir las búsquedas a archivos propios del flujo; excluir dependencias, archivos operativos y archivos con secretos de búsquedas generales. Ampliar las rutas solo cuando sea necesario (por ejemplo, archivos de la raíz o `tests/`); consultar logs de forma dirigida al depurar. No instalar ni ejecutar Codegraph ni indexar el proyecto por iniciativa propia.
- Antes de cambiar el contrato de un método de `Mdl_*`, buscar su nombre en `application/` y `assets/` para encontrar todos sus consumidores.
- Analizar de forma incremental: controlador → método → modelo o librería → vista y JavaScript consumidor.
- Revisar `application/config/routes.php` y el mecanismo de carga existente cuando el cambio afecte rutas, configuración o dependencias.
- No asumir que el comportamiento actual es correcto o seguro solo por ser un patrón existente. Corregir dentro del alcance solicitado; los hallazgos fuera de él (errores, riesgos de seguridad, valores de negocio hardcodeados) se reportan, no se corrigen.

## Arquitectura y convenciones

- Mantener la estructura MVC de CodeIgniter 3:
  - `application/controllers/`: entrada HTTP, validación, permisos y coordinación del flujo.
  - `application/models/`: consultas y persistencia, conservando el patrón de nombres `Mdl_*` existente.
  - `application/views/`: presentación y uso de los layouts compartidos en `application/views/layouts/`.
  - `application/libraries/` y `application/helpers/`: lógica reutilizable según las convenciones existentes.
  - `application/config/`: rutas, carga y configuración de la aplicación.
  - `assets/js/` y `assets/css/`: comportamiento y estilos del frontend.
- Usar los mecanismos existentes de `$this->load`, `$this->input`, `$this->session`, `$this->db` y las librerías del proyecto.
- Evitar consultas SQL y lógica de negocio nueva en vistas, así como duplicar reglas entre controladores y JavaScript.
- Preferir cambios pequeños y locales; no mover lógica entre capas ni introducir abstracciones amplias sin una necesidad concreta.
- Conservar nombres de clases, métodos, archivos, campos y tablas; respetar mayúsculas y minúsculas para compatibilidad entre sistemas operativos.
- Mantener el estilo del archivo editado y evitar reformateos ajenos al cambio. Para archivos nuevos, seguir `.editorconfig`: UTF-8, finales de línea LF y salto final.
- Verificar la versión real de PHP del entorno antes de introducir sintaxis o APIs nuevas. El mínimo declarado en `composer.json` no confirma la versión desplegada.

## Convenciones del proyecto

- Los controladores internos protegidos replican el patrón existente: `parent::__construct()`, `$this->permisos=$this->backend_lib->control()` y carga de modelos con `$this->load->model()`. `backend_lib->control()` ya comprueba la sesión y redirige a `base_url()` si no existe login; evitar duplicar esa comprobación cuando ya cubra el flujo.
- Los controladores de acceso y autenticación, como `Seguridad`, conservan sus controles específicos. Antes de aplicar `backend_lib` a una ruta nueva, revisar su relación con menús y permisos; no asumir que el permiso de lectura autoriza crear, editar o eliminar.
- Las pantallas completas se cargan en este orden: `layouts/header`, `layouts/aside`, la vista correspondiente y `layouts/footer`.
- Los endpoints AJAX responden con códigos numéricos (`echo 1/0/2`) o con JSON. Conservar el formato que espera el JavaScript consumidor; no cambiarlo por iniciativa propia.
- Usar `html_escape()` para texto HTML y atributos entre comillas; para datos JavaScript usar serialización JSON adecuada al contexto y para URLs validar el destino y codificar sus componentes. Validar entradas con `form_validation` y ejecutar SQL manual solo con bindings (`$this->db->query($sql, $binds)`).

## Contratos y datos

- Antes de cambiar un endpoint, revisar sus formularios, vistas y llamadas AJAX consumidoras.
- Preservar rutas, nombres de parámetros, estructuras JSON, códigos de respuesta, redirecciones y mensajes de los que dependan los consumidores, salvo que la tarea requiera modificarlos.
- Usar Query Builder o parámetros enlazados; no concatenar entradas del usuario en SQL. Validar con listas permitidas los nombres dinámicos de columnas y criterios de ordenación.
- Mantener la separación de datos por plaza, usuario y rol cuando corresponda.
- En inventario, pedidos, remesas, ventas, depósitos y nóminas, revisar unidades, cantidades, importes, redondeos, estados y fechas antes de alterar cálculos.
- Usar transacciones cuando varias escrituras deban completarse juntas; revisar errores, concurrencia y posibles envíos duplicados en operaciones sensibles.
- No ejecutar cambios de esquema, cargas masivas ni operaciones destructivas sobre bases de datos sin autorización explícita. No asumir acceso a una base de pruebas.
- Evitar consultas dentro de ciclos, consultas redundantes y cargas completas de datos cuando el flujo pueda filtrarse o paginarse.

## Seguridad y frontend

- Reutilizar los controles de sesión y permisos existentes, incluido `backend_lib` cuando aplique. Verificar autorización en el servidor para cada operación sensible.
- La visibilidad de botones y `$this->input->is_ajax_request()` no sustituyen la autorización.
- Validar entradas del servidor, tipos, identificadores, rangos y reglas de negocio; no depender únicamente de validaciones JavaScript.
- Escapar los datos dinámicos según el contexto HTML, atributo, URL o JavaScript. Evitar insertar contenido no confiable como HTML.
- Revisar la protección CSRF existente al modificar formularios o AJAX; no desactivarla para resolver errores.
- Validar tipo, tamaño, nombre y destino de archivos cargados; impedir rutas arbitrarias y ejecución de archivos subidos. Revisar acceso y descarga de reportes y archivos operativos.
- No exponer credenciales, tokens, datos personales o financieros en respuestas, logs, documentación ni mensajes al usuario. No copiar secretos de configuración al analizar el proyecto.
- `application/config/database.php` y `application/config/config.php` contienen credenciales y claves (por ejemplo `encryption_key`): excluirlos de búsquedas generales. Cuando la tarea requiera revisar configuración, consultar únicamente las opciones pertinentes y ocultar valores sensibles antes de mostrarlos; nunca copiar secretos a respuestas ni artefactos.
- Reutilizar los mecanismos de configuración del proyecto; no hardcodear secretos ni URLs específicas de ambientes.
- Mantener layouts, selectores, eventos y comportamiento responsive del flujo afectado. No introducir frameworks de frontend por iniciativa propia.
- Manejar errores de AJAX y evitar envíos duplicados cuando corresponda.

## Validación

- Ejecutar las comprobaciones disponibles y pertinentes al cambio. Para PHP, usar `php -l <archivo>` sobre los archivos modificados cuando el intérprete esté disponible; con XAMPP suele estar en `C:\xampp\php`.
- Para cambios funcionales, validar el flujo afectado, entradas inválidas, permisos y manejo de errores en un entorno adecuado y autorizado.
- Para cambios visuales, revisar renderizado, navegación y comportamiento responsive cuando se disponga de la aplicación ejecutándose.
- `tests/phpunit.xml` configura pruebas del núcleo de CodeIgniter; no asumir que validan los flujos de negocio de `application/`.
- No agregar pruebas por defecto para cambios menores. Añadir pruebas enfocadas cuando el usuario las solicite o sean necesarias para validar un cambio relevante; reutilizar las herramientas existentes.
- No instalar frameworks de pruebas ni cambiar dependencias solo para ejecutar validaciones sin revisar primero su impacto.
- Si falta PHP, la aplicación, una base de pruebas u otra dependencia, informar qué se verificó y qué quedó pendiente; no afirmar que una prueba pasó si no se ejecutó.

## Flujo de trabajo y comunicación

- Responder en español, de forma breve y directa, salvo indicación del usuario.
- Antes de modificar varios archivos, explicar brevemente el objetivo y mantener el alcance mínimo necesario.
- Avanzar sin pedir confirmación innecesaria cuando el alcance sea claro; pedir aclaración si una decisión cambia el resultado solicitado o exige una acción no autorizada.
- Respetar cambios existentes del usuario y no sobrescribirlos ni revertirlos.
- No crear commits ni ramas, publicar cambios o desplegar por cuenta propia. Hacerlo únicamente cuando el usuario lo solicite explícitamente.
- Evitar repetir contexto y reanalizar hallazgos ya confirmados. No crear archivos de memoria o resúmenes sin necesidad o solicitud.
- Al finalizar, indicar qué cambió, qué se validó y cualquier limitación relevante.
