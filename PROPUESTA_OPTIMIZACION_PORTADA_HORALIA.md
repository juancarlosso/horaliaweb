# Propuesta de optimización comercial y SEO de la portada — Horalia

**Fecha:** 25 de octubre de 2026. **Alcance:** propuesta de textos y prioridades, sin implementación. Fuente principal: `resources/views/welcome.blade.php` y [auditoría inicial](AUDITORIA_SEO_HORALIA.md). Se revisaron los flujos de alta, renovación y precio en el código. No se verificó la versión desplegada: `horalia.mx` no fue accesible desde este entorno.

## 1. Resumen ejecutivo

La portada ya permite entender que Horalia es un reloj checador en línea, muestra el precio base y ofrece varios accesos al registro. Tiene una estructura SEO sólida para una página de inicio: title, descripción, canonical, H1, subtítulos, texto HTML y datos estructurados. Conviene conservar su identidad gráfica y mejorar el mensaje dentro de las secciones existentes.

La mejora comercial más valiosa es unir, cerca del primer botón, **producto + público + beneficio + “30 días gratis de prueba”**. Después, la sección de precios debe explicar con mayor claridad qué incluye la mensualidad y cuándo empieza el pago. Las funciones de centros de trabajo y pausas merecen más visibilidad. Las aplicaciones móviles se prepararán para una comunicación futura, sin anunciarlas aún como disponibles.

**Condición para publicar la frase de prueba:** el alta de una empresa fija la primera renovación **un mes calendario después de la fecha de inicio**, no exactamente 30 días (`app/Models/Empresa.php:12-22`). El registro no pide tarjeta (`resources/views/auth/company-register.blade.php:65-118`), y el cobro de renovación se considera cuando llega la fecha (`app/Services/MembershipRenewalService.php:26-42,80-90`). La frase solicitada, **“30 días gratis de prueba”**, queda propuesta para la portada; antes de publicarla hay que hacer que la duración y condiciones reales coincidan con ella. El documento no propone cambiar la condición comercial.

## 2. Evaluación comercial actual

| Pregunta del visitante | Situación actual | Evaluación |
|---|---|---|
| ¿Qué es? | “Reloj checador. En línea.” y subtítulo sobre entradas y salidas (`resources/views/welcome.blade.php:72-76`). | Claro, aunque no nombra a empresas ni personal en el H1. |
| ¿Qué problema resuelve? | Muestra registro, horarios, incidencias y reportes (`:93-119`). | Se entienden funciones; la reducción de tareas manuales y la consulta por centros podrían expresarse mejor. |
| ¿Para quién es? | “Ideal para todo tipo de negocios” y lista de sectores (`:144`). | Amplio; la primera pantalla no identifica de inmediato a responsables de empresas o PyMEs. |
| ¿Cuánto cuesta? | $150 MXN/mes por empresa, hasta 200 empleados; $150 por bloque adicional de hasta 200 (`:142`). | El precio base está visible. El desglose de IVA y el momento de cobro no aparecen en la portada. |
| ¿Cómo empezar? | “Comenzar ahora” aparece en navegación, hero, precio y cierre (`:63,82,142,148`); registro en una pantalla (`resources/views/auth/company-register.blade.php:55-118`). | Recorrido corto. El CTA no anticipa la evaluación gratuita. |
| ¿Por qué confiar? | Capturas del producto, video, FAQ, políticas, contacto y atribución a Solventia Software (`resources/views/welcome.blade.php:82-89,146,169-185`). | Base útil; conviene explicar mejor quién presta el servicio, sin inventar testimonios o cifras. |

La comparación con métodos tradicionales puede formularse con hechos: un registro centralizado y reportes consultables frente a hojas dispersas o consolidación manual. Evitar prometer eliminación total de errores, cumplimiento legal automático o funcionamiento sin internet.

## 3. Evaluación SEO actual

El title actual, **“Horalia | Reloj checador y control de asistencia en línea”**, cubre dos búsquedas principales y conviene conservarlo inicialmente (`resources/views/welcome.blade.php:9`). La metadescripción ya menciona dispositivos, PIN, fotografía, reportes y Excel (`:6`); es completa, aunque podría concentrarse en producto, público y acción. El H1 **“Reloj checador. En línea.”** (`:75`) es legible y distintivo, pero tiene menos contexto semántico que “Reloj checador en línea para empresas”.

Los H2 describen funcionamiento, beneficios, reportes, marco legal, precios y preguntas (`:93-146`). El cuerpo cubre “reloj checador en línea”, “control de asistencia”, entradas/salidas y reportes de manera natural. “Software de asistencia para empresas” y “control de asistencia de personal” pueden aparecer en frases útiles, sin repetirlos como lista. El bloque legal no debe dominar la intención comercial; cualquier afirmación regulatoria requiere revisión editorial y jurídica previa.

Los enlaces internos de la portada apuntan principalmente a anclas, registro, login y documentos legales (`:59-63,169-175`). La auditoría inicial identifica que faltan páginas comerciales dedicadas; cuando existan, enlazarlas desde funciones y precios. No crear enlaces sin destino. El JSON-LD existente es `SoftwareApplication` y toma el precio de `config/constantes.php:5` (`resources/views/welcome.blade.php:29-48`); debe conservarse y contrastarse con el texto visible. No hay datos suficientes para atribuir volumen o posición a las búsquedas no marcarias.

## 4. Propuestas de mejora del encabezado principal

**Propuesta principal, de bajo impacto visual:** conservar la estructura del hero e intercambiar texto. H1: **“Reloj checador en línea para tu empresa”**. Subtítulo: **“Registra entradas, salidas y pausas de tu personal desde una tablet o computadora. Consulta la asistencia y tus reportes en un solo lugar.”** Línea de apoyo junto al CTA: **“30 días gratis de prueba”**, sujeta a la condición del resumen. Botón: **“Crear mi cuenta”**. De esta forma, el H1 indica producto y público; el subtítulo añade el beneficio operativo y el CTA describe la acción real.

**Alternativa más conservadora:** mantener el H1 actual y cambiar sólo el subtítulo a **“Controla las entradas, salidas y pausas de tu personal, consulta reportes y administra tus centros de trabajo desde Horalia.”** Esta opción preserva más la marca visual del hero; se puede comparar con la propuesta principal cuando exista una base de registros suficiente.

El badge laboral actual (“Prepárate para el registro electrónico de la jornada laboral”, `resources/views/welcome.blade.php:74`) atrae la mirada antes del producto. Recomiendo evaluar un badge centrado en beneficio, **“Control de asistencia para empresas”**, dejando el bloque legal en su sección. El cambio depende de la revisión del mensaje legal; no implica borrar la información existente.

## 5. Propuestas de mejora de funcionalidades y beneficios

La sección “¿Cómo funciona?” (`resources/views/welcome.blade.php:93-99`) tiene tres pasos correctos. El paso 2 podría decir **“Tu personal registra entradas, salidas y pausas con el reloj checador”** para reflejar una función real que hoy pasa inadvertida. En la sección del dispositivo (`:102-104`), sustituir “Funciona en cualquier dispositivo” por **“Úsalo desde dispositivos compatibles con navegador, cámara y conexión”** después de confirmar requisitos; “cualquier dispositivo” es demasiado absoluto. Mantener la fotografía y la ubicación **“cuando corresponda”**, tal como el producto lo expresa hoy.

En el grupo de tarjetas (`:107-115`), añadir o adaptar una tarjeta para **centros de trabajo** y otra para **pausas**, sin aumentar indiscriminadamente la longitud de la página. Texto sugerido: **“Centros de trabajo: organiza el registro de asistencia por sucursal o sede”** y **“Pausas: registra los periodos de comida, descanso u otras pausas configuradas”**. Las rutas y módulos existen (`routes/web.php:83-88,115-124`; `config/constantes.php:9-14`). Verificar en QA el alcance exacto de cada modalidad antes de prometerla en detalle.

La sección de reportes ya informa consulta por periodo y exportación a Excel (`resources/views/welcome.blade.php:118-119`). Puede enfatizar el resultado para el responsable: **“Consulta en un mismo lugar entradas, salidas, retardos y evidencias, y exporta reportes a Excel cuando los necesites.”** Es un ajuste de redacción, no de diseño.

## 6. Propuesta de presentación de precios

Mantener el precio visible y el cálculo por bloques. Propuesta de texto para la misma sección (`resources/views/welcome.blade.php:142`):

> **$150 MXN al mes por empresa**
>
> Incluye hasta 200 empleados. Cada bloque adicional de hasta 200 empleados suma $150 MXN al mes.
>
> **30 días gratis de prueba.**

El código calcula bloques de 200 con un mínimo de uno (`app/Services/EmployeeSubscriptionPricing.php:10-21`), de modo que la explicación de capacidad coincide con el producto. Para impuestos, `app/Services/PaymentInvoiceAmounts.php:15-45` desglosa el IVA **dentro** del total cobrado; la vista de suscripción presenta subtotal + IVA como desglose del estimado (`resources/views/subscription/index.blade.php:53-61`). Los términos sólo dicen que se determinarán los impuestos aplicables (`resources/legal/terminos-y-condiciones.md:113-121`). Antes de publicar una frase fiscal definitiva, el área comercial/contable debe alinear portada, términos, cobro y factura. Si el tratamiento actual es el definitivo, el texto candidato es **“Precio final en MXN; IVA incluido cuando corresponda”**. No añadir “más IVA” al precio mostrado sin cambiar y verificar el cobro real.

Sobre el inicio del pago, la redacción segura después de alinear la prueba es: **“Crea tu cuenta sin registrar una tarjeta. Consulta tu fecha de renovación dentro de Horalia; el primer cobro corresponde al término del periodo de prueba si continúas con el servicio.”** La primera oración está sustentada por el formulario de alta; la segunda debe validarse contra las políticas de cancelación, tarjetas guardadas y programación real de renovaciones antes de publicarse. Evitar “sin costos ocultos” como sustituto de esta explicación.

## 7. Propuesta de comunicación del periodo de evaluación

Mensaje público solicitado: **“30 días gratis de prueba”**. Ubicación recomendada: línea breve junto al CTA del hero, una línea en la sección de precios y una respuesta concreta en FAQ. No saturar cada tarjeta con el mismo reclamo.

**Desajuste que debe resolverse antes de publicar:** `Empresa::creating` usa `addMonthNoOverflow()` para `fecha_renovacion` (`app/Models/Empresa.php:14-22`). Un mes calendario puede durar 28, 29, 30 o 31 días; por eso la frase exacta de 30 días no describe todos los casos. El alta crea una empresa activa sin registrar tarjeta (`app/Services/RegisterCompanyService.php:22-54`; `resources/views/auth/company-register.blade.php:65-118`), y el sistema inicia el procesamiento de renovación al llegar `fecha_renovacion` (`app/Services/MembershipRenewalService.php:26-42`). La programación efectiva del proceso en producción no fue verificada. Esta propuesta **no cambia fechas, precio, rutas ni cobros**.

Cuando se haya alineado la duración real, FAQ sugerida: **“¿Qué sucede después de los 30 días gratis de prueba?”** Respuesta candidata: **“Puedes usar Horalia durante el periodo de prueba. Antes de que termine, revisa en tu cuenta la fecha de renovación y el precio de tu empresa. Si decides continuar, se aplicará la mensualidad correspondiente según el número de empleados.”** Ajustar el detalle de cancelación y cobro a los términos aprobados, sin prometer una experiencia que aún no aparece en el registro.

## 8. Recomendaciones para mejorar conversiones

1. Usar un CTA principal consistente, **“Crear mi cuenta”**, en hero, precio y cierre. El botón debe seguir llevando a `/registro`; no añadir pasos.
2. Poner **“30 días gratis de prueba”** junto al primer CTA, una vez resuelto el desajuste de duración. El usuario debe entender el beneficio sin buscar la sección de precios.
3. Responder en la FAQ tres dudas previas al alta: qué incluye el precio, cuándo empieza el cobro y si se necesita un equipo especial. Mantener respuestas breves y comprobables.
4. Mostrar en el pie, junto a contacto y políticas existentes, una frase factual de responsabilidad: **“Horalia es un servicio de Solventia Software”**. No inventar certificaciones, testimonios ni números de clientes.
5. Medir el recorrido de `visita → clic en registro → formulario enviado → cuenta creada → primera renovación pagada`, separando canales orgánicos de otros canales. Revisar la configuración de analítica y consentimiento antes de agregar eventos.
6. Probar textos con datos de conversión suficientes; el volumen inicial de Search Console citado en la auditoría no permite declarar un ganador.

## 9. Preparación comercial para las aplicaciones móviles

El proyecto Titanium contiene pantallas de acceso de administradores, reloj checador y pausas (`app/views/index.xml:19-48,102-150`; `app/controllers/index.js`). Esto prueba desarrollo local, **no publicación ni aprobación en tiendas**. Tampoco prueba paridad de funciones entre Android e iOS. La portada debe seguir describiendo el uso actual mediante navegador y tablet sin presentar las apps como descargables.

**Sección futura, condicionada a publicación verificada:** título **“Horalia en dispositivos compatibles”**; subtítulo a redactar según las capacidades comprobadas de **cada** aplicación; capturas reales y enlaces oficiales a Google Play y App Store sólo después de validar ficha pública, URL, plataforma, disponibilidad geográfica y funcionamiento. Si una plataforma se publica primero, mostrar únicamente esa plataforma. La ventaja de tablet puede explicarse desde el uso ya existente del checador; no vincularla automáticamente a la app nativa. No añadir badges, enlaces ficticios ni una sección “Próximamente” que desvíe del registro actual.

## 10. Tabla comparativa de textos actuales y propuestos

Cada fila contiene sección, oportunidad, texto actual y propuesto, justificación, efecto SEO esperado, prioridad e impacto visual. Las propuestas de prueba y fiscales tienen las condiciones indicadas en las secciones 6 y 7.

| Sección actual | Problema u oportunidad | Texto actual | Texto propuesto | Justificación comercial | Beneficio SEO esperado | Prioridad | Impacto visual |
|---|---|---|---|---|---|---|---|
| Hero, H1 (`welcome.blade.php:75`) | Falta público explícito | “Reloj checador. En línea.” | “Reloj checador en línea para tu empresa” | Identifica producto y comprador en una lectura. | Mayor correspondencia semántica con búsquedas de producto; resultado no garantizado. | Alta | Bajo: cambia longitud del título; revisar salto en móvil. |
| Hero, subtítulo (`:76`) | No menciona pausas ni reportes | “Controla entradas, salidas y asistencia de tu equipo de forma sencilla, desde cualquier lugar.” | “Registra entradas, salidas y pausas de tu personal desde una tablet o computadora. Consulta la asistencia y tus reportes en un solo lugar.” | Hace tangible el flujo y el resultado. | Amplía vocabulario natural relacionado con control de asistencia. | Alta | Bajo: puede ocupar una línea adicional. |
| Hero, CTA (`:82`) | Acción y evaluación poco claras | “Comenzar ahora” | “Crear mi cuenta” + línea “30 días gratis de prueba” | Explica la acción y reduce incertidumbre; publicar la línea tras alinear duración. | Efecto orgánico indirecto por mayor claridad de la página; no es factor de ranking probado. | Alta | Bajo: pequeño texto junto al botón. |
| Pasos, paso 2 (`:97`) | Pausas poco visibles | “Tu equipo marca entrada y salida desde su dispositivo.” | “Tu personal registra entradas, salidas y pausas con el reloj checador.” | Presenta una función ya disponible. | Relevancia adicional para consultas de registro de jornada. | Media | Nulo: reemplazo de frase. |
| Checador, lista (`:104`) | Afirmación demasiado absoluta | “Funciona en cualquier dispositivo” | “Úsalo desde dispositivos compatibles con navegador, cámara y conexión” | Reduce expectativas equivocadas. | Describe requisitos específicos; no se espera efecto SEO directo. | Media | Nulo: reemplazo de línea. |
| Funcionalidades (`:107-115`) | Centros y pausas carecen de tarjeta | No hay tarjeta específica. | “Centros de trabajo: organiza el registro por sede” y “Pausas: registra los descansos configurados” | Visibiliza usos reales para empresas con varias sedes. | Refuerza temas de consulta relevantes sin repetir el H1. | Media | Moderado si se añaden dos tarjetas; preferir adaptar tarjetas existentes. |
| Reportes (`:119`) | Beneficio puede ser más concreto | “Consulta la asistencia de tu equipo por periodo…” | “Consulta en un mismo lugar entradas, salidas, retardos y evidencias, y exporta reportes a Excel cuando los necesites.” | Muestra el resultado para RH. | Conecta la página con consultas de reportes de asistencia. | Media | Nulo o bajo. |
| Precios (`:142`) | Falta condición de evaluación e impuestos | “Todo esto por solo $150 MXN / mes… Sin costos ocultos…” | “$150 MXN al mes por empresa. Hasta 200 empleados. Cada bloque adicional de hasta 200 suma $150 MXN al mes. 30 días gratis de prueba.” | Explica unidad de cobro y beneficio inicial. Aclaración de IVA separada tras validación. | Más contenido útil para intención de precio; no garantiza posición. | Alta | Bajo: reorganizar texto en la misma tarjeta. |
| FAQ (`:146`) | Falta respuesta sobre inicio del pago | No existe pregunta de evaluación. | “¿Qué sucede después de los 30 días gratis de prueba?” con respuesta validada comercialmente. | Resuelve objeción previa al registro. | Responde una pregunta real en HTML; sin prometer rich result. | Alta | Bajo: un `details` adicional. |
| Pie (`:185`) | Responsable poco explícito | “© [año] Solventia Software. Todos los derechos reservados.” | “Horalia es un servicio de Solventia Software.” junto al pie actual | Aclara quién presta el servicio. | Refuerza identidad de marca; efecto SEO directo incierto. | Baja | Bajo. |
| Title y descripción (`:6,9`) | Title ya cubre temas; descripción puede centrarse en compra | Title actual y descripción de funciones | **Mantener title.** Descripción candidata: “Horalia es un reloj checador en línea para empresas. Registra entradas, salidas y pausas; consulta reportes. Conoce el precio y crea tu cuenta.” | Da contexto y siguiente paso en el fragmento potencial. | Puede mejorar pertinencia del snippet si Google lo utiliza; no garantiza CTR. | Media | Nulo. |
| Apps móviles (sin sección actual) | Lanzamiento futuro | Sin anuncio en portada | Sección futura sólo con funciones comprobadas y enlaces oficiales verificados. | Abre otro punto de entrada sin confundir el producto actual. | Posibles búsquedas de marca + plataforma tras publicación. | Diferida | Moderado; sujeto a aprobación posterior. |

## 11. Plan de implementación priorizado

| Orden | Decisión y trabajo propuestos | Condición de salida |
|---|---|---|
| 1 | Alinear en producto y términos la frase **“30 días gratis de prueba”** con la duración real, fecha de renovación y comunicación de cobro. Confirmar tratamiento de IVA del precio final. | Mensajes aprobados por operación y área fiscal; ningún visitante recibe una promesa distinta del flujo. |
| 2 | Ajustar hero, CTA y sección de precios dentro de la estructura y estilos actuales. | Lectura clara de producto, precio y registro en móvil y escritorio; enlaces existentes funcionan. |
| 3 | Hacer visibles pausas y centros de trabajo, actualizar FAQ y atribución a Solventia. | Copia verificada contra funciones existentes y sin nuevas promesas. |
| 4 | Evaluar metadescripción y enlaces a futuras páginas comerciales; conservar title y JSON-LD mientras sigan correctos. | HTML y datos estructurados consistentes con precio y contenido visibles. |
| 5 | Tras publicar cada aplicación, comprobar funciones por plataforma y fichas oficiales; entonces proponer sección y enlaces. | Disponibilidad pública verificable, capturas reales y enlaces oficiales correctos. |
| 6 | Medir clics a registro, altas y clientes de pago antes y después; revisar Search Console para búsquedas no marcarias. | Comparación con muestras suficientes, sin atribuir causalidad a datos escasos. |

**Estado:** propuesta para revisión. No se modificaron la portada, estilos, rutas, condiciones comerciales, registro, precios ni aplicaciones.
