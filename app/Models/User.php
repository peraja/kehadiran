<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'nip', 'nik', 'pangkat', 'jabatan', 'unit_name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nip',
        'nik',
        'name',
        'email',
        'password',
        'pangkat',
        'jabatan',
        'unit_name',
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

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    /**
     * Urutan prioritas peran (angka lebih kecil = prioritas lebih tinggi).
     */
    public const ROLE_PRIORITIES = [
        'admin' => 1,
        'admin_opd' => 2,
        'pimpinan' => 3,
        'pegawai' => 4,
    ];

    /**
     * Dapatkan peran default (prioritas tertinggi) milik pengguna.
     */
    public function defaultRole(): string
    {
        $userRoles = $this->roles->pluck('name')->toArray();
        if (empty($userRoles)) {
            return 'pegawai';
        }

        usort($userRoles, function ($a, $b) {
            $pA = self::ROLE_PRIORITIES[$a] ?? 99;
            $pB = self::ROLE_PRIORITIES[$b] ?? 99;
            return $pA <=> $pB;
        });

        return $userRoles[0];
    }

    /**
     * Dapatkan peran aktif saat ini dari session (atau default jika belum di-set / tidak valid).
     */
    public function currentRole(): string
    {
        $sessionRole = session('active_role');
        if ($sessionRole && $this->hasRole($sessionRole)) {
            return $sessionRole;
        }

        $default = $this->defaultRole();
        session(['active_role' => $default]);
        return $default;
    }

    /**
     * Periksa apakah peran aktif saat ini cocok dengan peran yang diminta.
     *
     * @param string|array $roles
     */
    public function hasActiveRole(string|array $roles): bool
    {
        $current = $this->currentRole();
        if (is_array($roles)) {
            return in_array($current, $roles, true);
        }

        return $current === $roles;
    }

    /**
     * Alias helper untuk hasActiveRole yang menerima satu atau banyak peran.
     */
    public function hasAnyActiveRole(string|array $roles): bool
    {
        return $this->hasActiveRole($roles);
    }

    /**
     * Beralih ke peran tertentu jika pengguna memiliki peran tersebut.
     */
    public function switchRole(string $role): bool
    {
        if ($this->hasRole($role)) {
            session(['active_role' => $role]);
            return true;
        }

        return false;
    }

    /**
     * Label human-readable untuk role.
     */
    public static function getRoleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Super Admin',
            'admin_opd' => 'Admin OPD',
            'pimpinan' => 'Pimpinan',
            'pegawai' => 'Pegawai',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    /**
     * Daftar seluruh peran milik pengguna yang diurutkan berdasarkan prioritas.
     */
    public function sortedRoles(): \Illuminate\Support\Collection
    {
        return $this->roles->sortBy(function ($role) {
            return self::ROLE_PRIORITIES[$role->name] ?? 99;
        })->values();
    }
}

