<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Support\GraphoriaAvatar;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'profile',
        'password',
        'personal_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function empresas()
    {
        return $this->belongsToMany(Empresa::class, 'users_empresas')
            ->withPivot('control_total')
            ->withTimestamps();
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function avatarUrl(): string
    {
        return $this->personal?->fotoUrl() ?? GraphoriaAvatar::url($this->name);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
