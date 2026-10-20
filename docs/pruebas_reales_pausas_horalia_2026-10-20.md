# Pruebas reales de pausas Horalia

**Fecha:** 20 de octubre de 2026  
**Entorno:** aplicación local de pruebas `http://127.0.0.1:8000`, `APP_ENV=local`, base MySQL `horalia` en `127.0.0.1`.  
**Empleado:** usuario de prueba perfil 2 y empleado asociado (Personal ID 1), centro en `America/Mexico_City`.  
**Autorización:** el usuario autorizó cámara, ubicación y envío/almacenamiento de evidencia de prueba para entrada y salida. No se incluyen coordenadas ni credenciales en este reporte.

## Preparación y limpieza

- Se asignó temporalmente el horario `PRUEBA PAUSAS 20261020` para permitir una jornada dentro de la ventana real.
- Al terminar, se restauraron las asignaciones de lunes a viernes al horario original `ADMINISTRATIVO`, 09:00–18:00.
- El horario de prueba se conserva **sin asignaciones**, por instrucción del usuario.
- Se preservaron los movimientos de prueba para trazabilidad. No se modificó la asistencia histórica 848 ni se operó sobre otros empleados.

## Casos de extremo a extremo

| Caso | Resultado | Evidencia / observación |
|---|---|---|
| P01 Iniciar comida sin entrada vigente | Aprobado | La pantalla de Pausas mostró “Sin entrada vigente”; acciones deshabilitadas e historial vacío. |
| P02 Registrar entrada | Aprobado | Asistencia **850**, 20/10/2026 00:40:38 local; confirmación visible. |
| P03 Iniciar COMIDA | Aprobado | Pausa **1**, 00:41:03. |
| P04 Iniciar SANITARIO con COMIDA activa | Parcial | Botón SANITARIO deshabilitado en la interfaz. No se invocó directamente el endpoint para comprobar el rechazo del servidor. |
| P05 Registrar salida con COMIDA activa | Aprobado | El servidor rechazó la salida con “Finaliza tu pausa en curso antes de registrar la salida”; la asistencia permaneció abierta. |
| P06 Finalizar COMIDA | Aprobado | Pausa 1 cerrada a las 00:41:50. |
| P07 Finalizar la misma pausa dos veces | Pendiente | Tras cerrarse, la interfaz retiró el control FINALIZAR; no se repitió una llamada al endpoint. |
| P08 Iniciar/finalizar SANITARIO | Aprobado | Pausa **2**, 00:42:03–00:43:09. |
| P09 Iniciar/finalizar DESCANSO | Aprobado | Pausa **3**, 00:43:14–00:43:34. |
| P10 Iniciar/finalizar PERMISO | Aprobado | Pausa **4**, 00:43:37–00:43:53. |
| P11 Segunda COMIDA independiente | Aprobado | Pausa **5**, 00:43:58–00:45:09. |
| P12 Registrar salida con pausas cerradas | Aprobado | Asistencia 850 cerrada a las 00:45:38 local; salida temprana de 29 minutos según la jornada temporal. |
| P13 Iniciar pausa después de la salida | Parcial | La página mostró “Sin entrada vigente” y controles deshabilitados. No se invocó directamente el endpoint tras la salida. |
| P14 Historial, detalle y reporte | Aprobado | El reporte mostró asistencia 850, entrada 00:40, salida 00:45, retardo 0, salida temprana 29, pausas 00:03 y tiempo productivo 00:01. El detalle listó las cinco pausas finalizadas. |
| P15 Persistencia tras recarga/navegación | Aprobado | Una pausa activa siguió visible al recargar/navegar; después se finalizó desde la aplicación. |
| P16 PIN inválido en checador | Aprobado para PIN inválido | El checador respondió “PIN no reconocido”; no avanzó al flujo de marcaje. No se probó empleado dado de baja. |

Los horarios y minutos anteriores son los que presentó la aplicación en la zona horaria del centro. Las duraciones de pausas de menos de un minuto aparecen como `00:00` en detalle, mientras el reporte agregado presenta `00:03` para el turno completo.

## Excel

El archivo se descargó desde el reporte real. Contiene las hojas `Asistencias` y `Pausas`; sus filas, filtros, horas, duraciones y estados coincidieron con lo mostrado en pantalla: [reporte_asistencia_2026-10-20_2026-10-20.xlsx](/Users/salinasj/Downloads/reporte_asistencia_2026-10-20_2026-10-20.xlsx).

## Checador y pruebas pendientes

Se abrió una sesión existente de checador y se verificó la pantalla principal, el botón PAUSAS, modo PAUSAS, captura de PIN y retorno visual ante PIN inválido. No se registró una nueva sesión ni se envió correo. No se completó en el checador un ciclo con PIN válido, pausa activa, finalización ni confirmación temporizada, porque la jornada de prueba ya estaba cerrada.

Pendiente de ejecución real:

- Llamada directa a los endpoints para validar el rechazo servidor de P04, P07 y P13.
- Carrera de dos inicios/finalizaciones simultáneos.
- Pausa abandonada y comportamiento al iniciar la jornada siguiente, sin falsificar fechas.
- Persistencia cruzada web-checador con pausa activa y finalización desde el checador.
- PIN de personal dado de baja.
- Pruebas en tablet vertical, incluyendo distribución y flujo completo de confirmaciones.
- Verificar rechazo por falta de cámara/ubicación y detalle visual de permisos en el dispositivo físico.

## Suite de pruebas automatizadas

`php artisan test --compact`: **47 pasaron, 8 fallaron (327 aserciones)**. Las 19 pruebas focalizadas de pausas habían pasado en la ejecución previa (158 aserciones).

Las ocho fallas de suite completa están en `AppAuthenticationTest`, `CompanyFiscalDetailsTest`, `DeleteCompanyCascadeTest`, `PaymentHistoryAccessTest` (3) y `PaymentInvoiceScreenTest` (2). Los mensajes son incompatibilidades entre expectativas de autenticación/empresa/fiscalidad/pagos y las respuestas HTML/redirect actuales: por ejemplo, nombre de empresa nulo, etiquetas esperadas ausentes y redirect de facturación a inicio. No atraviesan las rutas ni los controladores de pausas modificados; se clasifican como **ajenas en alcance** a esta función, aunque quedan como fallas existentes de la suite por corregir por separado.

## Resultado

El ciclo web real de entrada, pausas de los cuatro tipos, repetición de un tipo, bloqueo de salida con pausa activa, salida tras cerrar pausas, consulta y exportación queda verificado para este usuario y esta jornada local. Los casos parciales y pendientes se enumeran arriba; no se consideran aprobados.
