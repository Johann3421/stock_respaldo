<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
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

    /**
     * Relación con las verificaciones de stock
     */
    public function stockVerifications()
    {
        return $this->hasMany(StockVerification::class);
    }

    /**
     * Cerrar todas las sesiones previas excepto la actual
     * Implementa single session: solo una sesión activa por usuario
     */
    public function logoutOtherDevices($currentSessionId = null)
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $currentSessionId = $currentSessionId ?? session()->getId();

        try {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        } catch (\Exception $e) {
            Log::warning('Error al cerrar sesiones previas: ' . $e->getMessage());
        }
    }

    /**
     * Obtener nombre completo o email para mostrar
     */
    public function getDisplayNameAttribute()
    {
        return $this->name ?? $this->email ?? 'Usuario';
    }
}
