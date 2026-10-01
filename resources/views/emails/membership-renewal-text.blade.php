Hola{{ filled($recipientName) ? ' ' . $recipientName : '' }},

@if ($type === 'paid')
Se realizó correctamente el pago de la membresía {{ $companyName }} por ${{ $amount }} MXN.
La nueva fecha de renovación es {{ $renewalDate }}. Gracias por utilizar {{ $appName }}.
@elseif ($type === 'retry')
No fue posible realizar el cobro de la membresía de {{ $companyName }} por ${{ $amount }} MXN. Revisa los datos de tus tarjetas registradas o agrega otra tarjeta.
Volveremos a intentar realizar el cobro mañana. Puedes actualizar tus tarjetas desde {{ $appName }}.
@else
No fue posible procesar la renovación de la membresía {{ $companyName }} y se agotaron los cinco intentos automáticos. La empresa fue desactivada temporalmente.
El saldo pendiente es de ${{ $amount }} MXN. Puedes regularizar la membresía desde {{ $appName }} utilizando una tarjeta registrada.
@endif

Accede a tu cuenta: {{ $loginUrl }}

Correo automático de {{ $appName }}. Por favor, no respondas a este mensaje.
