<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\CentroController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\PaymentAttemptController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\MembershipPaymentController;
use App\Http\Controllers\PuestoController;
use App\Http\Controllers\HorarioEmpresaController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\ChecadorController;
use App\Http\Middleware\ValidateChecadorToken;
use App\Http\Controllers\ReporteAsistenciaController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\SessionController;

Route::middleware('guest')->group(function () {
    Route::get('/registro', [CompanyRegistrationController::class, 'create'])->name('registro.create');
    Route::post('/registro', [CompanyRegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('registro.store');
    Route::get('/registro/completado', [CompanyRegistrationController::class, 'completed'])
        ->name('registro.completado');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    Route::get('/password/forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::post('/home/empresas/{empresa}/membresia/pagar', [MembershipPaymentController::class, 'pay'])->whereNumber('empresa')->name('membership.pay');
    Route::post('/home/empresas/{empresa}/membresia/confirmar', [MembershipPaymentController::class, 'confirm'])->whereNumber('empresa')->name('membership.confirm');
    Route::get('/mi-perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mi-perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/mi-perfil/cambiar-contrasena', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/mi-perfil/cambiar-contrasena', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/mi-perfil/metodos-de-pago', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::get('/mi-perfil/suscripcion', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/mi-perfil/empresas/{empresa}/suscripcion/cancelar', [SubscriptionController::class, 'cancel'])->whereNumber('empresa')->name('subscription.cancel');
    Route::delete('/mi-perfil/empresas/{empresa}/suscripcion/cancelacion', [SubscriptionController::class, 'resume'])->whereNumber('empresa')->name('subscription.resume');
    Route::get('/home/pagos', [PaymentHistoryController::class, 'index'])->name('payment-history.index');
    Route::get('/home/pagos/{payment}', [PaymentHistoryController::class, 'show'])->whereNumber('payment')->name('payment-history.show');
    Route::get('/home/pagos/{payment}/factura/{format}', [PaymentHistoryController::class, 'invoiceDocument'])
        ->whereNumber('payment')
        ->whereIn('format', ['pdf', 'xml'])
        ->name('payment-history.invoice.document');
    Route::get('/home/pagos/{payment}/facturar', [PaymentHistoryController::class, 'invoice'])->whereNumber('payment')->name('payment-history.invoice');
    Route::post('/home/pagos/{payment}/facturar', [PaymentHistoryController::class, 'storeInvoiceOptions'])->whereNumber('payment')->name('payment-history.invoice.store');
    Route::post('/mi-perfil/metodos-de-pago/setup-intent', [PaymentMethodController::class, 'setupIntent'])->name('payment-methods.setup-intent');
    Route::post('/mi-perfil/metodos-de-pago/confirm', [PaymentMethodController::class, 'completeSetup'])->name('payment-methods.confirm');
    Route::put('/mi-perfil/metodos-de-pago/{paymentMethod}/predeterminada', [PaymentMethodController::class, 'makeDefault'])->where('paymentMethod', 'pm_[A-Za-z0-9]+')->name('payment-methods.default');
    Route::delete('/mi-perfil/metodos-de-pago/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->where('paymentMethod', 'pm_[A-Za-z0-9]+')->name('payment-methods.destroy');
    Route::get('/home/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
    Route::get('/home/empresas/{empresa}/intentos-de-pago', [PaymentAttemptController::class, 'index'])
        ->whereNumber('empresa')
        ->name('empresas.payment-attempts.index');
    Route::get('/home/empresas/create', [EmpresaController::class, 'create'])->name('empresas.create');
    Route::post('/home/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
    Route::get('/home/empresas/{empresa}/edit', [EmpresaController::class, 'edit'])->whereNumber('empresa')->name('empresas.edit');
    Route::put('/home/empresas/{empresa}', [EmpresaController::class, 'update'])->whereNumber('empresa')->name('empresas.update');
    Route::delete('/home/empresas/{empresa}', [EmpresaController::class, 'destroy'])->whereNumber('empresa')->name('empresas.destroy');
    Route::get('/home/centros-de-trabajo', [CentroController::class, 'index'])->name('centros.index');
    Route::get('/home/centros-de-trabajo/create', [CentroController::class, 'create'])->name('centros.create');
    Route::post('/home/centros-de-trabajo', [CentroController::class, 'store'])->name('centros.store');
    Route::get('/home/centros-de-trabajo/{centro}/edit', [CentroController::class, 'edit'])->whereNumber('centro')->name('centros.edit');
    Route::put('/home/centros-de-trabajo/{centro}', [CentroController::class, 'update'])->whereNumber('centro')->name('centros.update');
    Route::delete('/home/centros-de-trabajo/{centro}', [CentroController::class, 'destroy'])->whereNumber('centro')->name('centros.destroy');
    Route::get('/home/departamentos', [DepartamentoController::class, 'index'])->name('departamentos.index');
    Route::get('/home/departamentos/create', [DepartamentoController::class, 'create'])->name('departamentos.create');
    Route::post('/home/departamentos', [DepartamentoController::class, 'store'])->name('departamentos.store');
    Route::get('/home/departamentos/{departamento}/edit', [DepartamentoController::class, 'edit'])->whereNumber('departamento')->name('departamentos.edit');
    Route::put('/home/departamentos/{departamento}', [DepartamentoController::class, 'update'])->whereNumber('departamento')->name('departamentos.update');
    Route::delete('/home/departamentos/{departamento}', [DepartamentoController::class, 'destroy'])->whereNumber('departamento')->name('departamentos.destroy');
    Route::get('/home/puestos', [PuestoController::class, 'index'])->name('puestos.index');
    Route::get('/home/puestos/create', [PuestoController::class, 'create'])->name('puestos.create');
    Route::post('/home/puestos', [PuestoController::class, 'store'])->name('puestos.store');
    Route::get('/home/puestos/{puesto}/edit', [PuestoController::class, 'edit'])->whereNumber('puesto')->name('puestos.edit');
    Route::put('/home/puestos/{puesto}', [PuestoController::class, 'update'])->whereNumber('puesto')->name('puestos.update');
    Route::delete('/home/puestos/{puesto}', [PuestoController::class, 'destroy'])->whereNumber('puesto')->name('puestos.destroy');
    Route::get('/home/horarios', [HorarioEmpresaController::class, 'index'])->name('horarios.index');
    Route::get('/home/horarios/create', [HorarioEmpresaController::class, 'create'])->name('horarios.create');
    Route::post('/home/horarios', [HorarioEmpresaController::class, 'store'])->name('horarios.store');
    Route::get('/home/horarios/{horario}/edit', [HorarioEmpresaController::class, 'edit'])->whereNumber('horario')->name('horarios.edit');
    Route::put('/home/horarios/{horario}', [HorarioEmpresaController::class, 'update'])->whereNumber('horario')->name('horarios.update');
    Route::delete('/home/horarios/{horario}', [HorarioEmpresaController::class, 'destroy'])->whereNumber('horario')->name('horarios.destroy');
    Route::get('/home/personal', [PersonalController::class, 'index'])->name('personal.index');
    Route::get('/home/personal/create', [PersonalController::class, 'create'])->name('personal.create');
    Route::post('/home/personal', [PersonalController::class, 'store'])->name('personal.store');
    Route::get('/home/personal/{personal}/edit', [PersonalController::class, 'edit'])->whereNumber('personal')->name('personal.edit');
    Route::put('/home/personal/{personal}', [PersonalController::class, 'update'])->whereNumber('personal')->name('personal.update');
    Route::post('/home/personal/{personal}/regenerar-contrasena', [PersonalController::class, 'regeneratePassword'])->whereNumber('personal')->name('personal.password.regenerate');
    Route::post('/home/personal/{personal}/reenviar-pin', [PersonalController::class, 'resendPin'])->whereNumber('personal')->middleware('throttle:5,1')->name('personal.pin.resend');
    Route::delete('/home/personal/{personal}', [PersonalController::class, 'destroy'])->whereNumber('personal')->name('personal.destroy');
    Route::get('/home/checador', [ChecadorController::class, 'index'])->name('checador.index');
    Route::post('/home/checador', [ChecadorController::class, 'store'])->middleware('throttle:10,1')->name('checador.store');
    Route::post('/home/checador/{session}/enviar-enlace', [ChecadorController::class, 'emailActivationLink'])
        ->whereNumber('session')->middleware('throttle:10,1')->name('checador.email');
    Route::get('/home/asistencias', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::post('/home/asistencias/entrada', [AsistenciaController::class, 'markEntry'])->name('asistencia.entrada');
    Route::post('/home/asistencias/salida', [AsistenciaController::class, 'markExit'])->name('asistencia.salida');
    Route::get('/home/reportes/general', [ReporteAsistenciaController::class, 'index'])->name('reportes.general');
    Route::get('/home/reportes/asistencia', fn () => redirect()->route('reportes.general'))->name('reportes.asistencia');
    Route::get('/home/reportes/departamento', [ReporteAsistenciaController::class, 'byDepartment'])->name('reportes.departamento');
    Route::get('/home/reportes/departamento/detalle', [ReporteAsistenciaController::class, 'departmentDetail'])->name('reportes.departamento.detalle');
    Route::get('/home/reportes/asistencia/exportar', [ReporteAsistenciaController::class, 'export'])->name('reportes.asistencia.exportar');
    Route::get('/home/{section}', [DashboardController::class, 'module'])
        ->where('section', 'empresas|centros-de-trabajo|departamentos|puestos|horarios|personal|asistencia|reportes')
        ->name('dashboard.module');
});

Route::get('/checador/activar/{session}', [ChecadorController::class, 'activate'])
    ->whereNumber('session')->middleware('signed')->name('checador.activate');
Route::get('/checador/hora', [ChecadorController::class, 'clock'])->name('checador.clock');
Route::post('/checador/validar-pin', [ChecadorController::class, 'verifyPin'])
    ->middleware([ValidateChecadorToken::class, 'throttle:checador-pin'])->name('checador.verify-pin');
Route::post('/checador/marcar', [ChecadorController::class, 'mark'])
    ->middleware([ValidateChecadorToken::class, 'throttle:12,1'])->name('checador.mark');

$showLegalDocument = static function (string $filename, string $title) {
    return static function () use ($filename, $title) {
        $markdown = file_get_contents(resource_path('legal/' . $filename));

        abort_if($markdown === false, 404);

        return view('legal.document', [
            'title' => $title,
            'content' => Str::markdown($markdown),
        ]);
    };
};

Route::get('/', function () {
    return view('welcome');
})->name('inicio');

Route::get('/terminos-y-condiciones', $showLegalDocument('terminos-y-condiciones.md', 'Términos y Condiciones'))
    ->name('legal.terms');

Route::get('/politica-de-privacidad', $showLegalDocument('politica-de-privacidad.md', 'Política de Privacidad'))
    ->name('legal.privacy');

Route::get('/politica-de-cookies', $showLegalDocument('politica-de-cookies.md', 'Política de Cookies'))
    ->name('legal.cookies');
