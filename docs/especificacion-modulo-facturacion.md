# Especificación del módulo de facturación

Desarrolla un módulo que emita un **CFDI 4.0 de ingreso por cada pago exitoso de una suscripción**, lo timbre mediante SIFEI y entregue el XML y el PDF por correo y desde el historial de pagos. El flujo de referencia es el de Horalia.

## 1. Campos necesarios

| Origen | Campo | Regla |
|---|---|---|
| Empresa receptora | `rfc` | Obligatorio para emitir. Normalizar a mayúsculas, quitar espacios al inicio y al final y validar el formato de 12 o 13 caracteres. Bloquear los RFC provisionales que empiezan con `FAL`. |
| Empresa receptora | `razon_social` | Nombre fiscal del receptor tal como aparece en sus datos fiscales. Obligatorio para emitir. Máximo 255 caracteres. |
| Empresa receptora | `domicilio_codigo_postal` | Código postal fiscal de exactamente cinco dígitos. Guardarlo como texto para conservar ceros iniciales. |
| Empresa receptora | `regimen_fiscal` | Clave válida del catálogo `c_RegimenFiscal`. Obligatoria para emitir. |
| Solicitud de factura | `uso_cfdi` | Selección obligatoria del catálogo `c_UsoCFDI`. Por ejemplo, `G03` para Gastos en general. |
| Solicitud de factura | `forma_pago_sat` | Clave correspondiente al medio con el que se cobró el pago. Horalia admite `03` para transferencia electrónica, `04` para tarjeta de crédito y `28` para tarjeta de débito. Precargar la clave registrada en el pago cuando exista. |
| Solicitud de factura | `correo_facturacion` | Correo válido de hasta 255 caracteres. Precargar el de la empresa y permitir cambiarlo para esa factura. Se utiliza para la entrega. |
| Pago registrado | `id`, `empresa_id`, `resultado`, `intentado_en`, `factura` | Identifican el cobro y permiten verificar la empresa, el estado, el mes y si ya existe un CFDI emitido. |
| Pago registrado | `cantidad`, `moneda` | Total histórico efectivamente cobrado, con IVA incluido, y moneda del pago. El servidor toma ambos del registro del cobro. |

La ficha de la empresa puede guardar calle, números exterior e interior, colonia, municipio, ciudad y estado. El XML de este flujo utiliza únicamente el código postal del domicilio fiscal del receptor.

## 2. Configuración del emisor y del comprobante

| Dato | Valor o fuente en Horalia |
|---|---|
| Emisor | RFC, nombre fiscal, clave de régimen fiscal y código postal de expedición. El generador obtiene el RFC y el nombre del CSD y verifica que el RFC coincida con el configurado. |
| Sello digital | Certificado `.cer`, llave privada `.key.pem` y contraseña. Guardarlos en almacenamiento privado y validar la vigencia del certificado antes de firmar. |
| SIFEI | Usuario, contraseña, ID de equipo, URL de timbrado y modo de pruebas o producción. Cargar los secretos desde el entorno y verificar que la URL corresponda al modo. |
| Comprobante | `Version=4.0`, `TipoDeComprobante=I`, `MetodoPago=PUE`, `Exportacion=01`. El método es `PUE` porque el cobro ya se realizó. `FormaPago` lleva la clave del medio utilizado. |
| Identificación | Serie configurable, `H` en Horalia, folio consecutivo global por serie y fecha de emisión generada por el servidor. |
| Concepto | Una partida con cantidad `1`, `ClaveProdServ=81112501`, `ClaveUnidad=E48`, unidad `Unidad de servicio`, descripción `SUSCRIPCIÓN MENSUAL HORALIA` y `ObjetoImp=02`. |
| IVA | Traslado con `Impuesto=002`, `TipoFactor=Tasa` y `TasaOCuota=0.160000` para el IVA del 16 % configurado. |

## 3. Flujo de emisión

1. **Habilitar «Facturar» en el historial de pagos.** El pago debe ser exitoso, estar registrado en el mes calendario actual y seguir sin factura emitida. En Horalia, el perfil `1` tiene acceso global y el perfil `2` puede operar sobre sus empresas asignadas. Aplicar estas reglas en el servidor al abrir la pantalla y al confirmar.
2. **Mostrar los datos fiscales y el desglose.** Leer los datos del receptor desde su empresa. Si hay datos faltantes o inválidos, indicar cuáles son y bloquear la emisión. En Horalia, el perfil `1` o un usuario con `control_total` sobre la empresa puede editar su ficha fiscal. En la pantalla de facturación se capturan uso del CFDI, forma de pago y correo.
3. **Calcular desde el total histórico.** Trabajar en centavos enteros. Redondear `total / 1.16` a dos decimales para obtener el subtotal. Calcular `IVA = total − subtotal`. Por ejemplo, un pago de $1,160.00 genera $1,000.00 de subtotal y $160.00 de IVA. Usar el subtotal como valor unitario, importe del concepto y base del IVA.
4. **Reservar el folio y evitar duplicados.** En una transacción, bloquear el registro del pago y el consecutivo de la serie. Volver a comprobar la elegibilidad, reservar el folio y guardar una factura en estado `procesando`. Aplicar una restricción única por pago para impedir emisiones concurrentes.
5. **Generar, firmar y timbrar.** Construir el XML con CfdiUtils, firmarlo con el CSD y validar su estructura. Invocar `getCFDI` de SIFEI. La integración de Horalia lleva la serie dentro del XML y envía vacío el parámetro SOAP `Serie`. Extraer el XML timbrado de la respuesta y verificar que contenga el `TimbreFiscalDigital` con UUID.
6. **Registrar el timbrado y conservar el XML.** Guardar el UUID, la fecha de timbrado y el XML original devuelto por el PAC. Marcar el pago como facturado al registrar el timbre. Vincular la factura al pago y al usuario que la solicitó. Guardar serie, folio, opciones capturadas, importes, estado, errores y rutas de archivos.
7. **Preparar y entregar los archivos en segundo plano.** Generar el PDF a partir del XML timbrado e incluir el QR de verificación. Guardar ambos archivos en almacenamiento privado, Wasabi en Horalia. Encolar el correo con XML y PDF y habilitar su consulta desde el historial con los mismos permisos de empresa. El estado `correo_encolado` indica que el envío se programó.

## 4. Fallos y criterios de aceptación

- **Fallo confirmado antes del timbrado.** Mostrar el error y permitir un nuevo intento una vez corregida la causa, si el pago sigue siendo elegible.
- **Resultado incierto del PAC.** Ante un fallo SOAP, respuesta vacía o XML de respuesta sin timbre válido, guardar el estado `requiere_revision`. Bloquear otra solicitud hasta conciliar si SIFEI emitió el CFDI.
- **Fallo después del timbrado.** Conservar el UUID y el XML. Si falla el PDF, la subida o el correo, reintentar únicamente los pasos de entrega.
- **Casos que deben pasar.** Pago válido con emisión y descarga de ambos archivos, datos fiscales incompletos, pago fallido, pago de otro mes, acceso a una empresa ajena, doble clic al emitir, respuesta incierta de SIFEI y recuperación de archivos después de un timbrado exitoso.

Para el módulo nuevo, añadir dos validaciones de servidor además de comprobar que las claves existan en sus catálogos. Verificar que el uso del CFDI sea compatible con el régimen del receptor y que la forma de pago coincida con el cobro cuando se conoce. Horalia precarga la forma registrada y permite cambiarla.

## 5. Variables y arreglos de referencia

### Datos del emisor

| Campo del CFDI | Fuente |
|---|---|
| `Emisor.Rfc` | RFC leído del certificado de sello digital (CSD). Compararlo con `config('constantes.emisor.rfc')` antes de firmar. |
| `Emisor.Nombre` | Nombre fiscal leído del CSD por CfdiUtils. `constantes.emisor.razon_social` deja identificado al emisor en la configuración. |
| `Emisor.RegimenFiscal` | `config('constantes.emisor.regimen_clave')`. |
| `Comprobante.LugarExpedicion` | `config('constantes.emisor.codigo_postal')`. Conservarlo como texto. |
| `Comprobante.NoCertificado` y `Comprobante.Certificado` | Número y contenido del CSD que carga CfdiUtils. |
| `Comprobante.Sello` | Generado con la llave privada del CSD y su contraseña. |

Para reutilizar el módulo con otro emisor, obtener el RFC, el nombre y el régimen de sus datos fiscales, confirmar el código postal del lugar de expedición y cargar el CSD correspondiente. Los datos del receptor siguen saliendo de la empresa asociada al pago.

### Variables del entorno

| Variable | Clave en `config/services.php` | Valor que se debe cargar |
|---|---|---|
| `SIFEI_USUARIO` | `services.sifei.usuario` | Usuario asignado por SIFEI para el ambiente seleccionado. |
| `SIFEI_PASSWORD` | `services.sifei.password` | Contraseña de la cuenta SIFEI. |
| `SIFEI_ID_EQUIPO` | `services.sifei.id_equipo` | Identificador de equipo asignado por SIFEI. |
| `SIFEI_URL_TIMBRAR` | `services.sifei.url_timbrar` | URL del WSDL de timbrado del ambiente correspondiente. |
| `SIFEI_MODO` | `services.sifei.modo` | `pruebas` o `produccion`. El valor por defecto es `pruebas`. |
| `SIFEI_PWD_CSD_EMISOR` | `services.sifei.password_csd_emisor` | Contraseña de la llave privada del CSD. |

Cargar exactamente un archivo `.cer` y un archivo `.key.pem` en el directorio privado definido por `constantes.facturacion.disco_csd` y `constantes.facturacion.directorio_csd`.

### Arreglos de `config/constantes.php`

```php
return [
    'tasa_iva' => 16,

    'emisor' => [
        'rfc' => 'SAOJ750515T19',
        'razon_social' => 'JUAN CARLOS SALINAS OJEDA',
        'calle' => '3ra. Privada Vicente Guerrero ',
        'interior' => '12',
        'colonia' => 'San Francisco Culhuacan La Magdalena',
        'alcaldia' => 'Coyoacán',
        'ciudad' => 'CDMX',
        'codigo_postal' => '04260',
        'regimen_clave' => '612',
        'regimen' => '612 — Personas Físicas con Actividades Empresariales y Profesionales',
    ],

    'concepto_factura' => [
        'clave_prod_serv' => '81112501',
        'descripcion' => 'SUSCRIPCIÓN MENSUAL HORALIA',
        'clave_unidad' => 'E48',
        'unidad' => 'Unidad de servicio',
        'objeto_impuesto' => '02',
    ],

    'facturacion' => [
        'serie' => 'H',
        'ultimo_folio_inicial' => 1000,
        'tipo_comprobante' => 'I',
        'metodo_pago' => 'PUE',
        'exportacion' => '01',
        'disco_csd' => 'local',
        'directorio_csd' => 'csd',
        'logo_pdf' => 'assets/logos/logoLN.png',
        'qr_graphoria_url' => 'https://graphoria.dev/qr',
        'qr_sat_url' => 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx',
    ],

    'regimenes_fiscales' => [
        '601' => ['descripcion' => 'General de Ley Personas Morales', 'tipo_persona' => 'moral'],
        '603' => ['descripcion' => 'Personas Morales con Fines no Lucrativos', 'tipo_persona' => 'moral'],
        '605' => ['descripcion' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios', 'tipo_persona' => 'fisica'],
        '606' => ['descripcion' => 'Arrendamiento', 'tipo_persona' => 'fisica'],
        '607' => ['descripcion' => 'Régimen de Enajenación o Adquisición de Bienes', 'tipo_persona' => 'fisica'],
        '608' => ['descripcion' => 'Demás ingresos', 'tipo_persona' => 'fisica'],
        '610' => ['descripcion' => 'Residentes en el Extranjero sin Establecimiento Permanente en México', 'tipo_persona' => 'ambas'],
        '611' => ['descripcion' => 'Ingresos por Dividendos (socios y accionistas)', 'tipo_persona' => 'fisica'],
        '612' => ['descripcion' => 'Personas Físicas con Actividades Empresariales y Profesionales', 'tipo_persona' => 'fisica'],
        '614' => ['descripcion' => 'Ingresos por intereses', 'tipo_persona' => 'fisica'],
        '615' => ['descripcion' => 'Régimen de los ingresos por obtención de premios', 'tipo_persona' => 'fisica'],
        '616' => ['descripcion' => 'Sin obligaciones fiscales', 'tipo_persona' => 'fisica'],
        '620' => ['descripcion' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos', 'tipo_persona' => 'moral'],
        '621' => ['descripcion' => 'Incorporación Fiscal', 'tipo_persona' => 'fisica'],
        '622' => ['descripcion' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras', 'tipo_persona' => 'moral'],
        '623' => ['descripcion' => 'Opcional para Grupos de Sociedades', 'tipo_persona' => 'moral'],
        '624' => ['descripcion' => 'Coordinados', 'tipo_persona' => 'moral'],
        '625' => ['descripcion' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', 'tipo_persona' => 'fisica'],
        '626' => ['descripcion' => 'Régimen Simplificado de Confianza', 'tipo_persona' => 'ambas'],
    ],

    'uso_cfdi' => [
        'G01' => 'Adquisición de mercancías',
        'G02' => 'Devoluciones, descuentos o bonificaciones',
        'G03' => 'Gastos en general',
        'I01' => 'Construcciones',
        'I02' => 'Mobiliario y equipo de oficina por inversiones',
        'I03' => 'Equipo de transporte',
        'I04' => 'Equipo de cómputo y accesorios',
        'I05' => 'Dados, troqueles, moldes, matrices y herramental',
        'I06' => 'Comunicaciones telefónicas',
        'I07' => 'Comunicaciones satelitales',
        'I08' => 'Otra maquinaria y equipo',
        'D01' => 'Honorarios médicos, dentales y gastos hospitalarios',
        'D02' => 'Gastos médicos por incapacidad o discapacidad',
        'D03' => 'Gastos funerales',
        'D04' => 'Donativos',
        'D05' => 'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación)',
        'D06' => 'Aportaciones voluntarias al SAR',
        'D07' => 'Primas por seguros de gastos médicos',
        'D08' => 'Gastos de transportación escolar obligatoria',
        'D09' => 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones',
        'D10' => 'Pagos por servicios educativos (colegiaturas)',
        'S01' => 'Sin efectos fiscales',
        'CP01' => 'Pagos',
        'CN01' => 'Nómina',
    ],

    'formas_pago_sat' => [
        '03' => 'Transferencia electrónica de fondos',
        '04' => 'Tarjeta de crédito',
        '28' => 'Tarjeta de débito',
    ],
];
```

Las claves SAT deben viajar como cadenas en el XML y en el formulario, especialmente las que tienen ceros iniciales. `ultimo_folio_inicial` sirve para inicializar el consecutivo. Para emitir, leer y actualizar el consecutivo guardado en la base de datos para la serie.

## Implementación de referencia en Horalia

- [Controlador de pagos](../app/Http/Controllers/PaymentHistoryController.php)
- [Servicio de emisión](../app/Services/PaymentInvoiceIssuingService.php)
- [Cálculo de importes](../app/Services/PaymentInvoiceAmounts.php)
- [Generador CFDI 4.0](../app/Services/Cfdi40XmlBuilder.php)
- [Integración con SIFEI](../app/Services/SifeiTimbradoService.php)
- [Preparación de archivos y correo](../app/Jobs/PrepareInvoiceFilesAndEmail.php)
- [Configuración y catálogos](../config/constantes.php)
