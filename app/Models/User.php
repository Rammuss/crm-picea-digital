<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\LogsActivity;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'google_auth_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'google_auth_enabled' => 'boolean',
    ];

    public function canAccessFilament(): bool
    {
        return $this->is_active;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $current = auth()->user();
            if (! $current) {
                return;
            }

            if ($user->exists && $user->id === $current->id && $user->is_active === false) {
                throw ValidationException::withMessages([
                    'is_active' => 'No podes desactivarte a vos mismo.',
                ]);
            }

            $willBeAdmin = $user->role === 'admin';
            $willBeActive = (bool) $user->is_active;
            if ($user->exists && $user->getOriginal('role') === 'admin' && $user->getOriginal('is_active') && (! $willBeAdmin || ! $willBeActive)) {
                $otherActiveAdmins = self::query()
                    ->where('id', '!=', $user->id)
                    ->where('role', 'admin')
                    ->where('is_active', true)
                    ->count();

                if ($otherActiveAdmins === 0) {
                    throw ValidationException::withMessages([
                        'role' => 'Debe quedar al menos un admin activo en el sistema.',
                    ]);
                }
            }
        });

        static::deleting(function (self $user): void {
            $current = auth()->user();
            if ($current && $user->id === $current->id) {
                throw ValidationException::withMessages([
                    'delete' => 'No podes eliminarte a vos mismo.',
                ]);
            }

            if ($user->role === 'admin' && $user->is_active) {
                $otherActiveAdmins = self::query()
                    ->where('id', '!=', $user->id)
                    ->where('role', 'admin')
                    ->where('is_active', true)
                    ->count();

                if ($otherActiveAdmins === 0) {
                    throw ValidationException::withMessages([
                        'delete' => 'Debe quedar al menos un admin activo.',
                    ]);
                }
            }
        });
    }
}
