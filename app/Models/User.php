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

    /**
     * Dapatkan seluruh daftar jabatan dan unit kerja pengguna (Definitif & Plt lintas OPD).
     *
     * @return array<int, array{jabatan: string, unit: string, is_plt: bool, badge: string}>
     */
    public function getAllPositions(): array
    {
        $roles = [];
        $nip = trim((string)$this->nip);

        // 1. Profil Lokal (Jabatan Utama/Definitif)
        if ($this->jabatan && $this->unit_name) {
            $isPlt = str_starts_with(strtolower(trim($this->jabatan)), 'plt');
            $roles[] = [
                'jabatan' => $this->jabatan,
                'unit' => $this->unit_name,
                'is_plt' => $isPlt,
                'badge' => $isPlt ? 'Plt' : 'Definitif',
            ];
        }

        // 2. Data SIMPEG jika tersimpan di cache
        if (!empty($nip)) {
            $allPnsMap = \Illuminate\Support\Facades\Cache::get('simpeg_all_pns_by_nip', []);
            $pnsRecords = $allPnsMap[$nip] ?? [];
            foreach ($pnsRecords as $r) {
                $j = trim($r['jabatan_nama'] ?? '');
                $u = trim($r['parent_unit'] ?? '');
                $isPlt = ($r['jabatan_status_id'] ?? '1') != '1' || str_starts_with(strtolower($j), 'plt');

                $clean = preg_replace('/^plt\.?\s*/i', '', $j);
                if (preg_match('/^kepala\s+dinas\b/i', $clean)) { $clean = 'Kepala Dinas'; }
                elseif (preg_match('/^sekretaris\s+dinas\b/i', $clean)) { $clean = 'Sekretaris Dinas'; }
                elseif (preg_match('/^kepala\s+badan\b/i', $clean)) { $clean = 'Kepala Badan'; }
                elseif (preg_match('/^sekretaris\s+badan\b/i', $clean)) { $clean = 'Sekretaris Badan'; }
                elseif (preg_match('/^inspektur\s+daerah\b/i', $clean) || $clean === 'Inspektur') { $clean = 'Inspektur Daerah'; }
                elseif (preg_match('/^sekretaris\s+dprd\b/i', $clean)) { $clean = 'Sekretaris DPRD'; }
                elseif (preg_match('/^sekretaris\s+daerah\b/i', $clean)) { $clean = 'Sekretaris Daerah'; }

                $formattedJabatan = ($isPlt ? 'Plt. ' : '') . trim($clean);

                $exists = false;
                foreach ($roles as $existing) {
                    if ($existing['jabatan'] === $formattedJabatan && $existing['unit'] === $u) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists && $formattedJabatan && $u) {
                    $roles[] = [
                        'jabatan' => $formattedJabatan,
                        'unit' => $u,
                        'is_plt' => $isPlt,
                        'badge' => $isPlt ? 'Plt' : 'Definitif',
                    ];
                }
            }

            // 3. Kepala OPD di tabel opds
            $leadOpds = Opd::where('leader_nip', $nip)->get();
            foreach ($leadOpds as $opd) {
                $jTitle = $opd->leader_title ?: 'Kepala OPD';
                $isPlt = str_starts_with(strtolower(trim($jTitle)), 'plt');
                $exists = false;
                foreach ($roles as $r) {
                    if ($r['jabatan'] === $jTitle && $r['unit'] === $opd->name) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $roles[] = [
                        'jabatan' => $jTitle,
                        'unit' => $opd->name,
                        'is_plt' => $isPlt,
                        'badge' => $isPlt ? 'Plt' : 'Definitif',
                    ];
                }
            }

            // 4. Pejabat Penandatangan di tabel opd_signers
            $signers = OpdSigner::where('nip', $nip)->with('opd')->get();
            foreach ($signers as $s) {
                $jTitle = $s->title ?: 'Pejabat Penandatangan';
                $opdName = $s->opd ? $s->opd->name : 'OPD';
                $isPlt = str_starts_with(strtolower(trim($jTitle)), 'plt');
                $exists = false;
                foreach ($roles as $r) {
                    if ($r['jabatan'] === $jTitle && $r['unit'] === $opdName) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $roles[] = [
                        'jabatan' => $jTitle,
                        'unit' => $opdName,
                        'is_plt' => $isPlt,
                        'badge' => $isPlt ? 'Plt' : 'Definitif',
                    ];
                }
            }
        }

        // Deduplikasi dan perapian
        $uniqueRoles = [];
        foreach ($roles as $r) {
            $key = strtolower(trim($r['jabatan'])) . '|' . strtolower(trim($r['unit']));
            if (!isset($uniqueRoles[$key])) {
                $uniqueRoles[$key] = $r;
            }
        }
        $roles = array_values($uniqueRoles);

        // Jika ada jabatan definitif yang sama dengan OPD berbeda karena sisa data lama, prioritaskan yang OPD aslinya
        $jabatanSeen = [];
        $finalRoles = [];
        foreach ($roles as $r) {
            $jKey = strtolower(trim($r['jabatan']));
            if (!$r['is_plt'] && isset($jabatanSeen[$jKey])) {
                continue;
            }
            if (!$r['is_plt']) {
                $jabatanSeen[$jKey] = true;
            }
            $finalRoles[] = $r;
        }

        // Urutkan: Definitif di atas, Plt di bawah
        usort($finalRoles, function ($a, $b) {
            if ($a['is_plt'] === $b['is_plt']) {
                return 0;
            }
            return $a['is_plt'] ? 1 : -1;
        });

        return $finalRoles;
    }
}

