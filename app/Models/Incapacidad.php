<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incapacidad extends Model
{
    protected $table = 'incapacidades';
    protected $fillable = ['empresa_id', 'personal_id', 'registrado_por_user_id', 'fecha_inicio', 'fecha_fin', 'motivo', 'observaciones', 'jornadas_afectadas', 'snapshot', 'snapshot_generado_en', 'estado', 'cancelado_por_user_id', 'cancelado_en'];
    protected function casts(): array { return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'snapshot' => 'array', 'snapshot_generado_en' => 'datetime', 'cancelado_en' => 'datetime']; }
    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function personal() { return $this->belongsTo(Personal::class); }
    public function registradoPor() { return $this->belongsTo(User::class, 'registrado_por_user_id'); }
    public function canceladoPor() { return $this->belongsTo(User::class, 'cancelado_por_user_id'); }
    public function comprobantes() { return $this->hasMany(IncapacidadComprobante::class); }
}
