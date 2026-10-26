# Propuesta de página comercial SEO: Reloj checador para tablet

**Estado:** propuesta para revisión, sin implementación. **Fecha de análisis:** 26 de octubre de 2026.

## 1. Resumen ejecutivo y objetivo comercial

Crear una página pública que ayude a una empresa a decidir si puede usar una tablet como punto de registro de asistencia con Horalia y la conduzca al registro de su cuenta. La página debe explicar el flujo real del checador, sus requisitos y sus límites. Su enfoque será el **uso práctico en una tablet**, no una descripción general del software ni una guía de administración de personal.

**Decisión editorial:** describir primero el checador **web**, que ya tiene un flujo específico de activación mediante enlace. La aplicación Titanium puede mencionarse como desarrollo existente, pero no como aplicación disponible para descarga hasta verificar su publicación y funcionamiento en dispositivos reales.

## 2. Público objetivo y diferenciación

Dirigida a responsables de operaciones, recursos humanos y dueños de empresas que quieren habilitar un dispositivo compartido para registrar entradas, salidas y pausas del personal. También responde a quien evalúa los requisitos de cámara, ubicación y conexión antes de usar una tablet.

| Página | Intención principal | Enfoque que debe conservar |
| --- | --- | --- |
| Portada `/` | Conocer Horalia como reloj checador en línea | Propuesta general, funciones y precio. |
| `/control-de-asistencia-empleados` | Organizar y consultar la asistencia | Empleados, horarios, centros, seguimiento y reportes. |
| **`/reloj-checador-tablet`** | Usar una tablet como punto de checado | Activación del dispositivo, PIN, fotografía, ubicación, pausas y requisitos. |

Los H1 y primeros párrafos actuales están en [portada](resources/views/welcome.blade.php) y [control de asistencia](resources/views/control-asistencia.blade.php). La nueva página debe responder preguntas operativas específicas y enlazar a las otras dos cuando el lector quiera conocer el sistema completo o la gestión posterior.

## 3. Funcionamiento real comprobado en el código

### Checador web en tablet

1. **Preparación:** un usuario autenticado con facultad para administrar una empresa activa entra a `/home/checador`, selecciona **empresa** y duración de la sesión entre 1 y 7 días, y genera un enlace. Puede copiarlo o enviarlo por correo. El enlace sin activar vence a los 15 minutos. Al abrirlo, queda activa la sesión durante la vigencia elegida. No se solicita un inicio de sesión del administrador en la tablet: se abre el enlace firmado. Fuentes: [rutas](routes/web.php), [controlador](app/Http/Controllers/ChecadorController.php), [vista de configuración](resources/views/checador/index.blade.php).
2. **Centro de trabajo:** el formulario actual **no selecciona un centro**; la sesión se crea con `centro_id = null` y sirve para el personal de esa empresa. Cada empleado conserva su centro asignado, cuya geolocalización debe estar configurada para validar una marcación. Evitar afirmar que la tablet queda vinculada a un centro específico. Fuentes: [controlador](app/Http/Controllers/ChecadorController.php), [vista de configuración](resources/views/checador/index.blade.php).
3. **Identificación:** el empleado introduce un PIN numérico de cinco dígitos. El servidor valida al empleado activo y determina si corresponde registrar entrada o salida. Fuentes: [vista del checador](resources/views/checador/kiosk.blade.php), [controlador](app/Http/Controllers/ChecadorController.php), [servicio de asistencia](app/Services/RegistroAsistenciaService.php).
4. **Marcación web:** al iniciar, el navegador solicita permisos de cámara y ubicación. Tras validar el PIN obtiene la posición, captura una fotografía y envía ambos datos para registrar la entrada o salida; después muestra un mensaje de confirmación o error. Si falla el permiso, la cámara, la ubicación, el PIN o la conexión, no se completa el flujo. La hora que se muestra en pantalla proviene del dispositivo; la marcación la procesa el servidor. Fuentes: [vista del checador](resources/views/checador/kiosk.blade.php), [controlador](app/Http/Controllers/ChecadorController.php).
5. **Pausas:** desde la misma pantalla el empleado entra a pausas, vuelve a identificarse con PIN, elige un tipo disponible o finaliza la pausa activa. Los tipos configurados son comida, sanitario, descanso y permiso. Debe existir una entrada abierta y no puede haber otra pausa en curso. Este flujo no solicita fotografía. Fuentes: [vista del checador](resources/views/checador/kiosk.blade.php), [servicio de pausas](app/Services/AsistenciaPausaService.php), [tipos](config/constantes.php).
6. **Consulta posterior:** la administración de asistencia y los reportes están en el sistema autenticado, no en la pantalla pública de checado. Fuentes: [rutas](routes/web.php), [controlador de reportes](app/Http/Controllers/ReporteAsistenciaController.php), [exportación](app/Exports/AsistenciaReporteExport.php).

**Pantalla activa:** el checador web actualiza reloj y fecha cada segundo, pero no encontré una función para impedir que la tablet se bloquee o apague la pantalla. La empresa deberá ajustar el bloqueo automático y la alimentación del dispositivo según su uso; esto requiere validación operativa. La app Titanium tampoco contiene una instrucción explícita de mantener la pantalla despierta. Fuentes: [vista web](resources/views/checador/kiosk.blade.php) y [controlador Titanium](/Users/salinasj/Documents/Desarrollo/Titanium/Horalia/app/controllers/index.js).

### Aplicación Titanium: flujo distinto

En el proyecto Titanium, un **administrador con control total de una empresa activa** inicia sesión con correo y contraseña. El token queda guardado mediante `ti.identity`. Desde la pantalla de bienvenida abre el checador; el empleado usa un PIN de cinco dígitos. La app consulta la API, intenta obtener ubicación y fotografía, envía la marcación y muestra confirmación o error. También implementa inicio y fin de pausas. La empresa se fija en el token del administrador y el centro se toma del empleado; no se ve un selector de centro en la app. Fuentes del repositorio Titanium: `app/controllers/index.js`, `app/lib/api.js`, `app/lib/secureSession.js`, `app/config.json`; fuentes del servidor: [autenticación de app](app/Http/Controllers/Api/AppAuthenticationController.php), [API de asistencia](app/Http/Controllers/Api/AppAttendanceController.php), [rutas API](routes/api.php).

La API móvil admite fotografía y coordenadas opcionales en la petición de marcación; la app omite la fotografía si no hay cámara o permiso. Esto **no equivale** al comportamiento del checador web, que requiere foto y ubicación para registrar asistencia. El centro del empleado sí necesita una geolocalización configurada para que la validación del PIN móvil avance. Fuentes: `app/controllers/index.js` del repositorio Titanium; [API de asistencia](app/Http/Controllers/Api/AppAttendanceController.php).

## 4. Estado Android e iOS

Repositorio Titanium consultado: `/Users/salinasj/Documents/Desarrollo/Titanium/Horalia`.

| Plataforma | Implementado en el código | Verificación disponible | Pendiente |
| --- | --- | --- | --- |
| Android | Inicio de sesión administrativo, token seguro, PIN, marcaciones, cámara, ubicación, confirmaciones y pausas. `tiapp.xml` declara destino Android y permisos de cámara/ubicación. | Existe `dist/Horalia.aab`, evidencia de un paquete generado; **no prueba** que esta versión esté publicada ni que el flujo completo funcione en una tablet. | Prueba real de inicio de sesión, entrada, salida, foto, permisos, pausas y cierre de sesión; comprobar ficha de Google Play y versión publicada. |
| iOS / iPadOS | El mismo controlador Titanium contiene el flujo de checador y `tiapp.xml` declara destinos iPad/iPhone, orientaciones de iPad y textos de permiso de cámara/ubicación. El almacenamiento seguro tiene configuración específica para iOS. | Revisión de código solamente. No se encontró un paquete iOS ni evidencia de prueba en iPad en este análisis. | Compilar y probar en iPad compatible; comprobar permisos, cámara, ubicación, disposición y ficha de App Store. |
| Ambos | Pausas de comida, sanitario, descanso y permiso mediante API; avisos ante errores de servidor o conexión. | Código del cliente y rutas/API presentes. | Confirmar comportamiento completo en dispositivos y versiones de sistema operativo concretas. |

**Disponibilidad pública:** no confirmada en Google Play ni App Store. Una búsqueda pública por el identificador de la app no dio una ficha verificable; antes de publicar enlaces de descarga se necesitan las URL oficiales y la versión vigente. No anunciar “descarga la app” en esta página por ahora. **Función no implementada identificada:** mantener despierta automáticamente la pantalla; tampoco se observa cola de marcaciones sin conexión. Fuentes Titanium: `tiapp.xml`, `app/controllers/index.js`, `app/lib/api.js`, `app/lib/secureSession.js`, `dist/Horalia.aab`.

## 5. Requisitos técnicos comprobados y límites

| Modalidad | Requisitos respaldados por código | Límite que debe comunicarse |
| --- | --- | --- |
| Checador web | Tablet con navegador que admita `getUserMedia`, `navigator.geolocation`, cámara funcional, permiso de cámara y ubicación, conexión HTTPS y acceso a Horalia. Empresa activa, enlace de activación vigente y empleado activo con PIN y centro configurado con geolocalización. | Compatibilidad exacta por modelo, navegador y versión **no probada**. Se necesita conexión para validar PIN y registrar cada marca. No hay modo sin conexión ni control del bloqueo de pantalla. |
| App Titanium | Código dirigido a Android y iPad/iPhone; requiere inicio de sesión de administrador autorizado, conexión con `https://horalia.mx/api/app` y permisos de cámara/ubicación según el caso. | Publicación en tiendas y compatibilidad por versión del sistema **sin confirmar**. La foto puede faltar si no hay cámara o permiso; el flujo de cámara fallida puede impedir marcar. |

Fuentes: [vista web](resources/views/checador/kiosk.blade.php), [controlador web](app/Http/Controllers/ChecadorController.php), [API móvil](app/Http/Controllers/Api/AppAttendanceController.php) y, en Titanium, `tiapp.xml`, `app/config.json`, `app/controllers/index.js`, `app/lib/api.js`.

## 6. Propuesta SEO

- **URL definitiva:** `https://horalia.mx/reloj-checador-tablet`.
- **Title:** `Reloj checador para tablet con PIN | Horalia`.
- **Metadescription:** `Usa una tablet como punto de checado para tu empresa. Conoce cómo activar Horalia, registrar entradas y salidas con PIN y consultar la asistencia.`
- **H1:** `Convierte una tablet en tu reloj checador`.
- **Canonical:** autorreferencial a `https://horalia.mx/reloj-checador-tablet`, siempre que la página tenga contenido propio y sea indexable.
- **Open Graph:** `og:type=website`, `og:site_name=Horalia`, `og:locale=es_MX`, `og:url` igual a la canonical, título y descripción centrados en el uso desde tablet. Usar una imagen existente solo después de verificar que represente realmente el checador y no exponga datos personales.
- **Datos estructurados:** `WebPage` con nombre, URL, descripción, `inLanguage=es-MX` e `isPartOf` Horalia; `BreadcrumbList` solo si la navegación visible muestra esas migas. Mantener el precio en el texto visible, sin añadir `Offer` para la tablet si no se confirma su inclusión comercial. No se necesita `FAQPage` para aspirar a resultados enriquecidos: Google los limita principalmente a sitios gubernamentales y de salud reconocidos ([guía de Google](https://developers.google.com/search/blog/2023/08/howto-faq-changes)).
- **Sitemap futuro:** añadir únicamente la URL canónica al [sitemap actual](public/sitemap.xml) al implementar la página. El sitemap ayuda al descubrimiento, pero no garantiza indexación ([Google Search Central](https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview)).

Las expresiones “reloj checador para tablet”, “tablet como reloj checador”, “reloj checador con PIN”, “reloj checador con fotografía” y “checador de empleados desde tablet” son **hipótesis de intención**, sin volumen ni posiciones verificados. Deben aparecer solo donde respondan una pregunta real del lector.

## 7. Estructura de encabezados propuesta

| Nivel | Encabezado |
| --- | --- |
| H1 | Convierte una tablet en tu reloj checador |
| H2 | Una tablet como punto de checado de tu empresa |
| H2 | ¿Cómo funciona el checador en tablet? |
| H3 | 1. Activa el checador / 2. Ingresa el PIN / 3. Registra la asistencia / 4. Consulta los registros |
| H2 | PIN y fotografía en el checador web |
| H2 | Entradas, salidas y pausas desde el mismo dispositivo |
| H2 | Empresa y centros de trabajo: así se relacionan |
| H2 | ¿Qué necesita la tablet para funcionar? |
| H2 | Precio de Horalia |
| H2 | Preguntas frecuentes sobre el checador en tablet |
| H2 | Prepara tu punto de registro con Horalia |

## 8. Textos comerciales completos propuestos

> **Texto listo para revisión editorial.** Las referencias internas a “tablet” describen el checador web. No se promete disponibilidad de la app móvil ni un cargo específico por el dispositivo. Antes de publicar, resolver los pendientes comerciales y de compatibilidad del apartado 12.

### Sección 1 — Encabezado principal

**H1:** Convierte una tablet en tu reloj checador

**Subtítulo:** Coloca una tablet como punto de registro para tu equipo. Cada empleado ingresa su PIN para marcar entrada o salida; después puedes consultar la asistencia en Horalia.

**CTA:** Crear mi cuenta

**Mensaje:** ¡Tu primer mes es gratis!

### Sección 2 — Una alternativa al reloj checador tradicional

**H2:** Una tablet como punto de checado de tu empresa

Con Horalia puedes abrir el checador en el navegador de una tablet compatible y dejar un punto de registro disponible para tu personal. Cada persona se identifica con su PIN y el sistema registra su asistencia en la empresa correspondiente. Solo necesitas preparar el dispositivo, habilitar los permisos necesarios y mantenerlo conectado durante las marcaciones.

### Sección 3 — ¿Cómo funciona?

**H2:** ¿Cómo funciona el checador en tablet?

**H3: 1. Activa el checador.** Desde tu cuenta de Horalia, el administrador selecciona la empresa y genera un enlace temporal. Ábrelo en la tablet dentro del plazo de activación y deja la sesión lista para registrar asistencia.

**H3: 2. Ingresa el PIN.** El empleado escribe su PIN de cinco dígitos. Horalia identifica a la persona y verifica si corresponde una entrada o una salida.

**H3: 3. Registra la asistencia.** El checador web obtiene la ubicación y toma una fotografía con la cámara de la tablet. Cuando el servidor confirma la marcación, la pantalla muestra el resultado y la hora registrada.

**H3: 4. Consulta los registros.** Desde el área de administración puedes revisar la asistencia de tu personal y obtener reportes para dar seguimiento a la jornada.

### Sección 4 — Registro con PIN y fotografía

**H2:** PIN y fotografía en el checador web

El PIN permite identificar a cada empleado sin que tenga que iniciar una sesión personal en la tablet compartida. En el checador web, la fotografía se captura durante la marcación de entrada o salida, junto con la ubicación solicitada al dispositivo. Si la tablet no concede esos permisos, el registro web no puede completarse. La aplicación móvil tiene un flujo distinto y su funcionamiento en dispositivos aún requiere pruebas.

### Sección 5 — Entradas, salidas y pausas

**H2:** Entradas, salidas y pausas desde el mismo dispositivo

El mismo punto de checado permite registrar el inicio y el fin de la jornada. También puedes abrir el apartado de pausas: después de ingresar su PIN, el empleado selecciona comida, sanitario, descanso o permiso, o finaliza una pausa que sigue abierta. Para iniciar una pausa debe existir una entrada vigente; no se pueden mantener dos pausas abiertas al mismo tiempo.

### Sección 6 — Centros de trabajo

**H2:** Empresa y centros de trabajo: así se relacionan

El enlace del checador web se genera para una empresa. Cada empleado está asociado a su propio centro de trabajo, que Horalia utiliza al validar su marcación. Antes de comenzar, asegúrate de tener al personal activo, con PIN asignado y con la ubicación de su centro de trabajo configurada. La tablet no ofrece actualmente una selección de centro durante la activación del checador.

### Sección 7 — Requisitos

**H2:** ¿Qué necesita la tablet para funcionar?

Para usar el checador web necesitas una tablet con navegador compatible con cámara y ubicación, acceso por HTTPS, conexión a internet y permisos para ambos recursos. El administrador debe generar un enlace de activación vigente. Recomendamos comprobar el funcionamiento con el modelo y navegador que usarás en tu empresa y configurar el tiempo de bloqueo de pantalla conforme a tu operación. La compatibilidad no está certificada para todos los dispositivos y el registro sin conexión no está disponible.

### Sección 8 — Precio

**H2:** Precio de Horalia

Horalia cuesta **$150 MXN mensuales por empresa, IVA incluido**, para hasta **200 empleados**. Cada bloque adicional de hasta 200 empleados cuesta **$150 MXN mensuales, IVA incluido**. **Tu primer mes es gratis.**

**CTA:** Crear mi cuenta

**Nota editorial, no publicar hasta confirmar:** el código revisado no muestra un cargo separado por sesión de checador o por tablet, y la portada habla de “reloj checador” dentro de la mensualidad. Falta confirmación comercial expresa de que el **uso del checador desde tablet** está incluido y de si existe algún límite o costo adicional por dispositivo. Hasta resolverlo, no agregar “sin costo extra por tablet” ni “tablets ilimitadas”. Fuentes: [configuración de precio](config/constantes.php), [cálculo por empleados](app/Services/EmployeeSubscriptionPricing.php), [portada](resources/views/welcome.blade.php), [creación de sesiones](app/Http/Controllers/ChecadorController.php).

### Sección 9 — Preguntas frecuentes

**H2:** Preguntas frecuentes sobre el checador en tablet

**¿Cada empleado necesita iniciar sesión en la tablet?** No. En el checador web, el administrador activa la sesión de la empresa y cada empleado se identifica con su PIN de cinco dígitos al marcar.

**¿La tablet debe tener cámara y ubicación?** Sí para el checador web: el navegador necesita acceso a ambos recursos para completar una entrada o salida. También debe poder conectarse a Horalia durante cada registro.

**¿Puedo registrar pausas desde la misma tablet?** Sí. El empleado ingresa su PIN en el apartado de pausas para iniciar una de las opciones disponibles o finalizar la pausa activa. Necesita una entrada vigente y solo puede tener una pausa abierta.

**¿La tablet se asigna a un centro de trabajo?** El enlace web actual se crea para la empresa, sin seleccionar un centro en la tablet. Horalia toma el centro asignado a cada empleado para validar su registro.

**¿Cuánto cuesta comenzar?** El primer mes es gratis. Después, la suscripción comienza en $150 MXN mensuales por empresa, IVA incluido, para hasta 200 empleados; cada bloque adicional de hasta 200 empleados cuesta $150 MXN mensuales, IVA incluido. Antes de publicar esta respuesta junto a una promesa específica sobre tablets, confirmar las condiciones de uso del checador en tablet.

### Sección 10 — Cierre comercial

**H2:** Prepara tu punto de registro con Horalia

Crea tu cuenta, registra a tu personal y configura sus centros de trabajo. Cuando todo esté listo, genera el enlace del checador y pruébalo en la tablet que usarás en tu empresa.

**CTA:** Crear mi cuenta

**Mensaje:** ¡Tu primer mes es gratis!

## 9. Enlaces internos

- **Desde la nueva página:** CTA principal, precio y cierre a [registro](routes/web.php) (`/registro`); enlace contextual “Conoce Horalia” a `/`; enlace “Organiza y consulta la asistencia” a `/control-de-asistencia-empleados`.
- **Desde la portada:** un enlace contextual desde el bloque del checador hacia `/reloj-checador-tablet`, con texto que describa el uso en una tablet. Evitar sustituir el H1 o la propuesta general de la portada.
- **Desde control de asistencia:** un enlace contextual desde seguimiento/registro hacia la nueva página, sin duplicar sus secciones.
- **Navegación/pie:** evaluar un enlace discreto donde encaje la arquitectura actual. Mantener URLs absolutas coherentes en canonical, OG y sitemap.

## 10. Recomendaciones visuales

Reutilizar tipografía DM Sans, paleta, cabecera, botones y pie de la portada y de la página comercial existente; organizar el contenido con una secuencia propia centrada en la tablet y el flujo de cuatro pasos. Usar una captura **real** del checador web, idealmente con pantalla de PIN y otra confirmación, solo tras comprobar que no haya nombres, fotos, PIN ni otros datos personales reales. [Imagen existente](public/assets/images/asistencia.jpg): revisar si representa con precisión el flujo antes de usarla; no asumir que basta por su nombre. No crear imágenes nuevas ni mostrar una ficha de tienda no verificada. Diseñar para lectura cómoda en móvil, tablet y escritorio, sin sugerir que la vista comercial sea la interfaz del checador.

## 11. Riesgo de canibalización SEO

El riesgo **principal es con la portada**, que ya menciona “reloj checador”, tablet, PIN y fotografía. Hay un riesgo secundario con la página de control de asistencia por la explicación de entradas, salidas y reportes. La nueva página aporta valor si responde principalmente **cómo se activa y usa un dispositivo compartido, qué requiere y qué ocurre durante una marcación**. Si el borrador final se vuelve una lista genérica de funciones y precios, conviene reescribirlo antes de implementar.

Usar un H1 y un title específicos de tablet; evitar copiar el texto del bloque de checador de la portada y evitar replicar la estructura de organización de empleados. Mantener canonical propia solo cuando el contenido sea sustancialmente diferente. La canonical es una señal para Google, no una garantía de selección o indexación ([Google Search Central](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)). Tras publicar, comparar consultas y páginas en Search Console, sin presuponer volumen o posiciones.

## 12. Pendientes que bloquean afirmaciones de publicación

1. **Condición comercial:** confirmar con el responsable del producto si el uso del checador web desde tablet está incluido en la mensualidad, si existe un límite por dispositivos/sesiones y si hay cargos adicionales. El precio general sí está aprobado; esta condición específica no queda demostrada solo por la ausencia de un cobro en el código.
2. **Compatibilidad:** probar el checador web en modelos reales de tablet Android e iPad, navegadores y versiones concretas; cámara, ubicación, permisos, pantalla bloqueada, expiración de enlace y conectividad.
3. **Aplicaciones:** obtener URL oficiales de Google Play/App Store y verificar versiones publicadas. Probar el flujo Android e iOS/iPadOS; un `.aab` local y la configuración de `tiapp.xml` no prueban disponibilidad en tiendas.
4. **Capturas:** verificar exactitud visual y ausencia de datos personales antes de utilizarlas.

## 13. Archivos necesarios para una implementación posterior

| Acción futura | Archivo previsto |
| --- | --- |
| Crear vista comercial y metadatos | `resources/views/reloj-checador-tablet.blade.php` (nombre sugerido) |
| Dar URL pública | `routes/web.php` |
| Estilos específicos mínimos, si se requieren | `public/assets/css/reloj-checador-tablet.css` (nombre sugerido) |
| Enlaces contextuales, una vez aprobados | `resources/views/welcome.blade.php`, `resources/views/control-asistencia.blade.php` |
| Añadir URL canónica publicada | `public/sitemap.xml` |
| Imágenes | Solo reutilizar archivos reales existentes tras revisión; no hay archivo nuevo definido. |

Esta lista describe una fase posterior; **ninguno de esos archivos se modifica en esta entrega**.

## 14. Plan de implementación posterior

1. Resolver los pendientes comerciales, de compatibilidad y de capturas del apartado 12; aprobar el texto definitivo.
2. Crear la vista pública con las secciones aprobadas y los metadatos, y añadir la ruta.
3. Aplicar el diseño de Horalia y revisar accesibilidad y disposición en anchos móviles, tablet y escritorio.
4. Verificar con datos de prueba el enlace de activación, PIN, entrada, salida, foto, ubicación, pausas, errores y navegación; probar dispositivos reales antes de anunciar compatibilidad concreta.
5. Añadir enlaces internos y, cuando la URL funcione y sea indexable, incorporarla al sitemap. Validar canonical, Open Graph, datos estructurados, `git diff --check` y la respuesta HTTP.
6. Publicar solo tras una aprobación separada; medir después en Search Console la intención atendida por cada una de las tres páginas.

**Alcance de esta fase:** documento de análisis y propuesta únicamente. No se han creado rutas, vistas, estilos, imágenes ni cambios en las aplicaciones o el sitemap.
