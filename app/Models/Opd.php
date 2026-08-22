<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opd extends Model
{
    use HasFactory;

    protected $table = 'opds';

    protected $fillable = [
        'unit_id',
        'name',
        'address',
        'phone',
        'email',
        'leader_name',
        'leader_rank',
        'leader_nip',
        'leader_nik',
        'leader_title',
        'leader_eselon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'unit_name', 'name');
    }

    public function signers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OpdSigner::class);
    }

    /**
     * Standardized official address mapping for Sinjai OPDs.
     */
    public static function getStandardAddresses(): array
    {
        return [
            '730701' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730702' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730705' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730706' => 'Jl. Bulo-Bulo Barat No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730707' => 'Jl. Stadion Mini No. 7, Sinjai Utara, Kab. Sinjai 92611',
            '730708' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730709' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611',
            '730710' => 'Jl. Bulo-Bulo Timur No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730711' => 'Jl. Jend. Ahmad Yani No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730712' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611',
            '730713' => 'Jl. H. Abdul Latief No. 8, Sinjai Utara, Kab. Sinjai 92611',
            '730714' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611',
            '730715' => 'Jl. Bulo-Bulo Barat No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730716' => 'Jl. Bulu Pattuku, Kel. Bongki, Kec. Sinjai Utara, Kab. Sinjai 92613',
            '730717' => 'Jl. Lamatti No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730718' => 'Jl. Persatuan Raya No. 121, Sinjai Utara, Kab. Sinjai 92611',
            '730720' => 'Jl. Persatuan Raya No. 98, Sinjai Utara, Kab. Sinjai 92611',
            '730721' => 'Jl. Jend. Ahmad Yani No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730722' => 'Jl. Jenderal Sudirman No. 4, Sinjai Utara, Kab. Sinjai 92611',
            '730723' => 'Jl. Jenderal Sudirman No. 2, Sinjai Utara, Kab. Sinjai 92611',
            '730724' => 'Jl. Lamatti No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730725' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611',
            '730726' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611',
            '730727' => 'Jl. Jenderal Sudirman No. 3, Sinjai Utara, Kab. Sinjai 92611',
            '730728' => 'Jl. Jenderal Sudirman No. 47, Sinjai Utara, Kab. Sinjai 92611',
            '730729' => 'Jl. Persatuan Raya No. 134, Sinjai Utara, Kab. Sinjai 92611',
            '730730' => 'Jl. Kartini No. 1, Sinjai Utara, Kab. Sinjai 92611',
            '730731' => 'Jl. Persatuan Raya No. 141, Sinjai Utara, Kab. Sinjai 92611',
            '730732' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
            '730733' => 'Jl. Bulu Kunyi No. 1, Sinjai Utara, Kab. Sinjai 92612',
            '730734' => 'Jl. Abd. Latif No. 1, Sinjai Timur, Kab. Sinjai 92611',
            '730735' => 'Jl. Damai No. 1, Lappadata, Kec. Sinjai Tengah, Kab. Sinjai 92652',
            '730736' => 'Jl. Persatuan Raya Bikeru No. 1B, Kec. Sinjai Selatan, Kab. Sinjai 92661',
            '730737' => 'Jl. Persatuan Raya No. A.69, Manipi, Kec. Sinjai Barat, Kab. Sinjai 92653',
            '730738' => 'Jl. Pendidikan No. 64, Pasir Putih, Kec. Sinjai Borong, Kab. Sinjai 92622',
            '730739' => 'Mannanti, Kec. Tellu Limpoe, Kab. Sinjai 92662',
            '730740' => 'Lamatti Riawang, Kec. Bulupoddo, Kab. Sinjai 92651',
            '730741' => 'Pulau Harapan, Kec. Pulau Sembilan, Kab. Sinjai 92655',
            '730743' => 'Jl. Jenderal Sudirman No. 19, Sinjai Utara, Kab. Sinjai 92611',
            '730745' => 'Jl. H. A. Abdul Latief No. 1, Sinjai Utara, Kab. Sinjai 92612',
            '730746' => 'Jl. Jenderal Sudirman No. 21, Sinjai Utara, Kab. Sinjai 92615',
            '730747' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611',
            '7307'   => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611',
        ];
    }

    /**
     * Clean and normalize OPD name, address, phone, and email from API.
     */
    public static function cleanAndFormatData(array $unit): array
    {
        $rawName = trim($unit['unit_nama'] ?? '');
        $unitId = $unit['unit_id'] ?? null;

        // Normalize all-caps names
        if ($rawName === mb_strtoupper($rawName) && mb_strlen($rawName) > 4) {
            $name = mb_convert_case($rawName, MB_CASE_TITLE, "UTF-8");
        } else {
            $name = $rawName;
        }

        $rawAddress = $unit['unit_alamat'] ?? '';
        $address = str_ireplace(['<br>', '<br/>', '<br />', '&nbsp;'], ', ', $rawAddress);
        $address = strip_tags($address);
        $address = html_entity_decode($address);
        $address = ltrim($address, ": \t\n\r\0\x0B");

        // Extract email if available in raw address or unit array
        $email = $unit['email'] ?? null;
        if (preg_match('/(?:Email\s*:\s*|E-mail\s*:\s*)([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $rawAddress, $m)) {
            $email = strtolower(trim($m[1]));
        }

        // Extract phone if available in raw address or unit array
        $phone = $unit['phone'] ?? null;
        if (preg_match('/(?:Tlp\.?|Telp\.?|Telepon|Phone|Fax\.?|Telp\/Fax\.?)\s*[:.]?\s*([0-9\(\)\s\-\/\.,]+)/i', $rawAddress, $m)) {
            $rawPhone = trim($m[1], ",. \t\n\r/");
            $rawPhone = preg_replace('/\s*(?:Kode|Fax|Email|Provinsi|Kabupaten).*/i', '', $rawPhone);
            $rawPhone = trim($rawPhone, ",. \t\n\r/");
            if (strlen($rawPhone) >= 4) {
                $phone = $rawPhone;
            }
        }

        // Use official standard address
        $standards = self::getStandardAddresses();
        if ($unitId && isset($standards[$unitId])) {
            $address = $standards[$unitId];
        } else {
            // Clean up address
            $address = preg_replace('/(?:Email\s*:\s*|E-mail\s*:\s*)[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i', '', $address);
            $address = preg_replace('/(?:Tlp\.?|Telp\.?|Telepon|Phone|Fax\.?|Telp\/Fax\.?)\s*[:.]?\s*[\(\)\d\s\-\/\.]+/i', '', $address);
            $address = preg_replace('/^(?:Jalan|Jln|Jl|JL)\.?\s*/i', 'Jl. ', $address);
            $address = preg_replace('/,\s*(?:Jalan|Jln|Jl|JL)\.?\s*/i', ', Jl. ', $address);
            $address = preg_replace('/^J\.\s+/i', 'Jl. ', $address);
            $address = str_ireplace(['Sunjai', 'SInjai', 'Bulo – Bulo', 'Bulo — Bulo'], ['Sinjai', 'Sinjai', 'Bulo-Bulo', 'Bulo-Bulo'], $address);

            if ($address === strtoupper($address) && strlen($address) > 5) {
                $address = ucwords(strtolower($address));
                $address = preg_replace('/\bJl\b/i', 'Jl.', $address);
                $address = preg_replace('/\bNo\b/i', 'No.', $address);
            }

            $address = preg_replace('/,\s*,+/', ',', $address);
            $address = preg_replace('/\s{2,}/', ' ', trim($address, ",. \t\n\r\0\x0B"));
            if ($address === '0' || empty($address)) {
                $address = null;
            }
        }

        return [
            'unit_id' => $unitId,
            'name' => trim($name),
            'address' => $address,
            'phone' => $phone ? trim($phone) : null,
            'email' => $email ? trim($email) : null,
            'is_active' => true,
        ];
    }

    /**
     * Clean and format bidang/unit name based on job title.
     */
    public static function cleanBidangName(string $title): string
    {
        $title = trim($title);
        if (preg_match('/^(?:Plt\.\s+)?Sekretaris\b/i', $title)) {
            return str_starts_with($title, 'Plt.') ? 'Sekretariat (Plt. Sekretaris)' : 'Sekretariat';
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Bidang\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Bagian\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Sub\s*bagian\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . preg_replace('/^Sub\s*bagian/i', 'Sub Bagian', $m[1]);
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Seksi\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(UPT\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Kepala\s+(Tata\s+Usaha\s+.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . $m[1];
        }
        if (preg_match('/^(?:Plt\.\s+)?Lurah\s+(.+)/i', $title, $m)) {
            return (str_starts_with($title, 'Plt.') ? 'Plt. ' : '') . 'Kelurahan ' . $m[1];
        }
        return $title;
    }

    /**
     * Resolve unit_id and clean info from SIMPEG API by OPD name if missing.
     */
    public function resolveUnitIdFromApi(): bool
    {
        if (!empty($this->unit_id)) {
            return true;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get('http://apps.sinjaikab.go.id/api/pegawai/get_unit');
            if ($response->successful()) {
                $units = $response->json();
                $unitList = isset($units['data']) && is_array($units['data']) ? $units['data'] : (is_array($units) ? $units : []);

                $cleanCurrent = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->name));
                foreach ($unitList as $u) {
                    $rawName = $u['unit_nama'] ?? '';
                    $cleanApi = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawName));
                    if ($cleanApi === $cleanCurrent || str_contains($cleanApi, $cleanCurrent) || str_contains($cleanCurrent, $cleanApi)) {
                        $cleaned = self::cleanAndFormatData($u);
                        $this->update([
                            'unit_id' => $cleaned['unit_id'] ?? $u['unit_id'] ?? null,
                            'address' => $this->address ?: ($cleaned['address'] ?? null),
                            'phone' => $this->phone ?: ($cleaned['phone'] ?? null),
                            'email' => $this->email ?: ($cleaned['email'] ?? null),
                        ]);
                        $this->refresh();
                        return !empty($this->unit_id);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore connection error
        }

        return false;
    }

    /**
     * Synchronize officials (pejabat bereselon) from SIMPEG API.
     */
    public function syncSignersFromApi(): int
    {
        if (empty($this->unit_id)) {
            $this->resolveUnitIdFromApi();
        }

        if (empty($this->unit_id)) {
            return 0;
        }

        try {
            $pegawaiList = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = \Illuminate\Support\Facades\Http::timeout(12)->get('http://apps.sinjaikab.go.id/api/pegawai/get_pegawai/', [
                    'unit_id' => $this->unit_id,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (is_array($data) && !isset($data['error'])) {
                        $pegawaiList = isset($data['data']) && is_array($data['data']) ? $data['data'] : (is_array($data) ? $data : []);
                        break;
                    }
                    if (isset($data['error']) && str_contains(strtolower((string)$data['error']), 'too many requests')) {
                        sleep(2);
                        continue;
                    }
                }
                if ($attempt < 3) {
                    sleep(1);
                }
            }

            if (!$pegawaiList || !is_array($pegawaiList)) {
                return 0;
            }

            // Sync Kepala OPD (check peran_khusus === 'KEPALA' or detect head title patterns)
            $headFound = false;
            foreach ($pegawaiList as $p) {
                $isHeadRole = ($p['peran_khusus'] ?? '') === 'KEPALA';
                $jobTitle = trim($p['jabatan_nama'] ?? '');
                $eselon = strtoupper(trim((string)($p['jabatan_jenis_eselon'] ?? '')));

                $isHeadTitle = preg_match('/^(?:Plt\.\s+|Pj\.\s+|Pjs\.\s+)?(?:Kepala\s+(?:Dinas|Badan|Satuan|Kantor|Pelaksana|UPTD)|Camat|Inspektur|Sekretaris\s+(?:Daerah|DPRD|Dewan)|Direktur)\b/i', $jobTitle)
                    || (in_array($eselon, ['II.A', 'II.B'], true) && !preg_match('/(?:Asisten|Staf\s+Ahli)/i', $jobTitle));

                if ($isHeadRole || $isHeadTitle) {
                    $pangkat = trim((string)($p['pangkat_nama'] ?? ''));
                    $eselonRaw = trim((string)($p['jabatan_jenis_eselon'] ?? ($p['eselon'] ?? '')));
                    $nik = trim((string)($p['nik'] ?? ($p['no_ktp'] ?? ($p['ktp'] ?? ($p['no_identitas'] ?? '')))));
                    $this->update([
                        'leader_name' => $p['nama'],
                        'leader_nip' => $p['nip'],
                        'leader_nik' => $nik ?: $this->leader_nik,
                        'leader_title' => $jobTitle ?: $this->leader_title,
                        'leader_rank' => $pangkat ?: null,
                        'leader_eselon' => $eselonRaw ?: ($eselon ?: 'II.a'),
                    ]);

                    // Sync Kepala OPD as User with role: pimpinan
                    if (!empty($p['nip'])) {
                        $leaderUser = User::where('nip', $p['nip'])->first();
                        if (!$leaderUser) {
                            $leaderUser = User::create([
                                'nip' => $p['nip'],
                                'nik' => $nik ?: null,
                                'name' => trim($p['nama'] ?? $p['nip']),
                                'unit_name' => $this->name,
                                'jabatan' => $jobTitle ?: $this->leader_title,
                                'password' => null,
                            ]);
                        } else {
                            $leaderUser->update([
                                'name' => trim($p['nama'] ?? $leaderUser->name),
                                'nik' => $nik ?: $leaderUser->nik,
                                'unit_name' => $this->name,
                                'jabatan' => $jobTitle ?: $leaderUser->jabatan,
                            ]);
                        }
                        if (!$leaderUser->hasRole('admin')) {
                            $leaderUser->syncRoles(['pimpinan']);
                        }
                    }
                    $headFound = true;
                    break;
                }
            }

            $allowedEselons = ['II.A', 'II.B', 'III.A', 'III.B'];

            // Delete any existing signers below Eselon III.b (e.g. IV.a, IV.b) for this OPD
            $this->signers()->whereNotIn('eselon', ['II.a', 'II.b', 'III.a', 'III.b'])->delete();

            $syncedCount = 0;
            foreach ($pegawaiList as $p) {
                $eselon = trim((string)($p['jabatan_jenis_eselon'] ?? ($p['eselon'] ?? null)));
                if (empty($eselon) || $eselon === '-' || $eselon === 'null') {
                    continue;
                }

                // Filter up to Eselon III.b only (exclude IV.a, IV.b, V, etc.)
                if (!in_array(strtoupper($eselon), $allowedEselons, true)) {
                    continue;
                }

                // If this is the main Kepala OPD, skip adding to sub-bidang
                if (!empty($this->leader_nip) && ($p['nip'] ?? '') === $this->leader_nip) {
                    continue;
                }

                $title = trim($p['jabatan_nama'] ?? '');
                if (empty($title)) {
                    continue;
                }

                $bidangName = self::cleanBidangName($title);
                $pangkat = trim((string)($p['pangkat_nama'] ?? ''));
                $signerNik = trim((string)($p['nik'] ?? ($p['no_ktp'] ?? ($p['ktp'] ?? ($p['no_identitas'] ?? '')))));

                OpdSigner::updateOrCreate(
                    [
                        'opd_id' => $this->id,
                        'title' => $title,
                    ],
                    [
                        'bidang_name' => $bidangName,
                        'name' => trim($p['nama'] ?? ''),
                        'nip' => !empty($p['nip']) ? trim($p['nip']) : null,
                        'nik' => $signerNik ?: null,
                        'rank' => $pangkat ?: null,
                        'eselon' => $eselon,
                        'is_active' => true,
                    ]
                );

                // Sync Signer as User with role: pimpinan
                if (!empty($p['nip'])) {
                    $signerUser = User::where('nip', trim($p['nip']))->first();
                    if (!$signerUser) {
                        $signerUser = User::create([
                            'nip' => trim($p['nip']),
                            'nik' => $signerNik ?: null,
                            'name' => trim($p['nama'] ?? $p['nip']),
                            'unit_name' => $this->name,
                            'jabatan' => $title,
                            'password' => null,
                        ]);
                    } else {
                        $signerUser->update([
                            'name' => trim($p['nama'] ?? $signerUser->name),
                            'nik' => $signerNik ?: $signerUser->nik,
                            'unit_name' => $this->name,
                            'jabatan' => $title,
                        ]);
                    }
                    if (!$signerUser->hasRole('admin')) {
                        $signerUser->syncRoles(['pimpinan']);
                    }
                }

                $syncedCount++;
            }

            // Sync Admin OPD (Kasubag Kepegawaian / Kasubag Umum dan Kepegawaian / Kasubag TU & Kepegawaian)
            $adminOpdCandidate = null;

            // Prioritas 1: Jabatan Kasubag yang mengandung kata 'Kepegawaian'
            foreach ($pegawaiList as $p) {
                $jab = trim($p['jabatan_nama'] ?? '');
                if (preg_match('/(?:Kasubag|Kepala\s+Sub\s*\.?\s*Bagian)\s+.*kepegawaian/i', $jab)
                    && !preg_match('/(?:RSUD|Puskesmas|SDN|SMPN|Kelurahan|Pustu)/i', $jab)) {
                    $adminOpdCandidate = $p;
                    break;
                }
            }

            // Prioritas 2: Jabatan Kasubag Umum / TU jika tidak ada kata kepegawaian eksplisit
            if (!$adminOpdCandidate) {
                foreach ($pegawaiList as $p) {
                    $jab = trim($p['jabatan_nama'] ?? '');
                    if (preg_match('/(?:Kasubag|Kepala\s+Sub\s*\.?\s*Bagian)\s+.*(?:umum|tata\s+usaha)/i', $jab)
                        && !preg_match('/(?:RSUD|Puskesmas|SDN|SMPN|Kelurahan|Pustu)/i', $jab)) {
                        $adminOpdCandidate = $p;
                        break;
                    }
                }
            }

            if ($adminOpdCandidate && !empty($adminOpdCandidate['nip'])) {
                $nip = trim($adminOpdCandidate['nip']);
                $adminUser = User::where('nip', $nip)->first();
                if (!$adminUser) {
                    $adminUser = User::create([
                        'nip' => $nip,
                        'name' => trim($adminOpdCandidate['nama'] ?? $nip),
                        'unit_name' => $this->name,
                        'jabatan' => trim($adminOpdCandidate['jabatan_nama'] ?? ''),
                        'password' => bcrypt($nip),
                    ]);
                } else {
                    $adminUser->update([
                        'name' => trim($adminOpdCandidate['nama'] ?? $adminUser->name),
                        'unit_name' => $this->name,
                        'jabatan' => trim($adminOpdCandidate['jabatan_nama'] ?? $adminUser->jabatan),
                    ]);
                }

                if (!$adminUser->hasRole('admin')) {
                    $adminUser->syncRoles(['admin_opd']);
                }
            }

            return $syncedCount;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error syncing signers: ' . $e->getMessage());
            return 0;
        }
    }
}
