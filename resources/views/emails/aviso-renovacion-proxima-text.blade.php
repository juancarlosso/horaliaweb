⏰ ¡Tu renovación de Horalia está cerca!

Estimado cliente:

En Horalia queremos agradecerte por la confianza que has depositado en nosotros. Nos alegra seguir acompañándote en la gestión de asistencia de tus colaboradores. 💙

Te recordamos que tu suscripción está próxima a renovarse el {{ $renewalDate }}.

💳 Para garantizar que tu servicio continúe sin interrupciones, te invitamos a verificar que tu tarjeta registrada se encuentre vigente y cuente con fondos suficientes para realizar el cargo automático.

@if(($empresa?->metodo_pago ?? 'TARJETA') === 'TRANSFERENCIA')
Importe exacto: ${{ number_format((float) $empresa->precio, 2) }} MXN. Beneficiario: {{ config('constantes.pagos_transferencia.beneficiario') ?: 'consulta Métodos de Pago' }}. Concepto obligatorio: HORALIA {{ $empresa->referenciaTransferencia() }}.
Sube tu comprobante desde {{ $historyUrl }} al llegar el vencimiento. La validación puede tomar hasta 48 horas hábiles. Subir el comprobante no confirma el pago ni renueva el servicio. El sistema suspende el servicio después de cinco intentos automáticos fallidos; el código no establece un plazo fijo de siete días.
@else
✅ ¡No necesitas hacer nada más! Si tu método de pago está actualizado, la renovación se realizará automáticamente.
@endif

Gracias por permitirnos ser parte de tu empresa.

¡Seguimos trabajando para ofrecerte un Horalia cada día mejor! 🚀

El equipo de Horalia
🌐 https://horalia.mx
