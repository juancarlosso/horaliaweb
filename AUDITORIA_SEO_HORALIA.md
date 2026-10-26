# Auditoría SEO inicial — Horalia

**Fecha:** 25 de octubre de 2026 (México). **Alcance:** revisión de código local y datos de Search Console proporcionados por el equipo, con corte al 24 de octubre de 2026. No se implementaron mejoras, migraciones ni cambios de configuración o base de datos.

**Límite de verificación:** no fue posible consultar `https://horalia.mx` desde este entorno (falló la resolución DNS; el navegador de consulta tampoco obtuvo el sitio). Por tanto, el documento describe el código revisado, no certifica qué revisión está desplegada, ni los códigos HTTP, cabeceras, redirecciones, resultados enriquecidos o métricas de rendimiento reales de producción. Las rutas y archivos citados son relativos a la raíz del proyecto.

## 1. Resumen ejecutivo

Horalia ya tiene una portada con propuesta de valor, metadatos principales, etiquetas sociales, canonical, un H1, contenido HTML rastreable y datos estructurados `SoftwareApplication`. Las rutas privadas del panel exigen autenticación; los formularios de acceso y registro y el panel declaran `noindex`. El sitemap contiene cuatro URLs: portada y tres documentos legales.

Las prioridades son revisar el tratamiento del enlace firmado del checador y sus respuestas, decidir si las tres páginas legales deben consumir casi todo el inventario indexable, y crear páginas comerciales útiles para búsquedas distintas de la marca. En conversión, el precio se muestra, pero el visitante no encuentra una explicación clara de impuestos ni un periodo de prueba. No debe anunciarse una prueba hasta confirmar que existe comercial y técnicamente.

## 2. Estado SEO actual

Según los datos entregados de Search Console: dominio verificado por DNS, sitemap procesado, **3 páginas indexadas**, **4 descubiertas mediante sitemap**, **23 impresiones**, **1 clic**, **CTR 4.3 %**, **posición media 4.8** y una consulta visible, **“horalia”** (10 impresiones). Con esta muestra no se pueden inferir tendencias ni rendimiento para términos genéricos. La diferencia 3/4 no identifica por sí sola una incidencia: hay que consultar en Search Console la URL excluida y su motivo.

El archivo `public/sitemap.xml:1-15` enumera `/`, `/terminos-y-condiciones`, `/politica-de-privacidad` y `/politica-de-cookies`. `public/robots.txt:1-5` permite el rastreo general, excluye `/hrnexus/` y declara el sitemap. La confirmación de Search Console aportada por el usuario valida el procesamiento, pero no demuestra que las cuatro páginas se indexaron ni que las URLs respondan hoy con HTTP 200.

## 3. Hallazgos técnicos

**Criterio:** “confirmado” = observado en el código; “posible” = depende de respuesta, despliegue o medición externa; “recomendación” = oportunidad sin defecto probado. El impacto es una estimación cualitativa.

| ID / estado | Archivo y ubicación; evidencia | Impacto estimado / prioridad | Solución recomendada |
|---|---|---|---|
| T1 · Confirmado, favorable | `resources/views/welcome.blade.php:2-27`: `lang="es-MX"`, title único, descripción, `index, follow`, canonical absoluto, Open Graph y Twitter Card con imagen. | Base correcta para relevancia y compartidos / baja | Mantener; validar en producción que la imagen OG responda y que URL canónica y dominio final coincidan. |
| T2 · Confirmado | `resources/views/legal/document.blade.php:2-14`: los tres documentos comparten plantilla, tienen títulos distintos y canonical propio, pero no tienen metadescripción ni etiquetas sociales propias. | Control limitado del fragmento y vista al compartir / baja | Si permanecen indexables, redactar descripciones específicas; priorizar antes las páginas comerciales. No hay duplicidad de títulos confirmada. |
| T3 · Confirmado, favorable | `resources/views/welcome.blade.php:68-148`: un H1 (`Reloj checador. En línea.`), secciones con H2 y tarjetas con H3; contenido y enlaces CTA en HTML, imágenes informativas con `alt`. `resources/legal/*.md:1` aporta H1 a cada documento legal. | Facilita comprensión y rastreo / baja | Conservar jerarquía; al crear páginas nuevas, usar H1 descriptivo de la intención de búsqueda. |
| T4 · Confirmado | `resources/views/welcome.blade.php:59-63,169-175`: navegación interna principalmente a anclas de una sola página; enlaces a registro y a tres páginas legales. `routes/web.php:163-174` sólo define la portada y documentos legales como páginas públicas de contenido. | Cobertura orgánica muy estrecha para búsquedas no marcarias / alta | Planificar páginas comerciales independientes y enlazarlas desde navegación, bloques contextuales y sitemap, evitando páginas repetidas. |
| T5 · Confirmado, favorable | `resources/views/welcome.blade.php:29-48`: JSON-LD `SoftwareApplication` con categoría, idioma, imagen y oferta. El precio viene de `config/constantes.php:5`, el mismo valor mostrado en `resources/views/welcome.blade.php:142`. | Consistencia básica de datos estructurados / baja | Validar el JSON-LD renderizado y su concordancia con precio real y términos; no asumir resultados enriquecidos. `Organization` y `WebSite` sólo aportarían identidad adicional si se modelan con datos verificables. `BreadcrumbList` corresponde a futuras páginas con jerarquía real; `FAQPage` sólo a preguntas visibles y pertinentes, sin promesa de rich results. |
| T6 · Confirmado | `public/robots.txt:1-5` sólo excluye `/hrnexus/`; `public/sitemap.xml:1-15` incluye los tres textos legales y ninguna página comercial adicional. | Inventario indexable concentrado en textos legales / media | Decidir por URL legal si se busca indexación; si no, retirar del sitemap y usar `noindex` accesible al robot. Reservar sitemap para URLs públicas canónicas con valor de búsqueda. |
| T7 · Confirmado, favorable | `routes/web.php:49-134`: `/home`, reportes, pagos y personal dentro de `auth`; `resources/views/layouts/dashboard.blade.php:7` añade `noindex, nofollow`. `resources/views/auth/{login,company-register,registration-completed}.blade.php:6` y `resources/views/auth/passwords/{email,reset}.blade.php:6` también declaran `noindex`. | Reduce exposición de páginas operativas / baja | Mantener la autorización como control real; verificar en producción redirección de anónimo a login y que no se sirvan datos privados por recursos directos. |
| T8 · Confirmado / posible exposición de URL | `routes/web.php:136-137` expone `/checador/activar/{session}` mediante firma; `app/Http/Controllers/ChecadorController.php:159-177` valida vigencia y entrega la vista con token; `resources/views/checador/kiosk.blade.php:1-6,48` no incluye meta robots y coloca el token en el HTML. La explotación o indexación efectiva no está comprobada. | Una URL de activación compartida podría ser rastreada, guardada o difundida mientras esté vigente / alta | Revisar el flujo con seguridad y añadir `noindex`/`X-Robots-Tag`, `Cache-Control: no-store, private` y política de referrer apropiada a respuestas sensibles; comprobar que la firma, expiración y validación del token sigan intactas. No confiar en `robots.txt` para protegerla. |
| T9 · Confirmado / riesgo a verificar | `config/filesystems.php:41-47,90-92` configura un disco `public` y enlace `/storage`; `app/Models/Departamento.php:22-34` genera URL directa para documentos de políticas. `app/Models/Personal.php:90-91` y `app/Helper/Wasabi.php:65-75` usan URL temporal para fotos del personal. | Archivos subidos al disco público podrían ser accesibles por URL sin autenticación; no hay prueba de indexación o contenido sensible específico / alta | Inventariar contenido real de `/storage`, permisos y visibilidad de cada tipo de archivo. Mover documentos sensibles a almacenamiento privado con descarga autorizada; no publicar enlaces a datos de empleados. La URL temporal de Wasabi es un control favorable que debe conservarse. |
| T10 · Confirmado / rendimiento posible | `resources/views/welcome.blade.php:49-54,87-89,103,118,136`: Google Fonts, CSS local y kit externo de Font Awesome se solicitan desde el `head`; JS local usa `defer`; imagen principal usa `fetchpriority="high"`; imágenes inferiores usan `loading="lazy"`. `resources/views/layouts/fontawsome.blade.php:1-7` inserta un script externo sin `defer`. | El script externo, la fuente y el CSS podrían retrasar el renderizado; magnitud no medida / media | Medir primero LCP, INP, CLS y cascada de red en móvil; después evaluar carga de iconos, fuentes y CSS crítico. Conservar la prioridad de la imagen principal. |
| T11 · Confirmado / rendimiento posible | `public/assets/logos/logoLN.png` pesa ~508 KiB; `logoLB.png` ~528 KiB; `public/assets/images/preview.jpg` ~436 KiB; `landing.css` ~37 KiB. `public/assets/css/landing.css:12,30` usa imágenes de fondo. `resources/views/welcome.blade.php:59,171` utiliza el logo en portada y pie. | Transferencia y decodificación de imágenes, sobre todo en móvil; no hay medida de Core Web Vitals / media | Crear variantes optimizadas y tamaños adecuados, verificar visualmente y medir antes/después. Considerar que los fondos CSS se cargan fuera del mecanismo de `loading="lazy"`. |
| T12 · Posible | `public/.htaccess:1-25` gestiona reescritura y barra final, pero aquí no se demuestra compresión, caché de estáticos, HTTP→HTTPS ni preferencia entre `www` y raíz. `public/hrnexus/nginx.conf` y `web.config` son configuración del paquete de plantilla, no evidencia del servidor de producción. | Canonicalización o descarga subóptima si el servidor no configura estos puntos / media | Inspeccionar respuestas HTTP de variantes de dominio, rutas y assets: 200/301/404/410, `Location`, `Cache-Control`, `Content-Encoding`, `Vary` y `Content-Type`. Definir una única URL canónica. |
| T13 · Confirmado, favorable | `public/assets/css/landing.css:8-11` incluye puntos de ruptura a 1000, 760 y 390 px y preferencia por movimiento reducido; `resources/views/welcome.blade.php:5` declara viewport. | Base responsiva en código / baja | Verificar en móviles reales y con auditoría visual, especialmente menú, hero y CTA. No se han medido CLS ni usabilidad móvil. |
| T14 · Confirmado | `resources/views/welcome.blade.php:150-167` mantiene contenido del modal legal fuera del flujo normal; `public/assets/js/landing.js:37-50` crea el `iframe` de YouTube sólo tras clic. | El video no añade carga inicial; el texto legal del modal puede tener menor utilidad como página de búsqueda / baja | Conservar la carga bajo demanda; si existe demanda orgánica para el tema legal, publicar contenido propio y revisado en URL dedicada, con fuente oficial y fecha de revisión. |

No se observaron títulos o metadescripciones duplicados entre las cuatro URLs del sitemap; las tres páginas legales carecen de descripción explícita. No se ejecutó un rastreo completo de producción ni se verificaron 404 reales. La presencia de metadescripción, canonical o schema en el código tampoco garantiza que Google los use.

## 4. Problemas identificados

1. **Confirmado — cobertura comercial limitada (alta):** sólo hay una página comercial indexable en las rutas y en el sitemap (`routes/web.php:163-174`, `public/sitemap.xml:1-15`). La portada reúne varias intenciones, pero no ofrece páginas específicas para funciones, tablet, fotografía o PyMEs.
2. **Confirmado — explicación incompleta de la oferta (media):** `resources/views/welcome.blade.php:142` muestra $150 MXN/mes por empresa hasta 200 empleados y bloques extra; `resources/legal/terminos-y-condiciones.md:113-125` dice que impuestos aplicables se determinan aparte. En portada no se explica si el precio mostrado incluye IVA ni el total final. Conviene aclararlo conforme al cobro real.
3. **Confirmado — periodo de prueba no explicado (media):** no hay oferta de prueba en portada, registro o servicio de alta (`resources/views/welcome.blade.php:72-82,142`, `resources/views/auth/company-register.blade.php:55-117`, `app/Services/RegisterCompanyService.php:18-55`). No debe interpretarse como prueba existente: primero confirmar política comercial y comportamiento de suscripción.
4. **Posible — rastreo de enlace operativo (alta):** T8. Se requiere revisar cabeceras y Search Console para saber si Google descubrió una URL firmada.
5. **Posible — rendimiento y canonización (media):** T10–T12. Sin mediciones ni acceso HTTP no se puede calificar PageSpeed o Core Web Vitals.

## 5. Riesgos de indexación y seguridad

| Tipo de URL | Política recomendada y fundamento |
|---|---|
| `/` | Indexable: página comercial principal, con canonical propio. |
| `/terminos-y-condiciones`, `/politica-de-privacidad`, `/politica-de-cookies` | Públicas y enlazadas. Decidir si se indexan por utilidad para usuarios; no son el motor principal de captación. Si se elige `noindex`, retirarlas del sitemap y mantenerlas accesibles a lectores y rastreadores para que la directiva pueda verse. |
| `/registro`, `/login`, `/password/forgot`, `/password/reset/{token}`, `/registro/completado` | Mantener fuera de resultados. El código ya usa meta `noindex`; la recuperación con token y la confirmación requieren revisar además caché y cabeceras en producción. |
| `/home/**`, `/mi-perfil/**`, reportes, pagos, personal | Privadas: autenticación/autorización son imprescindibles. `noindex` es complementario; no sustituye control de acceso. |
| `/checador/activar/{session}` | Operativa y sensible: firma/caducidad existentes; revisar `noindex`, `X-Robots-Tag`, caché, referrer y posibles URLs compartidas o antiguas. |
| `/checador/hora`, endpoints POST y `/api/app/**` | No son contenido SEO. La API protegida usa `auth:sanctum` para sus rutas de datos (`routes/api.php:7-23`); verificar respuestas y políticas de caché donde proceda. |
| `/storage/**` y archivos en Wasabi | Auditar tipos y acceso efectivo. No confiar en exclusión del robot: un archivo privado debe requerir autorización o URL temporal correctamente limitada. |

`robots.txt` administra rastreo, no confidencialidad. Bloquear una URL sensible sólo allí puede impedir que Google vea su `noindex`; además la URL puede aparecer en resultados sin contenido si se descubre por enlaces. La discrepancia 3 indexadas/4 descubiertas debe investigarse con la inspección de URL y motivos de exclusión de Search Console, no con una modificación automática del robots.

## 6. Evaluación comercial

La portada comunica rápido **qué es** Horalia (reloj checador en línea y control de entradas/salidas) en `resources/views/welcome.blade.php:72-82`. Explica funciones concretas: dispositivo común, fotografía/ubicación cuando corresponda, horarios, incidencias, reportes y exportación (`:93-119`). Hay CTA a registro en menú, hero, precio y cierre (`:63,82,142,148`), y una sección de precio legible: $150 MXN/mes por empresa, hasta 200 empleados, con bloques adicionales al mismo importe (`:142`; origen en `config/constantes.php:5`). La FAQ responde dudas de equipo, multiempresa y ubicación (`:146`). El video se abre bajo demanda (`public/assets/js/landing.js:37-50`), y el pie muestra contacto, políticas y marca Solventia (`resources/views/welcome.blade.php:169-185`): señales de confianza, aunque no sustituyen evidencia verificable del servicio.

Un visitante puede entender qué hace el producto, ver el precio base y llegar al registro. Quedan fricciones: el CTA “Comenzar ahora” no anticipa si habrá cobro inmediato, cómo empieza la suscripción o si existe prueba; el precio no aclara IVA en la portada. La afirmación sobre reforma laboral (`resources/views/welcome.blade.php:121-138`) debe revisarse con fuente legal vigente y redacción precisa antes de usarla como argumento SEO. La nota de límites de cumplimiento (`:138,164-166`) es positiva. Esto es una observación editorial, no una conclusión jurídica.

## 7. Oportunidades de posicionamiento

**Hipótesis de palabras clave, pendientes de validar** con consultas reales de Search Console, investigación de SERP en México y, si se dispone, herramientas de palabras clave. No se atribuyen volúmenes, dificultad ni posiciones.

| Intención | Hipótesis de búsqueda | Página o respuesta potencial |
|---|---|---|
| Comercial | reloj checador en línea; reloj checador para empleados; software de asistencia en México | Página de producto con capacidades, límites, precio y CTA. |
| Comercial | control de asistencia para PyMEs; sistema de control de asistencia de empleados | Página orientada a operación de pequeñas y medianas empresas con ejemplos reales. |
| Comercial | reloj checador con fotografía; reloj checador desde tablet | Páginas de función con requisitos, flujo, privacidad y capturas verificadas. |
| Transaccional | contratar reloj checador en línea; precio software de control de asistencia; registro reloj checador para empresa | Página de precios/contratación con condiciones transparentes y acceso al registro. |
| Informativa | cómo registrar entradas y salidas de empleados; cómo organizar el control de asistencia en una PyME; cómo usar una tablet como reloj checador | Guías prácticas que resuelvan el proceso y enlacen de forma contextual al producto. |
| Informativa | registro electrónico de jornada laboral en México; control de asistencia y privacidad de fotografías | Artículos revisados por especialista, con fecha y fuentes oficiales; evitar promesas de cumplimiento automático. |

Separar “reloj checador en línea” y “control de asistencia” en páginas sólo si la intención y el contenido realmente difieren; si compiten por la misma consulta, una página robusta es preferible a duplicados.

## 8. Propuestas de contenido

| Título sugerido | Palabra clave principal (hipótesis) | Intención | Público | Objetivo comercial | Prioridad |
|---|---|---|---|---|---|
| Reloj checador en línea para empresas | reloj checador en línea | Comercial | Dueños y responsables de RH | Explicar producto, límites y llevar a registro | Alta |
| Control de asistencia para PyMEs en México | control de asistencia para PyMEs | Comercial | PyMEs con uno o varios centros | Mostrar flujo cotidiano, reportes y costo | Alta |
| Precios de Horalia: qué incluye cada empresa | precio software de control de asistencia | Transaccional | Compradores que comparan opciones | Resolver precio, IVA, bloques y comienzo del cobro | Alta |
| Reloj checador desde tablet: requisitos y funcionamiento | reloj checador desde tablet | Comercial | Negocios con punto de checado | Demostrar uso real y requisitos de cámara/conectividad | Media |
| Registro de asistencia con fotografía: uso y privacidad | reloj checador con fotografía | Comercial | RH y responsables de datos | Explicar evidencia, elección de funciones y resguardo | Media |
| Cómo organizar entradas, salidas y retardos en una PyME | registro de entradas y salidas de empleados | Informativa | Encargados de operación | Captar búsqueda temprana y dirigir a la solución | Media |
| Guía de registro electrónico de jornada laboral en México | registro electrónico de jornada laboral | Informativa | RH y dirección | Generar confianza con información verificada | Media, condicionada a revisión legal |

Cada página debe aportar capturas o ejemplos reales, respuestas específicas y enlaces internos pertinentes. Validar que ninguna función o promesa de la propuesta exceda el producto operativo.

## 9. Recomendaciones priorizadas

1. **Alta — seguridad e indexación:** revisar el enlace firmado del checador y archivos de `/storage`; definir cabeceras y autorización según sensibilidad (T8–T9). Verificar en Search Console si aparecen URLs operativas.
2. **Alta — cobertura de búsqueda:** investigar SERP y consultas, escoger dos páginas comerciales iniciales y un esquema de enlaces internos; mantener mensajes y precios consistentes (T4).
3. **Media — oferta y conversión:** aclarar IVA y momento de cobro; confirmar si existe prueba antes de mencionarla; hacer explícito qué ocurre tras “Comenzar ahora”.
4. **Media — medición técnica:** obtener un rastreo de las cuatro URLs y muestras de rutas excluidas, respuestas HTTP y cabeceras; medir Core Web Vitals con datos de campo y laboratorio móvil (T10–T12).
5. **Media — imágenes y recursos:** priorizar logo e imágenes visibles inicialmente para optimización después de medir; revisar carga del kit de iconos y fuentes.
6. **Baja — metadatos y schema:** decidir política de indexación legal; completar descripciones si se indexan; validar JSON-LD existente y sólo añadir tipos pertinentes.

## 10. Plan de implementación por fases

| Fase | Trabajo propuesto | Criterio de salida |
|---|---|---|
| 0. Verificación | Confirmar versión desplegada; exportar páginas indexadas/excluidas y consultas de Search Console; comprobar HTTP de `/`, las tres legales, autenticación, URL firmada caducada y una 404; revisar variantes `www`, HTTP/HTTPS y barras finales; medir CWV/PageSpeed sin inventar valores. | Inventario de URLs, códigos y cabeceras; línea base móvil y lista de motivos de exclusión. |
| 1. Protección y claridad | Revisar checador y archivos accesibles; proponer cambios de robots/cabeceras/caché donde correspondan; aprobar redacción de precio, impuestos, cobro y prueba conforme a reglas comerciales; revisar mensaje laboral. | Ninguna URL o archivo sensible accesible indebidamente; condiciones comerciales coherentes entre portada, registro, términos y cobro. |
| 2. Páginas comerciales | Publicar primero producto, PyMEs y precios, con contenido real, metadatos únicos, canonical, enlaces internos y sitemap actualizado; verificar conversión. | Páginas 200, indexables y rastreables; CTA correcto; Search Console las descubre sin exclusiones inesperadas. |
| 3. Rendimiento y contenido | Optimizar imágenes y recursos según mediciones; crear guías prácticas y páginas de función con evidencia; revisar datos estructurados aplicables. | Mejora medida frente a la línea base; contenidos útiles sin canibalización ni promesas no verificadas. |
| 4. Seguimiento | Revisar mensualmente consultas no marcarias, indexación, páginas de entrada, registros y calidad del tráfico; actualizar contenidos que cambien. | Decisiones sustentadas en datos suficientes y en conversiones, no en las 23 impresiones iniciales. |

**Estado de la auditoría:** diagnóstico únicamente. La implementación y las comprobaciones en producción quedan pendientes de autorización y acceso a los datos correspondientes.
