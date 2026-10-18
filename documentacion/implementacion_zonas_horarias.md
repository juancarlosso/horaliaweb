# Implementación de zonas horarias por centro

## Cambios incluidos

- `centros.timezone` usa identificadores IANA y queda en `America/Mexico_City` para centros existentes y nuevos.
- El formulario de centros ofrece Ciudad de México, Acuña, Tijuana, Cancún, Hermosillo, Chihuahua capital, Ciudad Juárez, Ojinaga, Mazatlán y Monterrey. Las correspondencias y reglas vigentes se contrastaron con la Ley de los Husos Horarios publicada por el DOF y la base IANA tzdb. IANA mantiene reglas separadas para las zonas fronterizas de Chihuahua; no se debe asignar la zona de la capital a Juárez u Ojinaga.
- Cada entrada/salida nueva guarda la hora local, el instante UTC, el centro y la zona usados en ese movimiento.
- La fecha de asistencia corresponde a la fecha local de entrada y no se modifica al registrar la salida.
- El registro web y PIN comparte transacción, bloquea al empleado y el centro y toma una sola hora UTC por movimiento. La asignación de centro durante una solicitud queda serializada por el bloqueo de la fila del empleado.
- Durante la migración de campos de asistencia, cada registro histórico se vincula por lotes al centro válido que el empleado tiene asignado en ese momento. Solo se asigna el centro de entrada si existe `llegada` y el de salida si existe `salida`. Los registros sin empleado o centro válido permanecen con referencias nulas y quedan reportados en el log. Las horas UTC y zonas históricas se conservan en `NULL`.
- Los campos que consume el historial y los reportes no cambian. La zona global de Laravel, la base de datos y el reloj visible del checador tampoco cambian.

Las referencias a centros en asistencias restringen el borrado del centro cuando tiene movimientos asociados. Esto protege el dato de atribución histórica; una solicitud de baja de ese centro deberá resolverse después de reasignar o conservar sus referencias.

## Despliegue (no ejecutado)

1. Respaldar la base y verificar que el respaldo pueda restaurarse.
2. Programar el cambio y pausar temporalmente las escrituras de asistencia web y PIN durante la migración para que el conjunto histórico quede definido.
3. Desplegar el código de esta rama y ejecutar una sola vez `php artisan migrate`. La migración agrega los campos y vincula las asistencias existentes en lotes de 500; no se requieren comandos adicionales.
4. Revisar el log de Laravel para conocer cuántos movimientos quedaron sin centro válido. Estos conservan las referencias nulas y no impiden completar la migración.
5. Comparar antes/después conteos y sumas de `fecha`, `llegada`, `salida`, `minutos_tarde`, `minutos_salida_temprano`, `created_at` y `updated_at`; comprobar que `llegada_utc`, `salida_utc`, `timezone_entrada` y `timezone_salida` históricos siguen nulos.
6. Reanudar las escrituras y validar una entrada y salida controladas por web y PIN en zonas representativas. Revisar historial, reporte y Excel contra los valores locales guardados.

La migración asigna únicamente `centro_trabajo_entrada_id` cuando `llegada` tiene valor y `centro_trabajo_salida_id` cuando `salida` tiene valor. Usa el centro actualmente asociado al empleado, siempre que pertenezca a su misma empresa. La actualización directa por lotes no cambia fechas, horas locales, minutos calculados ni timestamps. No rellena valores UTC ni zonas horarias para movimientos históricos. Si el empleado no existe o su centro actual no es válido, deja la referencia correspondiente en `NULL` y registra el conteo en el log. No ejecutar el despliegue productivo hasta autorizarlo y coordinar respaldo, pausa de escrituras y verificación.

## Reversión

La reversión de esquema requiere autorización y respaldo. Las migraciones `down` eliminan las columnas agregadas; por ello, revertir después de registrar movimientos nuevos descartaría su atribución UTC y de centro. En una reversión de aplicación, conservar primero las migraciones y los campos permite que los valores locales sigan siendo legibles por la reportería existente.

## Cobertura y límites conocidos

Las jornadas nocturnas conservan la regla existente: solo se busca una salida pendiente anterior cuando el horario termina a una hora igual o anterior a la entrada. Para una entrada y salida en zonas distintas, la métrica de salida temprana interpreta la hora programada en la zona vigente al registrar la salida y conserva `fecha` como el día de inicio de la jornada. Los horarios siguen perteneciendo al empleado; no se agregaron reglas de horario por centro ni se amplió la unicidad diaria.

Los centros con asistencias asociadas no se pueden borrar físicamente por la nueva clave foránea. La interfaz actual no tiene una acción de reasignación de historial; si se necesita borrar uno, habrá que definir y autorizar esa operación explícitamente.
