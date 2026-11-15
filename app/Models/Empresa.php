<?php

namespace App\Models;

use App\Helper\Wasabi;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Empresa extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Empresa $empresa) {
            $fechaInicio = $empresa->created_at
                ? Carbon::parse($empresa->created_at)->startOfDay()
                : now()->startOfDay();

            $empresa->precio ??= config('constantes.precio_mensual_por_empresa_mxn');
            $empresa->metodo_pago ??= 'TARJETA';
            $empresa->fecha_inicio ??= $fechaInicio->toDateString();
            $empresa->fecha_renovacion ??= $fechaInicio->copy()->addMonthNoOverflow()->toDateString();
        });
    }

    protected $fillable = [
        'rfc',
        'nombre',
        'razon_social',
        'direccion',
        'domicilio_calle',
        'domicilio_numero_exterior',
        'domicilio_numero_interior',
        'domicilio_colonia',
        'domicilio_codigo_postal',
        'domicilio_municipio',
        'domicilio_ciudad',
        'domicilio_estado',
        'regimen_fiscal',
        'correo_facturacion',
        'telefono',
        'minutos_tolerancia_entrada',
        'metros_distancia_entrada',
        'incluir_pausas_tiempo_trabajado',
        'logo',
        'activa',
        'stripe_customer_id',
        'stripe_default_payment_method_id',
        'intentos',
        'cancelar_al_renovar',
        'metodo_pago',
        'referencia_transferencia',
    ];

    protected function casts(): array
    {
        return [
            'minutos_tolerancia_entrada' => 'integer',
            'metros_distancia_entrada' => 'integer',
            'incluir_pausas_tiempo_trabajado' => 'boolean',
            'activa' => 'boolean',
            'precio' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_renovacion' => 'date',
            'intentos' => 'integer',
            'cancelar_al_renovar' => 'boolean',
            'metodo_pago' => 'string',
        ];
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'users_empresas')
            ->withPivot('control_total')
            ->withTimestamps();
    }

    public function centros()
    {
        return $this->hasMany(Centro::class);
    }

    public function departamentos()
    {
        return $this->hasMany(Departamento::class);
    }

    public function puestos()
    {
        return $this->hasMany(Puesto::class);
    }

    public function horarios()
    {
        return $this->hasMany(HorarioEmpresa::class, 'empresa_id');
    }

    public function personal()
    {
        return $this->hasMany(Personal::class, 'empresa_id');
    }

    public function intentosPago()
    {
        return $this->hasMany(IntentoPago::class);
    }

    public function transferencias()
    {
        return $this->hasMany(TransferenciaPago::class);
    }

    public function referenciaTransferencia(): string
    {
        if ($this->referencia_transferencia) return $this->referencia_transferencia;
        $letra = chr(random_int(65, 90));
        $reference = 'H'.$this->id.$letra;
        $this->forceFill(['referencia_transferencia' => $reference])->save();
        return $reference;
    }

    public function logoUrl(): ?string
    {
        if ($this->logo && str_starts_with($this->logo, 'empresas/logos/')) {
            return Storage::disk('public')->exists($this->logo)
                ? Storage::disk('public')->url($this->logo)
                : null;
        }

        return Wasabi::url($this->logo);
    }
}
