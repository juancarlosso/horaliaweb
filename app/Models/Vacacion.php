<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacacion extends Model
{
    protected $table = 'vacaciones';
    protected $fillable = ['empresa_id', 'personal_id', 'autorizado_por_personal_id', 'registrado_por_user_id', 'fecha_inicio', 'fecha_fin', 'total_dias', 'estado', 'cancelado_por_user_id', 'cancelado_en'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'cancelado_en' => 'datetime'];
    }

    public function personal() { return $this->belongsTo(Personal::class); }
    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function autorizadoPor() { return $this->belongsTo(Personal::class, 'autorizado_por_personal_id'); }
    public function registradoPor() { return $this->belongsTo(User::class, 'registrado_por_user_id'); }
    public function canceladoPor() { return $this->belongsTo(User::class, 'cancelado_por_user_id'); }
    public function fechas() { return $this->hasMany(VacacionFecha::class, 'vacacion_id')->orderBy('fecha'); }
    public function detalles() { return $this->hasMany(VacacionDetalle::class, 'vacacion_id')->orderBy('fecha'); }
}
