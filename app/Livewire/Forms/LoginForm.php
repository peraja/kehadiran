<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $nip = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $nip = trim($this->nip);
        $password = $this->password;

        $baseUrl = config('services.simpeg.url', 'http://apps.sinjaikab.go.id/api/pegawai');
        $timeout = config('services.simpeg.timeout', 10);

        // 1. Try API Kepegawaian Authentication
        try {
            $authResponse = \Illuminate\Support\Facades\Http::timeout(min(6, $timeout))->get("{$baseUrl}/user_auth/", [
                'username' => $nip,
                'password' => $password
            ]);

            $authBody = trim($authResponse->body());

            if ($authResponse->successful() && !empty($authBody)) {
                // API Auth Succeeded -> Fetch full employee details
                $pegawaiResponse = \Illuminate\Support\Facades\Http::timeout(min(5, $timeout))->get("{$baseUrl}/data_pegawai/", [
                    'nip' => $nip
                ]);

                $pegawaiData = $pegawaiResponse->json();
                $pData = isset($pegawaiData['data']) ? $pegawaiData['data'] : (isset($pegawaiData[0]) ? $pegawaiData[0] : $pegawaiData);

                $name = $pData['nama_pegawai'] ?? $pData['nama'] ?? $nip;
                $unit_id = $pData['unit_id'] ?? $pData['id_unit'] ?? null;
                $rawJabatan = $pData['jabatan_nama'] ?? $pData['jabatan'] ?? null;
                $pangkat = $pData['pangkat_nama'] ?? $pData['pangkat'] ?? null;
                $childUnit = trim((string)($pData['jabatan_grup'] ?? ''));
                $parentUnit = null;

                if ($unit_id) {
                    $unitResponse = \Illuminate\Support\Facades\Http::timeout(min(4, $timeout))->get("{$baseUrl}/get_unit/", [
                        'unit_id' => $unit_id
                    ]);
                    $unitData = $unitResponse->json();
                    $uData = isset($unitData['data']) ? $unitData['data'] : (isset($unitData[0]) ? $unitData[0] : $unitData);
                    $parentUnit = $uData['unit_nama'] ?? $uData['nama_unit'] ?? $uData['unit_kerja'] ?? null;
                }

                // Discover all positions for this employee (cross-OPD aware via cached index)
                $allPnsMap = \Illuminate\Support\Facades\Cache::get('simpeg_all_pns_by_nip', []);
                $roles = [];

                if (!empty($allPnsMap[$nip]) && is_array($allPnsMap[$nip])) {
                    foreach ($allPnsMap[$nip] as $item) {
                        $pUnit = $item['parent_unit'] ?? $parentUnit;
                        $cUnit = trim((string)($item['jabatan_grup'] ?? ''));
                        $rJabatan = $item['jabatan_nama'] ?? $rawJabatan;
                        $norm = $this->normalizePosition($pUnit, $cUnit, $rJabatan);

                        $exists = false;
                        foreach ($roles as $existingRole) {
                            if ($existingRole['jabatan'] === $norm['jabatan'] && $existingRole['unit'] === $norm['unit']) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $roles[] = $norm;
                        }
                    }
                }

                // If not in cross-OPD cache, discover positions in the employee's unit
                if (empty($roles) && $unit_id) {
                    $listArray = \Illuminate\Support\Facades\Cache::remember("simpeg_unit_pegawai_{$unit_id}", 600, function () use ($baseUrl, $unit_id) {
                        try {
                            $listResponse = \Illuminate\Support\Facades\Http::timeout(5)->get("{$baseUrl}/get_pegawai/", [
                                'unit_id' => $unit_id
                            ]);
                            $listData = $listResponse->json();
                            return isset($listData['data']) ? $listData['data'] : (isset($listData[0]) ? $listData[0] : $listData);
                        } catch (\Throwable $e) {
                            return [];
                        }
                    });

                    if (is_array($listArray)) {
                        foreach ($listArray as $item) {
                            if (($item['nip'] ?? '') === $nip) {
                                $cUnit = trim((string)($item['jabatan_grup'] ?? ''));
                                $rJabatan = $item['jabatan_nama'] ?? $rawJabatan;
                                $norm = $this->normalizePosition($parentUnit, $cUnit, $rJabatan);

                                $exists = false;
                                foreach ($roles as $existingRole) {
                                    if ($existingRole['jabatan'] === $norm['jabatan'] && $existingRole['unit'] === $norm['unit']) {
                                        $exists = true;
                                        break;
                                    }
                                }
                                if (!$exists) {
                                    $roles[] = $norm;
                                }
                            }
                        }
                    }
                }

                if (empty($roles)) {
                    $roles[] = $this->normalizePosition($parentUnit, $childUnit, $rawJabatan);
                }

                // Prioritaskan jabatan definitif di posisi pertama
                usort($roles, function ($a, $b) {
                    if ($a['is_plt'] === $b['is_plt']) {
                        return 0;
                    }
                    return $a['is_plt'] ? 1 : -1;
                });

                $jabatan = $roles[0]['jabatan'];
                $unit_name = $parentUnit ?: ($roles[0]['unit'] ?? 'Pemerintah Kabupaten Sinjai');

                $userData = [
                    'name' => $name,
                    'jabatan' => $jabatan,
                    'unit_name' => $unit_name,
                ];

                if (!empty($pangkat)) {
                    $userData['pangkat'] = trim((string)$pangkat);
                }

                $user = \App\Models\User::updateOrCreate(
                    ['nip' => $nip],
                    $userData
                );

                \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'pegawai']);

                if ($user->wasRecentlyCreated || $user->roles()->count() === 0) {
                    $user->assignRole('pegawai');
                }

                \Illuminate\Support\Facades\Auth::login($user, $this->remember);
                $user->currentRole(); // Inisialisasi session active_role berdasarkan prioritas tertinggi
                \Illuminate\Support\Facades\RateLimiter::clear($this->throttleKey());
                \App\Services\AuditLogger::log('login', 'Login SIMPEG', $user);
                return;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("SIMPEG Auth Error for NIP {$nip}: " . $e->getMessage());
            // If external API fails, continue to fallback to local DB check
        }

        // 2. Fallback to local authentication (for default admin 'kalamangna' or users with local password)
        if (\Illuminate\Support\Facades\Auth::attempt(['nip' => $nip, 'password' => $password], $this->remember)) {
            $localUser = \Illuminate\Support\Facades\Auth::user();
            $localUser->currentRole(); // Inisialisasi session active_role
            \Illuminate\Support\Facades\RateLimiter::clear($this->throttleKey());
            \App\Services\AuditLogger::log('login', 'Login Akun Lokal', $localUser);
            return;
        }

        \Illuminate\Support\Facades\RateLimiter::hit($this->throttleKey());

        // Check if user exists locally or in API Kepegawaian
        $userExistsLocally = \App\Models\User::where('nip', $nip)->exists();
        $userExistsInApi = false;

        if (!$userExistsLocally) {
            try {
                $checkPegawai = \Illuminate\Support\Facades\Http::timeout(3)->get("{$baseUrl}/data_pegawai/", [
                    'nip' => $nip
                ]);
                if ($checkPegawai->successful()) {
                    $checkData = $checkPegawai->json();
                    $cData = isset($checkData['data']) ? $checkData['data'] : (isset($checkData[0]) ? $checkData[0] : $checkData);
                    if (!empty($cData['nama'] ?? $cData['nama_pegawai'] ?? null)) {
                        $userExistsInApi = true;
                    }
                }
            } catch (\Exception $e) {
                // Ignore API connection issue
            }
        }

        if (!$userExistsLocally && !$userExistsInApi) {
            throw ValidationException::withMessages([
                'form.nip' => 'NIP tidak terdaftar.',
            ]);
        }

        throw ValidationException::withMessages([
            'form.password' => 'Kata sandi salah.',
        ]);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.nip' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->nip).'|'.request()->ip());
    }

    protected function normalizePosition(?string $parentUnit, ?string $childUnit, ?string $rawJabatan): array
    {
        $displayUnit = $parentUnit ?: 'Pemerintah Kabupaten Sinjai';
        $jabatan = $rawJabatan ?: '-';
        $jabatan = preg_replace('/\s+/', ' ', trim($jabatan));

        if ($parentUnit && !empty($childUnit)) {
            $parentLower = strtolower($parentUnit);
            $childLower = strtolower($childUnit);

            // 1. Kantor Kecamatan & Kelurahan
            if (str_contains($parentLower, 'kecamatan')) {
                $kelurahanMap = [
                    'bongki' => 'Kantor Kelurahan Bongki',
                    'biringere' => 'Kantor Kelurahan Biringere',
                    'lappa' => 'Kantor Kelurahan Lappa',
                    'balangnipa' => 'Kantor Kelurahan Balangnipa',
                    'alehanuae' => 'Kantor Kelurahan Alehanuae',
                    'lamatti' => 'Kantor Kelurahan Lamatti Rilau',
                    'samataring' => 'Kantor Kelurahan Samataring',
                    'samaenre' => 'Kantor Kelurahan Samaenre',
                    'sangiasseri' => 'Kantor Kelurahan Sangiasseri',
                    'sangiaserri' => 'Kantor Kelurahan Sangiasseri',
                    'balakia' => 'Kantor Kelurahan Balakia',
                    'tassililu' => 'Kantor Kelurahan Tassililu',
                    'pasir putih' => 'Kantor Kelurahan Pasir Putih',
                    'pair putih' => 'Kantor Kelurahan Pasir Putih',
                    'mannanti' => 'Kantor Kelurahan Mannanti',
                ];

                $matchedKelurahan = null;
                foreach ($kelurahanMap as $key => $officialKelurahan) {
                    if (str_contains($childLower, $key)) {
                        $matchedKelurahan = $officialKelurahan;
                        break;
                    }
                }

                $jLower = strtolower(trim((string)$jabatan));
                $isPlt = str_starts_with($jLower, 'plt.') || str_starts_with($jLower, 'plt ') || str_starts_with($jLower, 'plt.kepala') || str_starts_with($jLower, 'plt.sekretaris');
                $prefix = $isPlt ? 'Plt. ' : '';
                $cleanPos = preg_replace('/^plt\.?\s*/i', '', (string)$jabatan);

                if ($matchedKelurahan) {
                    $displayUnit = $matchedKelurahan;

                    if (preg_match('/^sekretaris\s+(kelurahan|lurah)\b/i', $cleanPos) || str_contains($jLower, 'sekretaris kelurahan') || str_contains($jLower, 'sekretaris lurah')) {
                        $jabatan = $prefix . 'Sekretaris Lurah';
                    } elseif (preg_match('/^lurah\b/i', $cleanPos) || (str_contains($jLower, 'lurah') && !str_contains($jLower, 'seksi') && !str_contains($jLower, 'kasi'))) {
                        $jabatan = $prefix . 'Lurah';
                    } elseif (str_contains($jLower, 'penelaah')) {
                        $jabatan = 'Penelaah Teknis Kebijakan';
                    } elseif (str_contains($jLower, 'pengolah data') || str_contains($jLower, 'pengolah  data')) {
                        $jabatan = 'Pengolah Data dan Informasi';
                    } elseif (str_contains($jLower, 'pengadministrasi')) {
                        $jabatan = 'Pengadministrasi Perkantoran';
                    } elseif (str_contains($jLower, 'seksi') || str_contains($jLower, 'kasi')) {
                        if (str_contains($jLower, 'pelayanan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pelayanan Umum';
                        } elseif (str_contains($jLower, 'pemberdayaan') || str_contains($jLower, 'pembangunan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pembangunan dan Pemberdayaan Masyarakat';
                        } elseif (str_contains($jLower, 'pemerintahan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pemerintahan';
                        }
                    }
                } else {
                    $displayUnit = $parentUnit;

                    if (preg_match('/^sekretaris\s+(camat|kecamatan)\b/i', $cleanPos) || str_contains($jLower, 'sekretaris camat') || str_contains($jLower, 'sekretaris kecamatan')) {
                        $jabatan = $prefix . 'Sekretaris Camat';
                    } elseif (preg_match('/^camat\b/i', $cleanPos) || (str_contains($jLower, 'camat') && !str_contains($jLower, 'seksi') && !str_contains($jLower, 'kasi') && !str_contains($jLower, 'sub bagian') && !str_contains($jLower, 'kasubag'))) {
                        $jabatan = $prefix . 'Camat';
                    } elseif (str_contains($jLower, 'penelaah')) {
                        $jabatan = 'Penelaah Teknis Kebijakan';
                    } elseif (str_contains($jLower, 'pengolah data') || str_contains($jLower, 'pengolah  data')) {
                        $jabatan = 'Pengolah Data dan Informasi';
                    } elseif (str_contains($jLower, 'pengadministrasi')) {
                        $jabatan = 'Pengadministrasi Perkantoran';
                    } elseif (str_contains($jLower, 'umum dan kepegawaian')) {
                        $jabatan = $prefix . 'Kepala Sub Bagian Umum dan Kepegawaian';
                    } elseif (str_contains($jLower, 'program dan keuangan') || str_contains($jLower, 'perencanaan dan keuangan')) {
                        $jabatan = $prefix . 'Kepala Sub Bagian Program dan Keuangan';
                    } elseif (str_contains($jLower, 'seksi') || str_contains($jLower, 'kasi')) {
                        if (str_contains($jLower, 'pelayanan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pelayanan Umum';
                        } elseif (str_contains($jLower, 'ketentraman') || str_contains($jLower, 'trantib') || str_contains($jLower, 'keamanan')) {
                            $jabatan = $prefix . 'Kepala Seksi Ketentraman dan Ketertiban Umum';
                        } elseif (str_contains($jLower, 'ekonomi') || str_contains($jLower, 'kesejahteraan')) {
                            $jabatan = $prefix . 'Kepala Seksi Ekonomi dan Kesejahteraan Rakyat';
                        } elseif (str_contains($jLower, 'pembangunan') || str_contains($jLower, 'pemberdayaan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pembangunan dan Pemberdayaan Masyarakat';
                        } elseif (str_contains($jLower, 'pemerintahan')) {
                            $jabatan = $prefix . 'Kepala Seksi Pemerintahan';
                        }
                    }
                }
            }
            // 2. Dinas Pendidikan (tampilkan nama Sekolah / Satuan Pendidikan saja)
            elseif (str_contains($parentLower, 'pendidikan')) {
                if (!str_contains($childLower, 'bidang') && !str_contains($childLower, 'sekretariat') && !str_contains($childLower, 'sub bagian') && !str_contains($childLower, 'seksi')) {
                    if (preg_match('/(sd|smp|tk|paud|negeri|sekolah|spf|uptd)/i', $childUnit)) {
                        $displayUnit = $this->formatSchoolUnitName($childUnit);
                    }
                }
            }
            // 3. Dinas Kesehatan (tampilkan nama Puskesmas / RSUD / Faskes saja)
            elseif (str_contains($parentLower, 'kesehatan')) {
                if (preg_match('/(puskesmas|rsud|rs\s+pratama|bulupac|bulupan|pustu|faskes|uptd|klinik|lab|psc)/i', $childUnit)) {
                    $displayUnit = $this->formatHealthUnitName($childUnit);
                }
            }
        }

        // 4. Normalisasi Global Jabatan Dinas, Badan, Inspektorat, Setda, Setwan
        $jLower = strtolower(trim((string)$jabatan));
        $isPlt = str_starts_with($jLower, 'plt.') || str_starts_with($jLower, 'plt ') || str_starts_with($jLower, 'plt.kepala') || str_starts_with($jLower, 'plt.sekretaris');
        $prefix = $isPlt ? 'Plt. ' : '';

        $clean = preg_replace('/^plt\.?\s*/i', '', $jabatan);
        $clean = preg_replace('/^plt\.kepala\s*/i', 'Kepala ', $clean);
        $clean = preg_replace('/^plt\.sekretaris\s*/i', 'Sekretaris ', $clean);

        // A. Pimpinan Utama OPD, Puskesmas & Sekolah
        if (preg_match('/^kepala dinas\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Dinas';
        } elseif (preg_match('/^sekretaris dinas\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Dinas';
        } elseif (preg_match('/^kepala badan\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Badan';
        } elseif (preg_match('/^sekretaris badan\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Badan';
        } elseif (preg_match('/^inspektur daerah\b/i', $clean) || (preg_match('/^inspektur\b/i', $clean) && !preg_match('/inspektur pembantu/i', $clean))) {
            $jabatan = $prefix . 'Inspektur Daerah';
        } elseif (preg_match('/^sekretaris dprd\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris DPRD';
        } elseif (preg_match('/^sekretaris daerah\b/i', $clean)) {
            $jabatan = $prefix . 'Sekretaris Daerah';
        } elseif (preg_match('/^ktu\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Sub Bagian Tata Usaha';
        } elseif (preg_match('/^kepala\s+(uptd\s+)?puskesmas\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Puskesmas';
        } elseif (preg_match('/^kepala\s+(uptd\s+)?(sd|sdn|smp|smpn|tk|paud|sekolah)\b/i', $clean)) {
            $jabatan = $prefix . 'Kepala Sekolah';
        } elseif (preg_match('/^direktur\s+(uptd\s+)?(rsud|rs)\b.*(bulupancing|bulupaccing|bulu\s+paccing)/i', $clean)) {
            $jabatan = $prefix . 'Direktur RS Pratama Bulupaccing';
        } elseif (preg_match('/^direktur\s+(rsud|rs)\s*(sinjai)?/i', $clean)) {
            $jabatan = $prefix . 'Direktur RSUD Sinjai';
        } else {
            // B. Bersihkan akhiran wilayah/induk pada jabatan dinas/badan/rsud/puskesmas/sekolah
            $clean = preg_replace('/\s+dinas\s+pemuda\s+dan\s+olahraga.*$/i', '', $clean);
            $clean = preg_replace('/\s+satpol\s+pp.*$/i', '', $clean);
            $clean = preg_replace('/\s+(pada|di)\s+.+$/i', '', $clean);
            $clean = preg_replace('/\s+(uptd\s+)?(rsud|rs)\b.*$/i', '', $clean);
            $clean = preg_replace('/\s+(uptd\s+)?puskesmas\b.*$/i', '', $clean);
            $clean = preg_replace('/\s+(uptd\s+)?(sd|sdn|smp|smpn|tk|paud|sekolah)\b.*$/i', '', $clean);
            $clean = preg_replace('/\s+kab\.?\s+sinjai.*$/i', '', $clean);
            $clean = preg_replace('/\s+kabupaten\s+sinjai.*$/i', '', $clean);
            $clean = preg_replace('/\s+sekretariat\s+daerah.*$/i', '', $clean);
            $clean = preg_replace('/\s+sekretariat\s+dprd.*$/i', '', $clean);

            // C. Staf pelaksana & fungsional
            if (preg_match('/^penelaah\b/i', $clean)) {
                $clean = 'Penelaah Teknis Kebijakan';
            } elseif (preg_match('/^pengolah\s+data\b/i', $clean)) {
                $clean = 'Pengolah Data dan Informasi';
            } elseif (preg_match('/^pengadministrasi\b/i', $clean)) {
                $clean = 'Pengadministrasi Perkantoran';
            } elseif (preg_match('/^pelak?a?sa?na\s+(bidan|perawat|dokter|nutrisionis|sanitarian|sanitasi|penyuluh|apoteker|asisten\s+apoteker|pranata)/i', $clean)) {
                $clean = preg_replace('/^pelak?a?sa?na\s+/i', '', $clean);
                if (preg_match('/\bpenyuluh\s+kesmas\b/i', $clean)) {
                    $clean = 'Penyuluh Kesehatan Masyarakat';
                }
                $clean = $this->formatJabatanTitleCase($clean);
            } elseif (preg_match('/^(ahli\s+(pertama|muda|madya|utama))\s*-\s*(.+)$/i', $clean, $m)) {
                $jenjang = ucwords(strtolower($m[1]));
                $namaJbt = $this->formatJabatanTitleCase($m[3]);
                $clean = $namaJbt . ' ' . $jenjang;
            } elseif (preg_match('/^(terampil|mahir|penyelia|pemula)\s*-\s*(.+)$/i', $clean, $m)) {
                $jenjang = ucfirst(strtolower($m[1]));
                $namaJbt = $this->formatJabatanTitleCase($m[2]);
                $clean = $namaJbt . ' ' . $jenjang;
            } elseif (preg_match('/^(kepala\s+bidang|kabid|kepala\s+bagian|kabag|kepala\s+seksi|kasi|kepala\s+sub\s+bagian|kasubag|kepala\s+sub\s+bidang|kasubid)\b/i', $clean)) {
                $clean = $this->formatJabatanTitleCase($clean);
            } else {
                $clean = $this->formatJabatanTitleCase($clean);
            }

            $jabatan = $prefix . trim($clean);
        }

        return [
            'jabatan' => $jabatan,
            'unit' => $displayUnit,
            'is_plt' => $isPlt,
        ];
    }

    protected function formatJabatanTitleCase(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        $words = explode(' ', $clean);
        $lowerWords = ['dan', 'atau', 'di', 'ke', 'dari', 'pada', 'untuk', 'tentang', 'yang', 'serta', 'per'];
        $upperWords = ['sd', 'smp', 'sma', 'smk', 'tk', 'paud', 'pnf', 'sda', 'sdm', 'asn', 'pns', 'b3', 'tik', 'dprd', 'rsud', 'uptd', 'ppkn', 'ipa', 'ips', 'ktu', 'tu', 'bappeda', 'bkpsdma', 'bkad', 'dpmptsp', 'dinsos', 'dishub', 'disdik', 'dinkes', 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x'];

        $formatted = [];
        foreach ($words as $i => $w) {
            $prefixPunct = '';
            $suffixPunct = '';
            if (preg_match('/^([\(])(.*)$/', $w, $m)) {
                $prefixPunct = $m[1];
                $w = $m[2];
            }
            if (preg_match('/^(.*)([\),.:])$/', $w, $m)) {
                $suffixPunct = $m[2];
                $w = $m[1];
            }

            $wLower = strtolower($w);
            if (in_array($wLower, $upperWords)) {
                $resWord = strtoupper($wLower);
            } elseif ($i > 0 && in_array($wLower, $lowerWords)) {
                $resWord = $wLower;
            } else {
                $resWord = ucfirst($wLower);
            }
            $formatted[] = $prefixPunct . $resWord . $suffixPunct;
        }

        return implode(' ', $formatted);
    }

    protected function formatSchoolUnitName(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        $clean = preg_replace('/\s+kec\.?\s*.+$/i', '', $clean);
        $clean = preg_replace('/\s+kab\.?\s*sinjai.*$/i', '', $clean);

        if (preg_match('/^(uptd\s+)?smp(n|\s+negeri|\s+neg\.?)?\s*(\d+)(\s+sinjai)?(\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))?/i', $clean, $m)) {
            $nomor = $m[3];
            return 'UPTD SMP Negeri ' . $nomor . ' Sinjai';
        }

        if (preg_match('/^sdn\.?\s*(no\.?)?\s*(\d+)\s*(.*)$/i', $clean, $m)) {
            $nomor = $m[2];
            $lokasi = trim($m[3]);
            $lokasi = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $lokasi);
            $words = explode(' ', $lokasi);
            $words = array_map(fn($w) => ucfirst(strtolower($w)), $words);
            return 'SDN No. ' . $nomor . ($lokasi ? ' ' . implode(' ', $words) : '');
        }

        if (preg_match('/^sd\s+negeri\s*(\d+)\s*(.*)$/i', $clean, $m)) {
            $nomor = $m[1];
            $lokasi = trim($m[2]);
            $lokasi = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $lokasi);
            $words = explode(' ', $lokasi);
            $words = array_map(fn($w) => ucfirst(strtolower($w)), $words);
            return 'SD Negeri ' . $nomor . ($lokasi ? ' ' . implode(' ', $words) : '');
        }

        if (preg_match('/^tk\s+(negeri|neg\.?)\s*(pembina)?\s*(.*)$/i', $clean, $m)) {
            $isPembina = !empty($m[2]);
            $lokasi = trim($m[3]);
            $lokasi = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $lokasi);
            $words = explode(' ', $lokasi);
            $words = array_map(fn($w) => ucfirst(strtolower($w)), $words);
            $cleanLokasi = implode(' ', $words);
            return ($isPembina ? 'TK Negeri Pembina' : 'TK Negeri') . ($cleanLokasi ? ' ' . $cleanLokasi : '');
        }

        $words = explode(' ', $clean);
        $upperKeywords = ['sd', 'sdn', 'smp', 'smpn', 'sma', 'sman', 'smk', 'smkn', 'tk', 'paud', 'uptd', 'spf', 'no.', 'ii', 'iii', 'iv', 'vi', 'vii', 'viii', 'ix', 'x'];

        $formatted = [];
        foreach ($words as $w) {
            $wLower = strtolower($w);
            if (in_array($wLower, $upperKeywords)) {
                $formatted[] = strtoupper($wLower);
            } else {
                $formatted[] = ucfirst($wLower);
            }
        }

        return preg_replace('/\s+/', ' ', trim(implode(' ', $formatted)));
    }

    protected function formatHealthUnitName(string $str): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($str));
        $clean = preg_replace('/\s+kec\.?\s*.+$/i', '', $clean);
        $clean = preg_replace('/\s+kab\.?\s*sinjai.*$/i', '', $clean);

        if (preg_match('/(rsud|rs)\b.*(bulupancing|bulupaccing|bulu\s+paccing)/i', $clean)) {
            return 'RS Pratama Bulupaccing';
        }

        if (preg_match('/^(rumah\s+sakit\s+umum\s+daerah|rsud)\s*(sinjai)?/i', $clean)) {
            return 'RSUD Sinjai';
        }

        if (preg_match('/(laboratorium|lab\.?\s*kes)/i', $clean)) {
            return 'UPTD Laboratorium Kesehatan Daerah';
        }

        if (preg_match('/(psc|public\s+safety\s+center)\s*119/i', $clean)) {
            return 'UPTD PSC 119';
        }

        if (preg_match('/(puskesmas\s+pembantu|pustu)\b.*(uptd\s+)?puskesmas\s+([a-z\s]+)/i', $clean, $m)) {
            $induk = trim($m[3]);
            $induk = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $induk);
            return $this->formatHealthUnitName('UPTD Puskesmas ' . $induk);
        }
        if (preg_match('/(puskesmas\s+pembantu|pustu)\s+([a-z\s]+)\/.*puskesmas\s+([a-z\s]+)/i', $clean, $m)) {
            $induk = trim($m[3]);
            $induk = preg_replace('/\s+(sinjai\s+(utara|timur|barat|selatan|tengah|borong|tellulimpoe|bulupoddo|pulau\s+sembilan))$/i', '', $induk);
            return $this->formatHealthUnitName('UPTD Puskesmas ' . $induk);
        }

        $puskesmasMap = [
            'balangnipa' => 'Balangnipa',
            'samataring' => 'Samataring',
            'kampala' => 'Kampala',
            'panaikang' => 'Panaikang',
            'aska' => 'Aska',
            'samaenre' => 'Samaenre',
            'biji nangka' => 'Biji Nangka',
            'bijinangka' => 'Biji Nangka',
            'borong kompleks' => 'Borong Kompleks',
            'manimpahoi' => 'Manimpahoi',
            'lappae' => 'Lappae',
            'lappadata' => 'Lappadata',
            'manipi' => 'Manipi',
            'tengnga lembang' => 'Tengnga Lembang',
            'tengngalembang' => 'Tengnga Lembang',
            'tengalembang' => 'Tengnga Lembang',
            'mannanti' => 'Mannanti',
            'bulupoddo' => 'Bulupoddo',
            'pulau sembilan' => 'Pulau Sembilan',
            'pulau ix' => 'Pulau Sembilan',
        ];

        $cleanLower = strtolower($clean);
        foreach ($puskesmasMap as $key => $officialName) {
            if (str_contains($cleanLower, $key)) {
                return 'UPTD Puskesmas ' . $officialName;
            }
        }

        $clean = preg_replace('/^(tu|tata usaha|sub bagian tata usaha)\s+/i', '', $clean);
        if (preg_match('/^puskesmas\b/i', $clean)) {
            $clean = 'UPTD ' . $clean;
        }

        $words = explode(' ', $clean);
        $words = array_map(fn($w) => in_array(strtolower($w), ['uptd', 'rsud', 'pustu', 'psc']) ? strtoupper($w) : ucfirst(strtolower($w)), $words);
        return implode(' ', $words);
    }
}

